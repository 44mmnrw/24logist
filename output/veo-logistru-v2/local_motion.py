"""Local-only video compositing. No API calls or network dependencies."""
import pathlib,sys,subprocess,math,json
OUT=pathlib.Path(__file__).resolve().parent
sys.path.insert(0,str(OUT.parent/'veo-logistru/deps'))
import imageio_ffmpeg
FF=imageio_ffmpeg.get_ffmpeg_exe()
def run(*args):
    subprocess.run([FF,'-hide_banner','-loglevel','error','-y',*map(str,args)],cwd=OUT,check=True)

# Corresponding outline vertices on source frames, excluding its environment and rider.
A=[(292,755),(307,737),(306,724),(314,721),(327,722),(347,696),(374,678),(425,673),(493,675),(550,682),(596,702),(643,742),(677,767),(691,807),(696,853),(690,886),(674,907),(422,923),(399,929),(381,919),(370,899),(364,872),(314,840),(303,836),(293,820),(288,790)]
B=[(234,741),(253,727),(266,725),(268,714),(280,712),(300,690),(326,675),(375,668),(432,669),(488,675),(532,692),(586,733),(624,758),(633,796),(635,855),(625,873),(608,882),(390,894),(371,901),(354,895),(343,882),(340,861),(266,822),(250,825),(237,813),(232,790)]

def mask(poly):
    data=bytearray(720*1280)
    ymin=max(0,int(min(y for x,y in poly))-2); ymax=min(1279,int(max(y for x,y in poly))+2)
    for y in range(ymin,ymax+1):
        xs=[]
        for (x0,y0),(x1,y1) in zip(poly,poly[1:]+poly[:1]):
            if (y0<=y+.5<y1) or (y1<=y+.5<y0):
                xs.append(x0+(y+.5-y0)*(x1-x0)/(y1-y0))
        xs.sort()
        for a,b in zip(xs[::2],xs[1::2]):
            left=max(0,int(a)); right=min(720,int(b)+1)
            if right>left:data[y*720+left:y*720+right]=bytes([255])*(right-left)
    return data

with (OUT/'forward-mask.gray').open('wb') as f:
    for n in range(36):
        # Source acceleration is slow initially, then stronger toward the end.
        u=n/35; q=u*u*(.7+.3*u)
        poly=[(ax+(bx-ax)*q,ay+(by-ay)*q) for (ax,ay),(bx,by) in zip(A,B)]
        f.write(mask(poly))

run('-loop',1,'-framerate',24,'-i','lone-branded.png','-ss',.25,'-i','turn.mp4','-f','rawvideo','-pixel_format','gray','-video_size','720x1280','-framerate',24,'-i','forward-mask.gray','-filter_complex',"[2:v]gblur=sigma=1.5[mask];[1:v]format=rgba[car];[car][mask]alphamerge,hflip[cut];[0:v][cut]overlay=x=220:y=0:format=auto,format=yuv420p[v]",'-map','[v]','-t',1.5,'-an','-c:v','libx264','-crf',18,'-preset','fast','forward-composite-test.mp4')
run('-i','forward-composite-test.mp4','-vf','fps=4,scale=270:480,tile=4x2','-frames:v',1,'-update',1,'forward-composite-check.png')
