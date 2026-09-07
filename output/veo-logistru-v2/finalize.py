import json,pathlib,subprocess,sys
OUT=pathlib.Path(__file__).resolve().parent
sys.path.insert(0,str(OUT.parent/'veo-logistru/deps'))
import imageio_ffmpeg
FF=imageio_ffmpeg.get_ffmpeg_exe()
LOGO=pathlib.Path('d:/Documents/www/logist/Лого/logo_platform.png')
def run(*args):
    subprocess.run([FF,'-hide_banner','-loglevel','error','-y',*map(str,args)],cwd=OUT,check=True)

def plane(asset,coords,label):
    p=[v*2 for v in coords]
    keys=['x0','y0','x1','y1','x2','y2','x3','y3']
    corner=':'.join(f'{k}={v}' for k,v in zip(keys,p))
    return f'[{asset}:v]format=rgba,pad=iw+20:ih+20:10:10:color=black@0,scale=1440:2560,perspective={corner}:sense=destination,scale=720:1280[{label}]'

track=json.loads((OUT/'tail-track.json').read_text())
def motion(axis):
    pts=track[::3]
    base=sum(v[axis] for v in track[:6])/6
    vals=[(v[0]/24,v[axis]-base) for v in pts]
    expr=str(round(vals[-1][1]+(8-vals[-1][0])*500 if axis==1 else vals[-1][1],3))
    # Beyond the logo's screen exit the overlay is disabled; final branch is immaterial.
    for (t0,v0),(t1,v1) in reversed(list(zip(vals,vals[1:]))):
        segment=f'({v0:.3f}+(t-{t0:.4f})*{(v1-v0)/(t1-t0):.3f})'
        expr=f'if(lt(t,{t1:.4f}),{segment},{expr})'
    return expr

filt=plane(1,(421,458,619,458,421,511,619,510),'board')+';'+plane(2,(318,797,557,800,336,851,558,849),'decal')+f";[0:v][board]overlay=x='0.25*t':y='-0.7*t':format=auto[b];[b][decal]overlay=x='{motion(1)}':y='{motion(2)}':enable='lt(t,4.45)':format=auto[v]"
run('-i','turn2.mp4','-loop',1,'-i','banner.png','-loop',1,'-i',LOGO,'-filter_complex',filt,'-map','[v]','-t',8,'-an','-c:v','libx264','-crf',18,'-preset','fast','-pix_fmt','yuv420p','07-departure-final.mp4')
run('-ss',7.8,'-i','07-departure-final.mp4','-frames:v',1,'-update',1,'end-source.png')
# Endcard preserves the PNG alpha. A soft contour glow improves contrast without a plate.
end=r"[0:v]gblur=sigma=22,eq=brightness=-0.17:saturation=0.6[bg];[1:v]scale=610:-1,format=rgba,split=2[logo][glow];[glow]lutrgb=r=255:g=255:b=255,gblur=sigma=5[halo];[bg][halo]overlay=x=55:y=505[tmp];[tmp][logo]overlay=x=55:y=505,drawtext=fontfile='C\:/Windows/Fonts/arial.ttf':textfile=slogan-1.txt:fontsize=40:fontcolor=white:shadowcolor=black@0.4:shadowx=1:shadowy=2:x=(w-tw)/2:y=735,drawtext=fontfile='C\:/Windows/Fonts/arial.ttf':textfile=slogan-2.txt:fontsize=38:fontcolor=white:shadowcolor=black@0.4:shadowx=1:shadowy=2:x=(w-tw)/2:y=792,fade=t=in:st=0:d=0.35[v]"
run('-loop',1,'-framerate',24,'-i','end-source.png','-i',LOGO,'-filter_complex',end,'-map','[v]','-t',5,'-an','-c:v','libx264','-crf',18,'-preset','fast','-pix_fmt','yuv420p','09-endcard-final.mp4')
parts=['01-aerial','02-stop','03-rider','04-listener','05-driver','06-green','07-departure-final','09-endcard-final']
(OUT/'final-concat.txt').write_text(''.join(f"file '{p}.mp4'\n" for p in parts),encoding='utf-8')
run('-f','concat','-safe',0,'-i','final-concat.txt','-c','copy','picture-final.mp4')
# Reuse the reviewed sound track unchanged. No generated Veo audio enters the final file.
run('-i','picture-final.mp4','-i','LogistRu-9x16-v2-review.mp4','-map','0:v','-map','1:a','-t',28,'-c','copy','-movflags','+faststart','LogistRu-9x16-v2.mp4')
run('-i','07-departure-final.mp4','-vf','fps=2,scale=360:640,tile=4x4','-frames:v',1,'-update',1,'departure-final-contact.png')
run('-ss',25,'-i','LogistRu-9x16-v2.mp4','-frames:v',1,'-update',1,'final-endcard.png')
run('-i','LogistRu-9x16-v2.mp4','-f','null','-')
print('Final MP4 rendered and decoded successfully.')
