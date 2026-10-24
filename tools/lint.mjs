import fs from 'node:fs';
import path from 'node:path';
const root=process.cwd();
const web=path.join(root,'apps','web');
const files=[]; const walk=d=>{for(const e of fs.readdirSync(d,{withFileTypes:true})){const p=path.join(d,e.name);if(e.isDirectory())walk(p);else if(/\.(html|css|js)$/.test(e.name))files.push(p)}};walk(web);
let failures=0;
for(const p of files){const s=fs.readFileSync(p,'utf8');if(/<script[^>]+src=["'](?:https?:)?\/\//i.test(s)) {console.error('FAIL external script:',p);failures++;} if(/\beval\s*\(/.test(s)||/document\.write\s*\(/.test(s)){console.error('FAIL unsafe API:',p);failures++;}}
const html=fs.readFileSync(path.join(web,'index.html'),'utf8');
for(const m of html.matchAll(/(?:src|href)=["']([^"']+)["']/g)){const ref=m[1];if(/^(?:https?:|#|data:|mailto:|tel:)/i.test(ref))continue;const target=path.resolve(web,ref);if(!fs.existsSync(target)){console.error('FAIL missing asset:',ref);failures++;}}
if(!fs.existsSync(path.join(web,'data','ak-data.js'))||!fs.existsSync(path.join(web,'data','ak-assets.js'))){console.error('FAIL data modules missing');failures++;}
console.log(`Lint checked ${files.length} web source files.`);if(failures)process.exit(1);
