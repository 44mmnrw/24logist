"""Assemble the reviewed, natively generated replacement driving shot."""
import ast, pathlib, subprocess, sys

OUT = pathlib.Path(__file__).resolve().parent
sys.path.insert(0, str(OUT.parent / 'veo-logistru/deps'))
import imageio_ffmpeg
FF = imageio_ffmpeg.get_ffmpeg_exe()
LOGO = pathlib.Path('d:/Documents/www/logist/Лого/logo_platform.png')
ENC = ['-an', '-c:v', 'libx264', '-crf', '18', '-preset', 'fast', '-pix_fmt', 'yuv420p']

def run(*args):
    subprocess.run([FF, '-hide_banner', '-loglevel', 'error', '-y', *map(str, args)], cwd=OUT, check=True)

if not (OUT / 'turn4-reviewed.mp4').exists():
    raise SystemExit('Review and finish the new driving shot first.')

# A closer view of the rider follows the car turning out of his lane.
run('-ss', 7.95, '-i', 'turn4-reviewed.mp4', '-frames:v', 1, '-update', 1, 'v4-last.png')
run('-i', 'v4-last.png', '-vf',
    "crop=405:720:0:330,scale=2160:3840,zoompan=z='1+0.03*on/47':x='(iw-iw/zoom)*0.45':y='(ih-ih/zoom)*0.4':d=48:s=720x1280:fps=24",
    '-t', 2, *ENC, 'v4-rider-ending.mp4')
run('-ss', 1.95, '-i', 'v4-rider-ending.mp4', '-frames:v', 1, '-update', 1, 'v4-end-source.png')

# Reuse the already reviewed transparent endcard styling, over the new shot.
tree = ast.parse((OUT / 'local_edit.py').read_text(encoding='utf-8'))
end = next(ast.literal_eval(n.value) for n in tree.body
           if isinstance(n, ast.Assign) and any(isinstance(t, ast.Name) and t.id == 'end' for t in n.targets))
run('-i', 'v4-end-source.png', '-i', LOGO, '-filter_complex', end,
    '-map', '[v]', '-t', 5, *ENC, 'v4-endcard.mp4')
parts = [f'local-{i:02d}.mp4' for i in range(1, 6)] + ['turn4-reviewed.mp4', 'v4-rider-ending.mp4', 'v4-endcard.mp4']
(OUT / 'v4-concat.txt').write_text(''.join(f"file '{p}'\n" for p in parts), encoding='utf-8')
run('-f', 'concat', '-safe', 0, '-i', 'v4-concat.txt', '-c', 'copy', 'v4-picture.mp4')

# Only the two approved Russian recordings and locally created non-vocal ambience.
audio = ('[1:a]adelay=6500|6500,apad[a1];[2:a]adelay=11000|11000,apad[a2];'
         '[3:a]apad,afade=t=out:st=24:d=5[fx];'
         '[a1][a2][fx]amix=inputs=3:normalize=0,alimiter=limit=0.95[a]')
run('-i', 'v4-picture.mp4', '-i', 'voice-1.wav', '-i', 'voice-2.wav', '-i', 'local-foley.wav',
    '-filter_complex', audio, '-map', '0:v', '-map', '[a]', '-t', 29,
    '-c:v', 'copy', '-c:a', 'aac', '-b:a', '192k', '-movflags', '+faststart', 'LogistRu-9x16-v4.mp4')
run('-i', 'LogistRu-9x16-v4.mp4', '-vf', 'fps=1/2,scale=270:480,tile=5x3', '-frames:v', 1, '-update', 1, 'v4-contact.png')
run('-i', 'LogistRu-9x16-v4.mp4', '-f', 'null', '-')
print('V4 complete: 29 seconds; native driving shot; isolated Russian speech.')
