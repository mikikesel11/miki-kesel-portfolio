// Renders resources/og/favicon.svg to public/ icons using Instrument Sans.
// Regenerate with: node scripts/favicon.mjs (or npm run favicon:build)
import { readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';
import { Resvg } from '@resvg/resvg-js';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const svg = readFileSync(resolve(root, 'resources/og/favicon.svg'));
const fontFiles = ['IS-Regular', 'IS-Medium', 'IS-Bold'].map((f) =>
    resolve(root, `resources/og/fonts/${f}.ttf`)
);

function renderPng(size) {
    return new Resvg(svg, {
        fitTo: { mode: 'width', value: size },
        font: { fontFiles, loadSystemFonts: false, defaultFontFamily: 'Instrument Sans' },
    })
        .render()
        .asPng();
}

// Wrap a PNG in an ICO container (modern browsers read PNG-compressed ICOs).
function pngToIco(png, size) {
    const header = Buffer.alloc(6);
    header.writeUInt16LE(0, 0); // reserved
    header.writeUInt16LE(1, 2); // type: icon
    header.writeUInt16LE(1, 4); // image count

    const entry = Buffer.alloc(16);
    entry.writeUInt8(size >= 256 ? 0 : size, 0); // width
    entry.writeUInt8(size >= 256 ? 0 : size, 1); // height
    entry.writeUInt8(0, 2); // palette
    entry.writeUInt8(0, 3); // reserved
    entry.writeUInt16LE(1, 4); // color planes
    entry.writeUInt16LE(32, 6); // bits per pixel
    entry.writeUInt32LE(png.length, 8); // image size
    entry.writeUInt32LE(22, 12); // offset (6 + 16)

    return Buffer.concat([header, entry, png]);
}

const png32 = renderPng(32);
writeFileSync(resolve(root, 'public/favicon-32.png'), png32);
writeFileSync(resolve(root, 'public/apple-touch-icon.png'), renderPng(180));
writeFileSync(resolve(root, 'public/favicon.ico'), pngToIco(png32, 32));

console.log('Wrote public/favicon.ico, public/favicon-32.png, public/apple-touch-icon.png');
