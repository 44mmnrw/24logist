import sharp from 'sharp';

await sharp('artifacts/logistru-registry-software-1080x1080.svg', { density: 144 })
  .resize(1280, 1280)
  .png({ compressionLevel: 9, palette: false })
  .toFile('artifacts/logistru-registry-software-telegram-1280x1280.png');

await sharp('artifacts/logistru-registry-software-telegram-1280x1280.png')
  .resize(270, 270)
  .png({ compressionLevel: 9 })
  .toFile('artifacts/logistru-registry-software-thumbnail.png');

await sharp('artifacts/logistru-registry-software-telegram-1280x1280.png')
  .png({ compressionLevel: 9, palette: false })
  .toFile('artifacts/logistru-registry-software-max-1280x1280.png');
