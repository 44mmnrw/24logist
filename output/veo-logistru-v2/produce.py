import base64, json, pathlib, runpy, sys, wave, urllib.request

OUT = pathlib.Path(__file__).resolve().parent
ROOT = OUT.parents[1]
args = sys.argv[1:]
sys.argv = ['generate.py', 'library']
legacy = runpy.run_path(str(ROOT / 'output/veo-logistru/generate.py'))
api = legacy['api']

if args[0] == 'tts':
    i = int(args[1])
    target = OUT / f'voice-{i}.wav'
    if target.exists(): raise SystemExit('Voice already exists')
    lines = ['Зато в очереди на заправку стоять не приходится.', 'Тут вы, конечно, правы!']
    voices = ['Charon', 'Puck']
    mood = ['Немного иронично, с лёгкой самоуверенностью, взрослый мужчина около 35 лет.', 'Доброжелательно, с улыбкой, взрослый мужчина около 35 лет.']
    prompt = 'Озвучь на русском языке, естественно, без акцента, в разговорном темпе. ' + mood[i-1] + ' Произнеси только следующую реплику, без вступлений, комментариев и добавленных слов:\n' + lines[i-1]
    result = api('models/gemini-3.1-flash-tts-preview:generateContent', {'contents':[{'parts':[{'text':prompt}]}], 'generationConfig':{'responseModalities':['AUDIO'], 'speechConfig':{'voiceConfig':{'prebuiltVoiceConfig':{'voiceName': voices[i-1]}}}}})
    parts = result['candidates'][0]['content']['parts']
    pcm = b''.join(base64.b64decode(p['inlineData']['data']) for p in parts if 'inlineData' in p)
    if not pcm: raise SystemExit('No audio returned')
    with wave.open(str(target), 'wb') as w:
        w.setnchannels(1); w.setsampwidth(2); w.setframerate(24000); w.writeframes(pcm)
    print(json.dumps({'file':str(target),'seconds':len(pcm)/48000}))
elif args[0] == 'submit':
    name = args[1]
    state = OUT / (name + '.operation.json')
    if state.exists(): raise SystemExit('Existing operation: no duplicate submission')
    prompt = (OUT / (name + '.prompt.txt')).read_text(encoding='utf-8')
    start = OUT / (name + '.png')
    body = {'instances':[{'prompt':prompt,'image':{'bytesBase64Encoded':base64.b64encode(start.read_bytes()).decode(),'mimeType':'image/png'}}], 'parameters':{'aspectRatio':'9:16','durationSeconds':8,'resolution':'720p','sampleCount':1,'personGeneration':'allow_adult'}}
    end = OUT / (name + '.end.png')
    if end.exists():
        body['instances'][0]['lastFrame'] = {'bytesBase64Encoded':base64.b64encode(end.read_bytes()).decode(),'mimeType':'image/png'}
    result = api('models/veo-3.1-generate-preview:predictLongRunning',body)
    state.write_text(json.dumps(result),encoding='utf-8')
    print(json.dumps(result))
elif args[0] == 'status':
    name = args[1]
    state=json.loads((OUT/(name+'.operation.json')).read_text())
    result=api(state['name'])
    (OUT/(name+'.status.json')).write_text(json.dumps(result),encoding='utf-8')
    print(json.dumps(result))
elif args[0] == 'download':
    name=args[1]
    result=json.loads((OUT/(name+'.status.json')).read_text())
    uri=result['response']['generateVideoResponse']['generatedSamples'][0]['video']['uri']
    if not uri.startswith('https://generativelanguage.googleapis.com/'): raise SystemExit('Unexpected media URL')
    request=urllib.request.Request(uri,headers={'x-goog-api-key':legacy['KEY']})
    with urllib.request.urlopen(request,timeout=120) as r: content=r.read()
    (OUT/(name+'.mp4')).write_bytes(content)
    print(json.dumps({'file':name+'.mp4','bytes':len(content)}))
