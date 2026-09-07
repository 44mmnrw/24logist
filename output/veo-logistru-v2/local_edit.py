"""Local-only third edit; existing footage, masks, camera transforms and synthesized FX."""
import pathlib,subprocess,sys,json,math,wave,random,struct
OUT=pathlib.Path(__file__).resolve().parent
sys.path.insert(0,str(OUT.parent/'veo-logistru/deps'))
import imageio_ffmpeg
FF=imageio_ffmpeg.get_ffmpeg_exe()
LOGO=pathlib.Path('d:/Documents/www/logist/Лого/logo_platform.png')
def run(*args):
    subprocess.run([FF,'-hide_banner','-loglevel','error','-y',*map(str,args)],cwd=OUT,check=True)
ENC=['-an','-c:v','libx264','-crf','18','-preset','fast','-pix_fmt','yuv420p']
def push(source,target,duration,z0,z1,cx=.5,cy=.5,still=False,start=0):
    frames=round(duration*24)
    z=f'{z0}+({z1-z0})*on/{max(1,frames-1)}'
    vf=f"scale=2160:3840,zoompan=z='{z}':x='(iw-iw/zoom)*{cx}':y='(ih-ih/zoom)*{cy}':d={'%d'%frames if still else '1'}:s=720x1280:fps=24,eq=contrast=1.018:saturation=0.98"
    args=['-i',source] if still else ['-ss',start,'-i',source]
    run(*args,'-vf',vf,'-t',duration,*ENC,target)

push('01-aerial.mp4','local-01.mp4',3,1,1.075,.54,.35)
push('stop-branded.png','local-02.mp4',3.5,1,1.07,.58,.65,True)
push('03-rider.mp4','local-03.mp4',3,1,1.025,.55,.35)
push('stop-branded.png','local-04.mp4',1.5,1.07,1.105,.58,.65,True)
push('05-driver.mp4','local-05.mp4',3,1,1.025,.6,.4)
push('06-green.mp4','local-06.mp4',1,1,1.04,.5,.5)

# Reconstruct the beginning of the right turn from the isolated car in an existing take.
shadow="[3:v]format=rgba,geq=r=0:g=0:b=0:a='65*exp(-pow((X-470)/175,2)-pow((Y-(930-0.30*(X-300)))/24,2))'[shadow]"
forward=shadow+";[0:v][shadow]overlay=x='35*pow(t/1.5,2)':y='-22*pow(t/1.5,2)'[bg];[2:v]gblur=sigma=1.5[mask];[1:v]format=rgba[car];[car][mask]alphamerge,hflip[cut];[bg][cut]overlay=x=220:y=0:format=auto,format=yuv420p[v]"
run('-loop',1,'-framerate',24,'-i','lone-branded.png','-ss',.25,'-i','turn.mp4','-f','rawvideo','-pixel_format','gray','-video_size','720x1280','-framerate',24,'-i','forward-mask.gray','-f','lavfi','-i','color=black@0:s=720x1280:r=24','-filter_complex',forward,'-map','[v]','-t',1.5,*ENC,'local-forward-base.mp4')
push('local-forward-base.mp4','local-07.mp4',1.5,1,1.02,.65,.65)

def plane(asset,coords,label,alpha=1):
    corner=':'.join(f'{k}={v*2}' for k,v in zip(['x0','y0','x1','y1','x2','y2','x3','y3'],coords))
    return f'[{asset}:v]format=rgba,pad=iw+20:ih+20:10:10:color=black@0,scale=1440:2560,perspective={corner}:sense=destination,scale=720:1280,colorchannelmixer=aa={alpha}[{label}]'
track=json.loads((OUT/'tail-track.json').read_text())
def motion(axis):
    base=sum(p[axis] for p in track[:6])/6
    pts=[(p[0]/24,p[axis]-base) for p in track[::3]]
    expr='1000' if axis==1 else str(pts[-1][1])
    for (t0,v0),(t1,v1) in reversed(list(zip(pts,pts[1:]))):
        expr=f'if(lt(t,{t1}),({v0}+(t-{t0})*{(v1-v0)/(t1-t0)}),{expr})'
    return expr
# Smaller and less contrasty decal, composited in perspective and tracked to the body.
filt=plane(1,(421,458,619,458,421,511,619,510),'board')+';'+plane(2,(350,804,526,806,361,844,527,841),'decal',.63)+f";[0:v][board]overlay=x='0.25*t':y='-0.7*t':format=auto[b];[b][decal]overlay=x='{motion(1)}':y='{motion(2)}':enable='lt(t,4.45)':format=auto[v]"
run('-i','turn2.mp4','-loop',1,'-i','banner.png','-loop',1,'-i',LOGO,'-filter_complex',filt,'-map','[v]','-t',8,*ENC,'local-departure-base.mp4')
push('local-departure-base.mp4','local-08.mp4',4,1.015,1.025,.60,.60,start=1.75)
push('local-departure-base.mp4','local-09.mp4',2.5,1.025,1.075,.22,.57,start=5.5)

