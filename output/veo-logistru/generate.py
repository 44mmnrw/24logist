import base64
import json
import pathlib
import sys
import urllib.error
import urllib.request
import urllib.parse

ROOT = pathlib.Path(__file__).resolve().parents[2]
OUT = pathlib.Path(__file__).resolve().parent
KEY = None
for line in (ROOT / '.env').read_text(encoding='utf-8-sig').splitlines():
    name, sep, value = line.strip().removeprefix('export ').partition('=')
    if sep and name.strip() == 'API_KEY_GOOGLE':
        KEY = value.strip().strip('\"\'')
        break
if not KEY:
    raise SystemExit('API_KEY_GOOGLE missing')

def api(path, data=None):
    req = urllib.request.Request('https://generativelanguage.googleapis.com/v1beta/' + path,
        data=json.dumps(data).encode() if data is not None else None,
        headers={'x-goog-api-key': KEY, 'Content-Type': 'application/json'})
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            return json.load(r)
    except urllib.error.HTTPError as e:
        message = e.read().decode().replace(KEY, '[REDACTED]')
        print('HTTP', e.code, message)
        raise SystemExit(1)

COMMON = '''Photorealistic premium Russian commercial. Vertical 9:16 framing, realistic daylight, natural colors, subtle humor. A quiet modern four-way intersection with traffic lights. Across the intersection diagonally on the far right is a modern red service station with EV charging and a large banner reading exactly: Техподдержка, Инструкции, Роуминг. Across the street from that station on the far left is a rustic wooden cowboy saloon. Right-hand traffic. A white Tesla sedan is in the right lane, and a chestnut horse with an adult male rider in a charcoal office suit and white shirt is immediately to its left. Tesla driver is an adult man in a light blue shirt seated INSIDE the car, left window open. Both men are about 35, short brown hair, clean shaven. The Tesla has a large decal on its RIGHT side matching the supplied LogistRu logo reference, dark navy and blue Cyrillic lettering, blue truck and green pin. The reference is a printed car decal, not the scene or a full screen graphic. Keep spatial geography, characters, clothing and vehicle consistent. No subtitles, no added captions, no music, natural street sound.'''
SCENES = [
    '''First 3 seconds: overhead drone establishing shot of the EMPTY intersection, service station and cowboy saloon both visible in portrait composition. Then cut to a rear three-quarter street view: the white Tesla arrives on the right with its RIGHT amber indicator blinking; the chestnut horse and office-suited rider arrive to its left. They come to a complete stop at the white stop line at a clearly RED traffic signal. End with both stopped side by side, rider turns his head toward driver. No dialogue in this shot. Camera can see a little of the Tesla right-side logo. 8 seconds.''',
    '''Both are already stopped at a RED light. Medium two-shot showing the horse rider on the left and Tesla driver seated inside the open left window on the right. They meet each other's gaze. The rider spreads both hands, reins loose, and says in clear conversational Russian, mildly smug: «Зато в очереди на заправку стоять не приходится». Then the Tesla driver smiles warmly and replies in Russian: «Тут вы, конечно, правы!» Distinct natural male voices, correct speaker assignment, synchronized lips. Fit both lines naturally within 8 seconds. Both horse and car remain stationary. Red light throughout.''',
    '''Start with Tesla and horse stopped side by side, rear three-quarter view. Traffic light switches from RED to GREEN. The Tesla's RIGHT turn indicator blinks. Tesla makes a smooth legal RIGHT turn. Camera tracks around to show the car's RIGHT SIDE with the large supplied ЛогистРу logo clearly visible and readable as it turns and drives away. The man in the office suit remains sitting on the stationary chestnut horse at the intersection, watching the car leave. End holding a well composed portrait shot of the lone rider and horse with the intersection beyond. No dialogue, no subtitles, no end card, natural quiet electric car and horse sounds. 8 seconds.'''
]

if sys.argv[1] == 'submit':
    i = int(sys.argv[2])
    state = OUT / f'scene-{i}.operation.json'
    if state.exists():
        raise SystemExit('Existing operation; use status to avoid duplicate charges')
    logo = pathlib.Path('d:/Documents/www/logist/Лого/logo_platform.png').read_bytes()
    prompt = COMMON + '\n\n' + SCENES[i-1]
    (OUT / f'scene-{i}.prompt.txt').write_text(prompt, encoding='utf-8')
    body = {'instances': [{'prompt': prompt, 'referenceImages': [{'image': {'bytesBase64Encoded': base64.b64encode(logo).decode(), 'mimeType': 'image/png'}, 'referenceType': 'asset'}]}],
            'parameters': {'aspectRatio': '9:16', 'durationSeconds': 8, 'resolution': '720p', 'sampleCount': 1, 'personGeneration': 'allow_adult'}}
    continuity = OUT / 'continuity.png'
    if i > 1 and continuity.exists():
        body['instances'][0]['referenceImages'].append({'image': {'bytesBase64Encoded': base64.b64encode(continuity.read_bytes()).decode(), 'mimeType': 'image/png'}, 'referenceType': 'asset'})
    result = api('models/veo-3.1-generate-preview:predictLongRunning', body)
    state.write_text(json.dumps(result), encoding='utf-8')
    print(json.dumps(result))
elif sys.argv[1] == 'download':
    i = int(sys.argv[2])
    result = json.loads((OUT / f'scene-{i}.status.json').read_text())
    video = result['response']['generateVideoResponse']['generatedSamples'][0]['video']
    uri = video['uri']
    class GoogleRedirect(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, req, fp, code, msg, headers, newurl):
            host = urllib.parse.urlparse(newurl).hostname or ''
            if not host.endswith(('.googleapis.com', '.googleusercontent.com')):
                raise RuntimeError('Unexpected download redirect host')
            return super().redirect_request(req, fp, code, msg, headers, newurl)
    host = urllib.parse.urlparse(uri).hostname or ''
    if not host.endswith(('.googleapis.com', '.googleusercontent.com')):
        raise SystemExit('Unexpected download host')
    req = urllib.request.Request(uri, headers={'x-goog-api-key': KEY})
    with urllib.request.build_opener(GoogleRedirect()).open(req, timeout=120) as r:
        content = r.read()
    target = OUT / f'scene-{i}.mp4'
    target.write_bytes(content)
    print(json.dumps({'file': str(target), 'bytes': len(content)}))
elif sys.argv[1] == 'status':
    i = int(sys.argv[2])
    state = json.loads((OUT / f'scene-{i}.operation.json').read_text())
    result = api(state['name'])
    (OUT / f'scene-{i}.status.json').write_text(json.dumps(result), encoding='utf-8')
    print(json.dumps(result))
