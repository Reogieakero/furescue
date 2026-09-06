/**
 * Copy browser ESM for clsx, tailwind-merge, and CVA into public/ so
 * the HTML import map can load them locally (same pattern as Lucide).
 *
 * Run: npm run build:vendor
 */
import { copyFile, mkdir, stat } from "node:fs/promises";
import { dirname, join, relative } from "node:path";
import { fileURLToPath } from "node:url";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");
const outDir = join(root, "public/assets/js/vendor");

const COPIES = [
  { from: "node_modules/clsx/dist/clsx.mjs", to: "clsx.js" },
  { from: "node_modules/tailwind-merge/dist/bundle-mjs.mjs", to: "tailwind-merge.js" },
  { from: "node_modules/class-variance-authority/dist/index.mjs", to: "cva.js" },
];

await mkdir(outDir, { recursive: true });

for (const item of COPIES) {
  const src = join(root, item.from);
  const dest = join(outDir, item.to);
  try {
    await copyFile(src, dest);
  } catch {
    throw new Error(`build-vendor: missing ${item.from}. Run npm install first.`);
  }
  const { size } = await stat(dest);
  const rel = relative(root, dest).replaceAll("\\", "/");
  console.log(`Vendor: ${item.from} → ${rel} (${(size / 1024).toFixed(1)} KB)`);
}
