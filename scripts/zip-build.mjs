import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { ZipArchive } from 'archiver';

const [sourceDir = 'dist', targetFile = 'archive/vityaz.zip', archiveRoot = ''] =
  process.argv.slice(2);
const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const sourcePath = path.resolve(projectRoot, sourceDir);
const targetPath = path.resolve(projectRoot, targetFile);

await fs.access(sourcePath);
await fs.mkdir(path.dirname(targetPath), { recursive: true });

const output = await fs.open(targetPath, 'w');
const stream = output.createWriteStream();
const archive = new ZipArchive({ zlib: { level: 9 } });

await new Promise((resolve, reject) => {
  stream.on('close', resolve);
  archive.on('error', reject);
  archive.pipe(stream);
  archive.directory(sourcePath, archiveRoot || false);
  archive.finalize();
});

await output.close();
