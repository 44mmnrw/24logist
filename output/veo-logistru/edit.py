import pathlib
import subprocess
import sys

OUT = pathlib.Path(__file__).resolve().parent
sys.path.insert(0, str(OUT / 'deps'))
import imageio_ffmpeg

FFMPEG = imageio_ffmpeg.get_ffmpeg_exe()
def run(*args):
    subprocess.run([FFMPEG, '-hide_banner', '-loglevel', 'error', '-y', *map(str, args)], cwd=OUT, check=True)

if sys.argv[1] == 'inspect':
    for i in [2, 3]:
        run('-i', f'scene-{i}.mp4', '-vf', 'fps=1/2,scale=270:480,tile=4x1', '-frames:v', 1, '-update', 1, f'scene-{i}-contact.png')
    run('-ss', '7.9', '-i', 'scene-3.mp4', '-frames:v', 1, '-update', 1, 'last-frame.png')
elif sys.argv[1] == 'assemble':
    (OUT / 'slogan-1.txt').write_text('Сохраняйте свой комфорт', encoding='utf-8')
    (OUT / 'slogan-2.txt').write_text('при любых условиях.', encoding='utf-8')
    logo = pathlib.Path('d:/Documents/www/logist/Лого/logo_platform.png')
    filters = "[0:v]gblur=sigma=20,eq=brightness=-0.23:saturation=0.65,drawbox=x=40:y=450:w=640:h=194:color=white:t=fill[bg];[1:v]scale=600:-1[logo];[bg][logo]overlay=x=60:y=475,drawtext=fontfile='C\:/Windows/Fonts/arial.ttf':textfile=slogan-1.txt:fontsize=40:fontcolor=white:x=(w-tw)/2:y=710,drawtext=fontfile='C\:/Windows/Fonts/arial.ttf':textfile=slogan-2.txt:fontsize=38:fontcolor=white:x=(w-tw)/2:y=770,fade=t=in:st=0:d=0.4[v]"
    run('-loop', 1, '-framerate', 24, '-i', 'last-frame.png', '-i', logo, '-f', 'lavfi', '-i', 'anullsrc=r=48000:cl=stereo', '-filter_complex', filters, '-map', '[v]', '-map', '2:a', '-t', 4, '-c:v', 'libx264', '-crf', 19, '-preset', 'fast', '-pix_fmt', 'yuv420p', '-c:a', 'aac', 'endcard.mp4')
    (OUT / 'concat.txt').write_text("file 'scene-1.mp4'\nfile 'scene-2.mp4'\nfile 'scene-3.mp4'\nfile 'endcard.mp4'\n", encoding='utf-8')
    run('-f', 'concat', '-safe', 0, '-i', 'concat.txt', '-c:v', 'libx264', '-crf', 19, '-preset', 'fast', '-c:a', 'aac', '-b:a', '160k', '-movflags', '+faststart', 'LogistRu-9x16-draft.mp4')
    run('-ss', 26, '-i', 'LogistRu-9x16-draft.mp4', '-frames:v', 1, '-update', 1, 'endcard-check.png')
    run('-i', 'LogistRu-9x16-draft.mp4', '-f', 'null', '-')
    print('Created and decoded final MP4:', OUT / 'LogistRu-9x16-draft.mp4')
