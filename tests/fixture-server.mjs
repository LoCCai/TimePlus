import { createReadStream, statSync } from 'node:fs';
import { createServer } from 'node:http';
import { extname, resolve, sep } from 'node:path';

const root = resolve('.');
const types = {
  '.css': 'text/css; charset=utf-8',
  '.gif': 'image/gif',
  '.html': 'text/html; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.png': 'image/png',
  '.svg': 'image/svg+xml'
};

createServer((request, response) => {
  const pathname = decodeURIComponent(new URL(request.url, 'http://127.0.0.1').pathname);
  const candidate = resolve(root, '.' + pathname);
  if (candidate !== root && !candidate.startsWith(root + sep)) {
    response.writeHead(403).end('Forbidden');
    return;
  }

  try {
    if (!statSync(candidate).isFile()) throw new Error('Not a file');
    response.writeHead(200, { 'Content-Type': types[extname(candidate)] || 'application/octet-stream' });
    createReadStream(candidate).pipe(response);
  } catch (error) {
    response.writeHead(404).end('Not found');
  }
}).listen(4173, '127.0.0.1');
