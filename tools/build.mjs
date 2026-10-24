import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const src=path.join(root,'apps','web');
const out=path.join(root,'dist');
fs.rmSync(out,{recursive:true,force:true});
fs.cpSync(src,out,{recursive:true});
// Remove development placeholders from the web copy.
for (const p of [path.join(out,'README.md'), path.join(out,'.gitkeep')]) if(fs.existsSync(p)) fs.rmSync(p);
console.log(`Built static web output: ${path.relative(root,out)}`);
