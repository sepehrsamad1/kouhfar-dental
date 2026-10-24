import fs from 'node:fs'; import path from 'node:path';
const root=process.cwd(); const html=fs.readFileSync(path.join(root,'apps/web/index.html'),'utf8');
const required=['data/ak-assets.js','data/ak-data.js','js/sanitizer.js','js/03-inline.js'];
for(const r of required) if(!html.includes(r)) throw new Error(`Missing script ${r}`);
const forbidden=fs.readdirSync(path.join(root,'apps/web','js')).filter(f=>f==='02-inline.js'); if(forbidden.length) throw new Error('Old data script still present');
console.log('Task 05 smoke test: PASS');
