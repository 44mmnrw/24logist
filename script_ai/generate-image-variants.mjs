import path from "node:path";
import { mkdir, stat } from "node:fs/promises";
import sharp from "sharp";

function parsePositiveIntegers(value) {
    return [...new Set(
        String(value ?? "")
            .split(",")
            .map((item) => Number.parseInt(item, 10))
            .filter((item) => Number.isInteger(item) && item > 0),
    )].sort((left, right) => left - right);
}

async function isFresh(target, sourceMtimeMs) {
    try {
        return (await stat(target)).mtimeMs >= sourceMtimeMs;
    } catch {
        return false;
    }
}

const [
    sourcePath,
    widthsArgument = "640,1280",
    webpQualityArgument = "82",
    avifQualityArgument = "62",
    forceArgument = "0",
] = process.argv.slice(2);

if (!sourcePath) {
    throw new Error("Source image path is required.");
}

const sourceStats = await stat(sourcePath);
const metadata = await sharp(sourcePath, {
    limitInputPixels: 40_000_000,
    failOn: "error",
}).metadata();

if (!metadata.width || !metadata.height) {
    throw new Error("Unable to determine source image dimensions.");
}

const requestedWidths = parsePositiveIntegers(widthsArgument);
const maximumRequestedWidth = Math.max(...requestedWidths, metadata.width);
const targetWidths = requestedWidths.filter((width) => width <= metadata.width);

if (
    targetWidths.length === 0
    || (metadata.width < maximumRequestedWidth && !targetWidths.includes(metadata.width))
) {
    targetWidths.push(metadata.width);
}

const widths = [...new Set(targetWidths)].sort((left, right) => left - right);
const extension = path.extname(sourcePath);
const basePath = sourcePath.slice(0, -extension.length);
const webpQuality = Math.max(1, Math.min(100, Number.parseInt(webpQualityArgument, 10) || 82));
const avifQuality = Math.max(1, Math.min(100, Number.parseInt(avifQualityArgument, 10) || 62));
const force = forceArgument === "1";
const generated = [];
const skipped = [];

await mkdir(path.dirname(sourcePath), { recursive: true });

for (const width of widths) {
    const targets = [
        {
            format: "webp",
            path: `${basePath}--${width}w.webp`,
        },
        {
            format: "avif",
            path: `${basePath}--${width}w.avif`,
        },
    ];

    const pendingTargets = [];

    for (const target of targets) {
        if (!force && await isFresh(target.path, sourceStats.mtimeMs)) {
            skipped.push(target.path);
        } else {
            pendingTargets.push(target);
        }
    }

    if (pendingTargets.length === 0) {
        continue;
    }

    const resized = sharp(sourcePath, {
        limitInputPixels: 40_000_000,
        failOn: "error",
    })
        .rotate()
        .resize({
            width,
            withoutEnlargement: true,
            fit: "inside",
        });

    await Promise.all(pendingTargets.map(async (target) => {
        if (target.format === "webp") {
            await resized
                .clone()
                .webp({
                    quality: webpQuality,
                    effort: 4,
                    smartSubsample: true,
                })
                .toFile(target.path);
        } else {
            await resized
                .clone()
                .avif({
                    quality: avifQuality,
                    effort: 4,
                    chromaSubsampling: "4:2:0",
                })
                .toFile(target.path);
        }

        generated.push(target.path);
    }));
}

process.stdout.write(JSON.stringify({
    source: sourcePath,
    sourceWidth: metadata.width,
    sourceHeight: metadata.height,
    widths,
    generated,
    skipped,
}));
