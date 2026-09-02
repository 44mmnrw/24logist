import fs from 'node:fs/promises';
import path from 'node:path';
import sharp from 'sharp';

const sourceRoot = 'C:/Users/rozov/.codex/generated_images';
const outputRoot = 'artifacts/reference-review';
const allowed = new Set(['.png', '.jpg', '.jpeg', '.webp', '.gif', '.bmp', '.tif', '.tiff']);

async function walk(dir) {
  const entries = await fs.readdir(dir, { withFileTypes: true });
  const nested = await Promise.all(entries.map(async (entry) => {
    const full = path.join(dir, entry.name);
    return entry.isDirectory() ? walk(full) : [full];
  }));
  return nested.flat();
}

await fs.mkdir(outputRoot, { recursive: true });
const files = (await walk(sourceRoot))
  .filter((file) => allowed.has(path.extname(file).toLowerCase()))
  .sort();

const records = [];
for (let i = 0; i < files.length; i += 1) {
  const metadata = await sharp(files[i]).metadata();
  records.push({
    index: i + 1,
    path: files[i],
    width: metadata.width,
    height: metadata.height,
    format: metadata.format,
  });
}

await fs.writeFile(
  path.join(outputRoot, 'manifest.json'),
  JSON.stringify(records, null, 2),
  'utf8',
);

const columns = 4;
const rows = 4;
const tileWidth = 430;
const tileHeight = 475;
const imageBox = 400;
const gap = 20;
const sheetWidth = columns * tileWidth + gap;
const sheetHeight = rows * tileHeight + gap;
const perSheet = columns * rows;

for (let sheetIndex = 0; sheetIndex < Math.ceil(records.length / perSheet); sheetIndex += 1) {
  const canvas = sharp({
    create: {
      width: sheetWidth,
      height: sheetHeight,
      channels: 4,
      background: '#E7ECF3',
    },
  });
  const composites = [];
  const batch = records.slice(sheetIndex * perSheet, (sheetIndex + 1) * perSheet);

  for (let localIndex = 0; localIndex < batch.length; localIndex += 1) {
    const record = batch[localIndex];
    const column = localIndex % columns;
    const row = Math.floor(localIndex / columns);
    const left = gap + column * tileWidth;
    const top = gap + row * tileHeight;
    const thumb = await sharp(record.path)
      .resize(imageBox, imageBox, { fit: 'contain', background: '#FFFFFF' })
      .png()
      .toBuffer();
    const label = Buffer.from(
      `<svg width="400" height="45" xmlns="http://www.w3.org/2000/svg">
        <rect width="400" height="45" fill="#FFFFFF"/>
        <text x="12" y="30" font-family="Arial" font-size="24" font-weight="700" fill="#0D2147">${String(record.index).padStart(2, '0')} · ${record.width}×${record.height}</text>
      </svg>`,
    );
    composites.push({ input: thumb, left, top });
    composites.push({ input: label, left, top: top + imageBox });
  }

  await canvas
    .composite(composites)
    .png({ compressionLevel: 9 })
    .toFile(path.join(outputRoot, `contact-sheet-${sheetIndex + 1}.png`));
}
