import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

await Promise.all(
  ['app', 'dist', 'backend', 'archive', 'public'].map((dir) =>
    fs.rm(path.join(projectRoot, dir), { recursive: true, force: true }),
  ),
);