# Continue the final scene while it darkens and blurs; logo retains its transparent alpha.
run('-ss',2.45,'-i','local-09.mp4','-frames:v',1,'-update',1,'local-end-source.png')
end=r"[0:v]scale=2160:3840,zoompan=z='1+0.035*on/119':x='(iw-iw/zoom)*0.25':y='(ih-ih/zoom)*0.55':d=120:s=720x1280:fps=24,split=2[sharp][soft];[soft]gblur=sigma=22,eq=brightness=-0.17:saturation=0.6[blur];[sharp][blur]blend=all_expr='A*(1-min(T/0.8,1))+B*min(T/0.8,1)'[bg];[1:v]scale=610:-1,format=rgba,split=2[logo][glow];[glow]lutrgb=r=255:g=255:b=255,colorchannelmixer=aa=0.45,gblur=sigma=4[halo];[bg][halo]overlay=x=55:y=505:enable='gte(t,0.55)'[tmp];[tmp][logo]overlay=x=55:y=505:enable='gte(t,0.55)',drawtext=fontfile='C\:/Windows/Fonts/arial.ttf':textfile=slogan-1.txt:fontsize=40:fontcolor=white:shadowcolor=black@0.4:shadowx=1:shadowy=2:x=(w-tw)/2:y=735:enable='gte(t,0.75)',drawtext=fontfile='C\:/Windows/Fonts/arial.ttf':textfile=slogan-2.txt:fontsize=38:fontcolor=white:shadowcolor=black@0.4:shadowx=1:shadowy=2:x=(w-tw)/2:y=792:enable='gte(t,0.75)'[v]"
run('-i','local-end-source.png','-i',LOGO,'-filter_complex',end,'-map','[v]','-t',5,*ENC,'local-10.mp4')
parts=[f'local-{i:02d}.mp4' for i in range(1,11)]
(OUT/'local-concat.txt').write_text(''.join(f"file '{p}'\n" for p in parts),encoding='utf-8')
run('-f','concat','-safe',0,'-i','local-concat.txt','-c','copy','local-picture.mp4')

# Deterministic Foley generated locally: indicator ticks, restrained wind, tire pass.
rate=48000; duration=28; rng=random.Random(704)
events=[(3.3+i*.72,.032,.072,1400) for i in range(18)]
events += [(3.13,.10,.09,115),(3.37,.085,.065,170)]
active={}
for start,length,gain,freq in events:
    for n in range(round(length*rate)):
        t=n/rate; idx=round(start*rate)+n
        env=math.exp(-t/(length*.23))
        val=gain*env*(.65*math.sin(2*math.pi*freq*t)+.35*rng.uniform(-1,1))
        active[idx]=active.get(idx,0)+val
pcm=bytearray(); low=0
for i in range(duration*rate):
    t=i/rate; noise=rng.uniform(-1,1); low=.97*low+.03*noise
    wind=.020*low*(.7+.3*math.sin(t*.73)**2)
    passage=math.exp(-((t-18.5)/1.05)**2)
    tire=.038*passage*(.8*low+.2*noise)
    hum=.014*passage*math.sin(2*math.pi*(180*t+6*t*t))
    value=max(-.9,min(.9,wind+tire+hum+active.get(i,0)))
    pcm+=struct.pack('<h',round(value*32767))
with wave.open(str(OUT/'local-foley.wav'),'wb') as w:
    w.setnchannels(1);w.setsampwidth(2);w.setframerate(rate);w.writeframes(pcm)
mix='[1:a]volume=1[voice];[2:a]afade=t=out:st=23:d=5[fx];[voice][fx]amix=inputs=2:normalize=0,alimiter=limit=0.95[a]'
run('-i','local-picture.mp4','-i','LogistRu-9x16-v2.mp4','-i','local-foley.wav','-filter_complex',mix,'-map','0:v','-map','[a]','-t',28,'-c:v','copy','-c:a','aac','-b:a','192k','-movflags','+faststart','LogistRu-9x16-v3-local.mp4')
run('-i','LogistRu-9x16-v3-local.mp4','-vf','fps=1/2,scale=270:480,tile=5x3','-frames:v',1,'-update',1,'local-final-contact.png')
run('-i','local-07.mp4','-vf','fps=4,scale=360:640,tile=4x2','-frames:v',1,'-update',1,'local-forward-check.png')
run('-i','LogistRu-9x16-v3-local.mp4','-f','null','-')
print('Local-only v3 export complete, 28 seconds.')
