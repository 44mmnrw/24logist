import pathlib, subprocess, sys, json
OUT=pathlib.Path(__file__).resolve().parent
sys.path.insert(0,str(OUT.parent/'veo-logistru/deps'))
import imageio_ffmpeg
FF=imageio_ffmpeg.get_ffmpeg_exe()
LOGO=pathlib.Path('d:/Documents/www/logist/Лого/logo_platform.png')
def run(*args):
    subprocess.run([FF,'-hide_banner','-loglevel','error','-y',*map(str,args)],cwd=OUT,check=True)

# Manually reviewed door-plane positions at quarter-second intervals.
keys=[
 (5.00,(441,650,448,646,442,661,448,654)),
 (5.25,(444,650,458,646,445,661,458,656)),
 (5.50,(454,649,475,646,455,662,475,657)),
 (5.75,(460,648,490,645,461,662,490,657)),
 (6.00,(473,647,507,644,474,662,506,658)),
 (6.25,(494,645,535,643,495,660,534,657)),
 (6.50,(509,644,560,642,510,659,559,656)),
 (6.75,(531,643,587,641,532,658,586,655)),
 (7.00,(553,642,618,641,554,658,617,655)),
 (7.25,(569,642,639,641,570,658,638,655)),
 (7.50,(585,642,654,641,586,658,653,655)),
 (7.75,(595,642,667,642,596,658,666,656)),
 (8.00,(601,642,673,643,602,658,672,657)),
]
(OUT/'turn4-door-track.json').write_text(json.dumps(keys,indent=2))
def expr(i):
    value=str(keys[-1][1][i]*2)
    for (t0,p0),(t1,p1) in reversed(list(zip(keys,keys[1:]))):
        seg=f'({p0[i]*2}+(on/24-{t0})*{2*(p1[i]-p0[i])/(t1-t0)})'
        value=f'if(lt(on/24,{t1}),{seg},{value})'
    return f'if(lt(on/24,5),{keys[0][1][i]*2},{value})'
names=['x0','y0','x1','y1','x2','y2','x3','y3']
board=':'.join(f'{k}={v*2}' for k,v in zip(names,(435,405,650,405,435,461,650,460)))
decal=':'.join(f"{k}='{expr(i)}'" for i,k in enumerate(names))
filt=(f'[1:v]format=rgba,pad=iw+20:ih+20:10:10:color=black@0,scale=1440:2560,perspective={board}:sense=destination,scale=720:1280[b];'
      f'[2:v]format=rgba,pad=iw+20:ih+20:10:10:color=black@0,scale=1440:2560,perspective={decal}:sense=destination:eval=frame,scale=720:1280,colorchannelmixer=aa=0.68,fade=t=in:st=5:d=0.3:alpha=1[d];'
      "[0:v][b]overlay=0:0:format=auto[bg];[bg][d]overlay=0:0:enable='gte(t,5)':format=auto,format=yuv420p[v]")
(OUT/'turn4-filter.txt').write_text(filt)
run('-i','turn4.mp4','-loop',1,'-framerate',24,'-i','banner.png','-loop',1,'-framerate',24,'-i',LOGO,
    '-filter_complex_script','turn4-filter.txt','-map','[v]','-t',8,'-an','-c:v','libx264','-crf',18,'-preset','fast','-pix_fmt','yuv420p','turn4-reviewed.mp4')
run('-i','turn4-reviewed.mp4','-vf',"select='gte(n,120)*not(mod(n,6))',crop=420:150:300:580,scale=840:300,tile=3x4",'-frames:v',1,'-update',1,'turn4-logo-check.png')
run('-ss',7,'-i','turn4-reviewed.mp4','-frames:v',1,'-update',1,'turn4-branded-check.png')
print('Replacement shot composited.')
