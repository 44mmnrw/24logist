import json,pathlib,subprocess,sys
OUT=pathlib.Path(__file__).resolve().parent
sys.path.insert(0,str(OUT.parent/'veo-logistru/deps'))
import imageio_ffmpeg
raw=subprocess.check_output([imageio_ffmpeg.get_ffmpeg_exe(),'-v','error','-i',str(OUT/'turn2.mp4'),'-vf','scale=360:640','-f','rawvideo','-pix_fmt','rgb24','-'])
size=360*640*3
cx,cy=58,390
points=[]
for n in range(110):
    frame=raw[n*size:(n+1)*size]
    candidates=[]
    for y in range(max(0,int(cy)-10),min(640,int(cy)+10)):
        for x in range(max(0,int(cx)-16),min(360,int(cx)+30)):
            k=(y*360+x)*3
            r,g,b=frame[k:k+3]
            if r>45 and r>g*1.55 and r>b*1.45:
                candidates.append((x,y))
    if len(candidates)>3:
        cx=sum(p[0] for p in candidates)/len(candidates)
        cy=sum(p[1] for p in candidates)/len(candidates)
    points.append([n,round(cx*2,2),round(cy*2,2),len(candidates)])
(OUT/'tail-track.json').write_text(json.dumps(points),encoding='utf-8')
print(json.dumps(points[::6]))
