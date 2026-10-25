import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const web = path.join(root, 'apps', 'web');

const files = [];

const walk = (directory) => {
  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    const filePath = path.join(directory, entry.name);

    if (entry.isDirectory()) {
      walk(filePath);
    } else if (/\.(html|css|js)$/.test(entry.name)) {
      files.push(filePath);
    }
  }
};

walk(web);

let failures = 0;

for (const filePath of files) {
  const source = fs.readFileSync(filePath, 'utf8');

  if (/<script\b[^>]*\bsrc\s*=\s*["'](?:https?:)?\/\//i.test(source)) {
    console.error('FAIL external script:', filePath);
    failures++;
  }

  if (/\beval\s*\(/.test(source) || /document\.write\s*\(/.test(source)) {
    console.error('FAIL unsafe API:', filePath);
    failures++;
  }
}

const html = fs.readFileSync(path.join(web, 'index.html'), 'utf8');

for (const match of html.matchAll(/\b(?:src|href)\s*=\s*["']([^"']+)["']/g)) {
  const ref = match[1];

  if (/^(?:https?:|\/\/|#|data:|mailto:|tel:)/i.test(ref)) {
    continue;
  }

  const webTarget = path.resolve(web, ref);
  const publicTarget = path.resolve(root, 'public', ref);

  if (!fs.existsSync(webTarget) && !fs.existsSync(publicTarget)) {
    console.error('FAIL missing asset:', ref);
    failures++;
  }
}

if (
  !fs.existsSync(path.join(web, 'data', 'ak-data.js')) ||
  !fs.existsSync(path.join(web, 'data', 'ak-assets.js'))
) {
  console.error('FAIL data modules missing');
  failures++;
}

console.log(`Lint checked ${files.length} web source files.`);

if (failures > 0) {
  process.exit(1);
}
