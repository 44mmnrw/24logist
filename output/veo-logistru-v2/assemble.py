"""Reproducible review edit. Corrected departure remains a still until Veo is funded."""
import pathlib, subprocess, sys, json, wave
OUT=pathlib.Path(__file__).resolve().parent
sys.path.insert(0,str(OUT.parent/'veo-logistru/deps'))
import imageio_ffmpeg
FF=imageio_ffmpeg.get_ffmpeg_exe()
LOGO=pathlib.Path('d:/Documents/www/logist/Лого/logo_platform.png')

def run(*args):
    subprocess.run([FF,'-hide_banner','-loglevel','error','-y',*map(str,args)],cwd=OUT,check=True)

def plane(asset,coords,label):
    x0,y0,x1,y1,x2,y2,x3,y3=coords
    return f'[{asset}:v]format=rgba,pad=iw+20:ih+20:10:10:color=black@0,scale=720:1280,perspective=x0={x0}:y0={y0}:x1={x1}:y1={y1}:x2={x2}:y2={y2}:x3={x3}:y3={y3}:sense=destination[{label}]'

def decorated_still(source,target,board,logo=None):
    args=['-i',source,'-i','banner.png']
    filters=['[0:v]scale=720:1280[base]',plane(1,board,'board'),'[base][board]overlay=0:0:format=auto[branded]']
    last='branded'
    if logo:
        args+=['-i',LOGO]
        filters+=[plane(2,logo,'decal'),'[branded][decal]overlay=0:0:format=auto[finished]']
        last='finished'
    run(*args,'-filter_complex',';'.join(filters),'-map',f'[{last}]','-frames:v',1,'-update',1,target)

def hold(source,target,duration,filter='null'):
    run('-loop',1,'-framerate',24,'-i',source,'-vf',filter,'-t',duration,'-an','-c:v','libx264','-crf',18,'-preset','fast','-pix_fmt','yuv420p',target)

def clip(source,target,duration,start=0):
    run('-ss',start,'-i',source,'-t',duration,'-an','-c:v','libx264','-crf',18,'-preset','fast','-pix_fmt','yuv420p',target)

decorated_still('master.png','stop-branded.png',(435,405,650,405,435,461,650,460))
decorated_still('turn2.png','departure-branded.png',(421,458,619,458,421,511,619,510),(283,796,565,804,309,852,566,849))
decorated_still('lone.png','lone-branded.png',(421,458,619,458,421,511,619,510))

# The aerial camera drifts only a few pixels during the used first three seconds.
filters=plane(1,(512,87,685,86,512,134,679,133),'board')+';[0:v][board]overlay=0:0:format=auto[v]'
run('-i','overhead.mp4','-loop',1,'-i','banner.png','-filter_complex',filters,'-map','[v]','-t',3,'-an','-c:v','libx264','-crf',18,'-preset','fast','-pix_fmt','yuv420p','01-aerial.mp4')
hold('stop-branded.png','02-stop.mp4',3.5)
clip('rider.mp4','03-rider.mp4',3)
hold('stop-branded.png','04-listener.mp4',1.5)
clip('driver.mp4','05-driver.mp4',3)
hold('turn2.png','06-green.mp4',1,'crop=210:374:365:175,scale=720:1280')
hold('departure-branded.png','07-departure-PENDING.mp4',5)
hold('lone-branded.png','08-lone.mp4',3)

# Transparent source logo: no opaque rectangle or background plate.
filt=r"[0:v]gblur=sigma=22,eq=brightness=-0.17:saturation=0.6[bg];[1:v]scale=610:-1,format=rgba,split=2[logo][glow];[glow]lutrgb=r=255:g=255:b=255,gblur=sigma=5[halo];[bg][halo]overlay=x=55:y=505[tmp];[tmp][logo]overlay=x=55:y=505,drawtext=fontfile='C\:/Windows/Fonts/arial.ttf':textfile=slogan-1.txt:fontsize=40:fontcolor=white:shadowcolor=black@0.4:shadowx=1:shadowy=2:x=(w-tw)/2:y=735,drawtext=fontfile='C\:/Windows/Fonts/arial.ttf':textfile=slogan-2.txt:fontsize=38:fontcolor=white:shadowcolor=black@0.4:shadowx=1:shadowy=2:x=(w-tw)/2:y=792,fade=t=in:st=0:d=0.35[v]"
run('-loop',1,'-framerate',24,'-i','lone-branded.png','-i',LOGO,'-filter_complex',filt,'-map','[v]','-t',5,'-an','-c:v','libx264','-crf',18,'-preset','fast','-pix_fmt','yuv420p','09-endcard.mp4')
parts=['01-aerial','02-stop','03-rider','04-listener','05-driver','06-green','07-departure-PENDING','08-lone','09-endcard']
(OUT/'review-concat.txt').write_text(''.join(f"file '{x}.mp4'\n" for x in parts),encoding='utf-8')
run('-f','concat','-safe',0,'-i','review-concat.txt','-c','copy','picture-review.mp4')
# 28 seconds. Speech is isolated from all Veo-generated audio.
mix='[1:a]aresample=48000,adelay=6500|6500,apad,atrim=duration=28[v1];[2:a]aresample=48000,adelay=11000|11000,apad,atrim=duration=28[v2];[3:a]lowpass=f=800,highpass=f=120,volume=0.16[amb];[v1][v2][amb]amix=inputs=3:duration=longest:normalize=0,alimiter=limit=0.95[a]'
run('-i','picture-review.mp4','-i','voice-1.wav','-i','voice-2.wav','-f','lavfi','-i','anoisesrc=color=pink:amplitude=0.015:duration=28:sample_rate=48000','-filter_complex',mix,'-map','0:v','-map','[a]','-t',28,'-c:v','copy','-c:a','aac','-b:a','192k','-movflags','+faststart','LogistRu-9x16-v2-review.mp4')
run('-ss',25,'-i','LogistRu-9x16-v2-review.mp4','-frames:v',1,'-update',1,'endcard-check.png')
run('-i','LogistRu-9x16-v2-review.mp4','-f','null','-')
(OUT/'review-status.json').write_text(json.dumps({'status':'incomplete','duration_seconds':28,'pending':'Correct rightward departure animation blocked by Google prepayment balance. Review uses a still from 15 to 20 seconds.','veo_audio_used':False,'logo_background':'transparent'},ensure_ascii=False,indent=2),encoding='utf-8')
print('Rendered review MP4; departure animation is still pending.')
