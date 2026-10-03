from pathlib import Path
import sys, math, subprocess, json
ROOT = Path(__file__).resolve().parent
sys.path.insert(0, str(ROOT / '.deps'))
from PIL import Image, ImageDraw, ImageFont
import imageio_ffmpeg

W,H,FPS,SECONDS = 1920,1080,30,15
BG = Image.open(ROOT.parent / 'imagegen/logistru-yandex-business-cover-landscape-v3.png').convert('RGB')
LOGO = Image.open(ROOT / 'logo-original.png').convert('RGBA')
LOGO.thumbnail((550,160),Image.Resampling.LANCZOS)
font = ImageFont.truetype('C:/Windows/Fonts/seguisb.ttf',49)
small = ImageFont.truetype('C:/Windows/Fonts/segoeui.ttf',28)
def ease(t):
    t=max(0,min(1,t)); return t*t*(3-2*t)
def overlay(im,layer,a):
    if a <= 0: return im
    if a < 1: layer.putalpha(layer.getchannel('A').point(lambda v:round(v*a)))
    return Image.alpha_composite(im,layer)
def frame(t):
    im=Image.new('RGBA',(W,H),'#f8fafc')
    # Subtle camera push across a static photographic composition.
    z=1+0.045*ease(t/15)
    bw=BG.width/z; bh=BG.height/z
    cx=BG.width*(0.5+0.006*ease(t/15)); cy=BG.height*.5
    photo=BG.resize((W,640),Image.Resampling.BICUBIC,box=(cx-bw/2,cy-bh/2,cx+bw/2,cy+bh/2))
    im.paste(photo,(0,0))
    d=ImageDraw.Draw(im)
    d.rectangle((0,640,W,644),fill='#1d4ed8')
    a=ease((t-.45)/1.1)
    layer=Image.new('RGBA',(W,H)); layer.paste(LOGO,(100,746+round(15*(1-a))))
    im=overlay(im,layer,a)
    a=ease((t-2.0)/1.15)
    layer=Image.new('RGBA',(W,H)); d=ImageDraw.Draw(layer)
    d.line((748,744,748,922),fill='#dce3ec',width=2)
    y=745+round(18*(1-a))
    d.text((824,y),'логистРу - для тех',font=font,fill='#132644')
    d.text((824,y+72),'кто ценит свое время',font=font,fill='#132644')
    d.rounded_rectangle((826,y+164,906,y+169),radius=2,fill='#16a34a')
    im=overlay(im,layer,a)
    layer=Image.new('RGBA',(W,H)); d=ImageDraw.Draw(layer)
    d.text((106,922),'24logist.ru',font=small,fill='#45556c')
    im=overlay(im,layer,ease((t-3.4)/.9))
    return im.convert('RGB')

out=ROOT/'logistru-promo-15s-1080p.mp4'
ffmpeg=imageio_ffmpeg.get_ffmpeg_exe()
cmd=[ffmpeg,'-y','-f','rawvideo','-vcodec','rawvideo','-pix_fmt','rgb24','-s',f'{W}x{H}','-r',str(FPS),'-i','-','-an','-c:v','libx264','-preset','fast','-crf','19','-pix_fmt','yuv420p','-movflags','+faststart',str(out)]
with (ROOT/'encode.log').open('w') as log:
    p=subprocess.Popen(cmd,stdin=subprocess.PIPE,stdout=log,stderr=log)
    for i in range(FPS*SECONDS):
        f=frame(i/FPS)
        p.stdin.write(f.tobytes())
        if i in [0,90,240,449]: f.save(ROOT/f'preview-{i:03}.jpg',quality=90)
        if i % 90 == 0: print(f'{i}/{FPS*SECONDS}',flush=True)
    p.stdin.close()
    if p.wait()!=0: raise RuntimeError('Encoding failed: see encode.log')
result=subprocess.run([ffmpeg,'-v','error','-i',str(out),'-f','null','-'],capture_output=True,text=True)
if result.returncode: raise RuntimeError(result.stderr)
print(json.dumps({'file':str(out),'duration':SECONDS,'frames':FPS*SECONDS,'width':W,'height':H,'bytes':out.stat().st_size,'decode_check':'passed'}))
