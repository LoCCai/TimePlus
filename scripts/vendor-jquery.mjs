import { copyFile, readFile } from 'node:fs/promises';
import { resolve } from 'node:path';

const source = resolve('node_modules/jquery/dist/jquery.min.js');
const destination = resolve('assets/js/jquery.min.js');
const content = await readFile(source, 'utf8');

if (!content.includes('jQuery v3.7.1')) {
  throw new Error('Expected the pinned jQuery 3.7.1 distribution');
}

await copyFile(source, destination);
