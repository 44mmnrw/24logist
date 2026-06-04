import { existsSync } from 'node:fs';
import { pathToFileURL } from 'node:url';

const htmlPath = process.argv[2];
const outputPath = process.argv[3];

if (!htmlPath || !outputPath) {
    console.error('Usage: node script_ai/generate-og-hero-png.mjs <html-path> <png-path>');
    process.exit(1);
}

const puppeteer = await import('puppeteer').then((m) => m.default).catch(() => null);

if (!puppeteer) {
    console.error('puppeteer is not installed. Run: npm install puppeteer --save-dev');
    process.exit(1);
}

function resolveExecutablePath() {
    if (process.env.PUPPETEER_EXECUTABLE_PATH && existsSync(process.env.PUPPETEER_EXECUTABLE_PATH)) {
        return process.env.PUPPETEER_EXECUTABLE_PATH;
    }

    const candidates = [
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
        'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    ];

    return candidates.find((path) => existsSync(path)) ?? null;
}

const fileUrl = pathToFileURL(htmlPath).href;
const executablePath = resolveExecutablePath();
const launchOptions = { headless: true };

if (executablePath) {
    launchOptions.executablePath = executablePath;
}

const browser = await puppeteer.launch(launchOptions).catch((error) => {
    console.error(error.message);
    console.error('Install Chrome/Edge or run: npx puppeteer browsers install chrome');
    process.exit(1);
});
const page = await browser.newPage();
await page.setViewport({ width: 1200, height: 630, deviceScaleFactor: 1 });
await page.goto(fileUrl, { waitUntil: 'load', timeout: 30000 });
await page.screenshot({ path: outputPath, type: 'png' });
await browser.close();

console.log(`Saved ${outputPath}`);
