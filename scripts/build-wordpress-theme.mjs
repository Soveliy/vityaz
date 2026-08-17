import fs from 'node:fs/promises';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { fileURLToPath } from 'node:url';
import prettier from 'prettier';
import { MAP_LOCATIONS } from '../src/js/components/map-locations.js';

const scriptDir = path.dirname(fileURLToPath(import.meta.url));
const projectRoot = path.resolve(scriptDir, '..');
const distDir = path.join(projectRoot, 'dist');
const themeAssetsDir = path.join(projectRoot, 'wordpress-theme', 'vityaz', 'assets');
const themeDataDir = path.join(projectRoot, 'wordpress-theme', 'vityaz', 'data');
const assetFolders = ['css', 'img', 'js', 'resources'];
const starterCaPath = path.join(themeDataDir, 'certificates', 'vityazi-kursk-ca-chain.pem');
const starterCaSha256 = 'd0d0e65409c5d583357ec05a6b70cab858334437de8b2fb12388f35dd0fbbc51';

await fs.rm(themeAssetsDir, { force: true, recursive: true });
await fs.mkdir(themeAssetsDir, { recursive: true });
await fs.mkdir(themeDataDir, { recursive: true });

const starterCa = await fs.readFile(starterCaPath);
const starterCaDigest = createHash('sha256')
  .update(starterCa.toString('utf8').replace(/\r\n?/g, '\n'))
  .digest('hex');

if (starterCaDigest !== starterCaSha256) {
  throw new Error('Starter-content TLS certificate bundle is missing or has changed.');
}

const locationsJson = await prettier.format(JSON.stringify(MAP_LOCATIONS), { parser: 'json' });
await fs.writeFile(path.join(themeDataDir, 'map-locations.json'), locationsJson, 'utf8');

for (const folder of assetFolders) {
  await fs.cp(path.join(distDir, folder), path.join(themeAssetsDir, folder), {
    force: true,
    recursive: true,
  });
}

console.log(`WordPress assets copied to ${path.relative(projectRoot, themeAssetsDir)}`);
