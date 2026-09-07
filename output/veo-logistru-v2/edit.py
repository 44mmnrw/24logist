import json, pathlib, subprocess, sys

OUT=pathlib.Path(__file__).resolve().parent
sys.path.insert(0,str(OUT.parent/'veo-logistru/deps'))
import imageio_ffmpeg
FF=imageio_ffmpeg.get_ffmpeg_exe()

def run(*args):
    subprocess.run([FF,'-hide_banner','-loglevel','error','-y',*map(str,args)],cwd=OUT,check=True)

if sys.argv[1]=='inspect':
    for name in sys.argv[2:]:
        run('-i',name+'.mp4','-vf','fps=1,scale=270:480,tile=4x2','-frames:v',1,'-update',1,name+'-contact.png')
        run('-i',name+'.mp4','-vf','fps=2','-update',0,name+'-%02d.png')
elif sys.argv[1]=='assets':
    for name,text in [('banner-1','Техподдержка'),('banner-2','Инструкции'),('banner-3','Роуминг'),('slogan-1','Сохраняйте свой комфорт'),('slogan-2','при любых условиях.')]:
        (OUT/(name+'.txt')).write_text(text,encoding='utf-8')
    filt=r"drawtext=fontfile='C\:/Windows/Fonts/arialbd.ttf':textfile=banner-1.txt:fontsize=85:fontcolor=0x102346:x=(w-tw)/2:y=25,drawtext=fontfile='C\:/Windows/Fonts/arial.ttf':textfile=banner-2.txt:fontsize=64:fontcolor=0x1060e8:x=95:y=146,drawtext=fontfile='C\:/Windows/Fonts/arial.ttf':textfile=banner-3.txt:fontsize=64:fontcolor=0x1060e8:x=615:y=146"
    run('-f','lavfi','-i','color=c=white:s=1000x240','-vf',filt,'-frames:v',1,'-update',1,'banner.png')
elif sys.argv[1]=='board-test':
    filt='[1:v]format=rgba,pad=iw+20:ih+20:10:10:color=black@0,scale=720:1280,perspective=x0=512:y0=87:x1=685:y1=86:x2=512:y2=134:x3=679:y3=133:sense=destination[sign];[0:v][sign]overlay=0:0:format=auto[v]'
    run('-i','overhead-01.png','-i','banner.png','-filter_complex',filt,'-map','[v]','-frames:v',1,'-update',1,'board-test.png')
