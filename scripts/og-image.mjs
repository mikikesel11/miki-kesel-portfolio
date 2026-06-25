// Renders resources/og/card.svg to public/og-image.png at 1200x630 using the
// Instrument Sans font. Regenerate with: node scripts/og-image.mjs
import { readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';
import { Resvg } from '@resvg/resvg-js';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const svg = readFileSync(resolve(root, 'resources/og/card.svg'));

const resvg = new Resvg(svg, {
    fitTo: { mode: 'width', value: 1200 },
    font: {
        // Static weights instanced from the variable font so font-weight
        // (400/500/700) resolves correctly — resvg doesn't instance var axes.
        fontFiles: [
            resolve(root, 'resources/og/fonts/IS-Regular.ttf'),
            resolve(root, 'resources/og/fonts/IS-Medium.ttf'),
            resolve(root, 'resources/og/fonts/IS-Bold.ttf'),
        ],
        loadSystemFonts: false,
        defaultFontFamily: 'Instrument Sans',
    },
});

const png = resvg.render().asPng();
const out = resolve(root, 'public/og-image.png');
writeFileSync(out, png);

console.log(`Wrote ${out} (${png.length} bytes)`);
