"""Finish the native-logo take after visual review. Never overlays a car decal."""
import ast, pathlib, subprocess, sys
OUT = pathlib.Path(__file__).resolve().parent
sys.path.insert(0, str(OUT.parent / 'veo-logistru/deps'))
import imageio_ffmpeg
FF = imageio_ffmpeg.get_ffmpeg_exe()
ENC = ['-an', '-c:v', 'libx264', '-crf', '18', '-preset', 'fast', '-pix_fmt', 'yuv420p']
def run(*args):
    subprocess.run([FF, '-hide_banner', '-loglevel', 'error', '-y', *map(str,args)], cwd=OUT, check=True)

if not (OUT / 'turn5.mp4').exists():
    raise SystemExit('No generated take yet. Submit, download and visually review turn5 first.')
if '--reviewed' not in sys.argv:
    raise SystemExit('Run with --reviewed only after inspecting the native take and full vehicle exit.')

# The only post-generated scene graphic is the stationary service billboard.
board = ('[1:v]format=rgba,pad=iw+20:ih+20:10:10:color=black@0,scale=1440:2560,'
         'perspective=x0=870:y0=810:x1=1300:y1=810:x2=870:y2=922:x3=1300:y3=920:sense=destination,'
         'scale=720:1280[b];[0:v]scale=720:1280[bg];[bg][b]overlay=0:0:format=auto,format=yuv420p[v]')
run('-i', 'turn5.mp4', '-loop', 1, '-framerate', 24, '-i', 'banner.png',
    '-filter_complex', board, '-map', '[v]', '-t', 8, *ENC, 'turn5-reviewed.mp4')
run('-i', 'turn5.png', '-i', 'banner.png', '-filter_complex', board, '-map', '[v]',
    '-frames:v', 1, '-update', 1, 'v5-stop-branded.png')
for name,duration,z0,z1 in [('v5-stop.mp4',3.5,1,1.07),('v5-listener.mp4',1.5,1.07,1.105)]:
    frames=round(duration*24)
    vf=(f"scale=2160:3840,zoompan=z='{z0}+{z1-z0}*on/{frames-1}':"
        f"x='(iw-iw/zoom)*0.58':y='(ih-ih/zoom)*0.65':d={frames}:s=720x1280:fps=24,"
        'eq=contrast=1.018:saturation=0.98')
    run('-i','v5-stop-branded.png','-vf',vf,'-t',duration,*ENC,name)

# Reuse reviewed timing, Russian audio and transparent endcard from v4.
source=(OUT/'assemble_v4.py').read_text(encoding='utf-8')
source=source.replace('turn4','turn5').replace('v4','v5').replace('V4','V5')
source=source.replace("[f'local-{i:02d}.mp4' for i in range(1, 6)]",
                      "['local-01.mp4', 'v5-stop.mp4', 'local-03.mp4', 'v5-listener.mp4', 'local-05.mp4']")
exec(compile(source,str(OUT/'assemble_v5.generated.py'),'exec'),{'__file__':str(OUT/'assemble_v5.generated.py'),'__name__':'__main__'})
