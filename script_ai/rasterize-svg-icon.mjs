import { readFileSync, writeFileSync } from 'node:fs';

const svgPath = process.argv[2];
const outputPath = process.argv[3];
const size = Number.parseInt(process.argv[4] ?? '180', 10);

if (!svgPath || !outputPath || !Number.isFinite(size) || size < 1) {
    console.error('Usage: node script_ai/rasterize-svg-icon.mjs <svg-path> <png-path> <size>');
    process.exit(1);
}

const sharp = await import('sharp').then((m) => m.default).catch(() => null);

if (!sharp) {
    console.error('sharp is not installed. Run: npm install sharp --save-dev');
    process.exit(1);
}

const svg = readFileSync(svgPath);

await sharp(svg, { density: 300 })
    .resize(size, size, {
        fit: 'contain',
        background: { r: 255, g: 255, b: 255, alpha: 0 },
    })
    .png()
    .toFile(outputPath);

console.log(`Saved ${outputPath} (${size}x${size})`);
