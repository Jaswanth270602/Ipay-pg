import fs from 'fs';
import path from 'path';

const viewsDir = path.join(process.cwd(), 'resources', 'views');

function walk(dir) {
  for (const name of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, name.name);
    if (name.isDirectory()) walk(p);
    else if (name.name.endsWith('.blade.php')) processFile(p);
  }
}

function processFile(fp) {
  const text = fs.readFileSync(fp, 'utf8');
  if (!text.includes('ng-app="ipayApp"')) return;
  let changed = false;
  const eol = text.includes('\r\n') ? '\r\n' : '\n';
  const lines = text.split(/\r?\n/);
  const out = lines.map((line) => {
    if (line.includes('ng-app="ipayApp"') && !line.includes('ng-cloak')) {
      if (/<div\s+/.test(line)) {
        const nl = line.replace(/<div\s+/, '<div ng-cloak ');
        if (nl !== line) {
          changed = true;
          return nl;
        }
      }
    }
    return line;
  });
  if (changed) {
    fs.writeFileSync(fp, out.join(eol), 'utf8');
    console.log('updated:', fp);
  }
}

walk(viewsDir);
