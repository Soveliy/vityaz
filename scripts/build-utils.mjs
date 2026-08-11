import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { load } from 'cheerio';
import fastGlob from 'fast-glob';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export const projectRoot = path.resolve(__dirname, '..');
export const srcDir = path.join(projectRoot, 'src');
export const publicDir = path.join(projectRoot, 'public');

const srcImgDir = path.join(srcDir, 'img');
const srcSvgDir = path.join(srcImgDir, 'svg');
const srcResourcesDir = path.join(srcDir, 'resources');
const srcStaticDir = path.join(srcDir, 'static');
const srcVendorScriptsDir = path.join(srcDir, 'js', 'vendor');

async function pathExists(filePath) {
  try {
    await fs.access(filePath);
    return true;
  } catch {
    return false;
  }
}

async function ensureDir(dirPath) {
  await fs.mkdir(dirPath, { recursive: true });
}

async function readFileIfExists(filePath) {
  try {
    return await fs.readFile(filePath);
  } catch (error) {
    if (error.code === 'ENOENT') {
      return null;
    }

    throw error;
  }
}

async function runWithRetry(action, attempts = 5) {
  let lastError;

  for (let index = 0; index < attempts; index += 1) {
    try {
      return await action();
    } catch (error) {
      lastError = error;

      if (!['EBUSY', 'EPERM'].includes(error.code) || index === attempts - 1) {
        throw error;
      }

      await new Promise((resolve) => {
        setTimeout(resolve, 100 * (index + 1));
      });
    }
  }

  throw lastError;
}

async function writeFileIfChanged(filePath, content) {
  const nextContent = Buffer.isBuffer(content) ? content : Buffer.from(content);
  const currentContent = await readFileIfExists(filePath);

  if (currentContent && currentContent.equals(nextContent)) {
    return;
  }

  await ensureDir(path.dirname(filePath));
  await runWithRetry(() => fs.writeFile(filePath, nextContent));
}

async function copyFileIfChanged(source, target) {
  const sourceContent = await fs.readFile(source);
  const currentContent = await readFileIfExists(target);

  if (currentContent && currentContent.equals(sourceContent)) {
    return;
  }

  await ensureDir(path.dirname(target));
  await runWithRetry(() => fs.copyFile(source, target));
}

async function copyFiles(sourceDir, targetDir, patterns = ['**/*', '!**/.gitkeep']) {
  if (!(await pathExists(sourceDir))) {
    return;
  }

  const files = await fastGlob(patterns, {
    cwd: sourceDir,
    dot: true,
    onlyFiles: true,
  });

  await Promise.all(
    files.map(async (file) => {
      const source = path.join(sourceDir, file);
      const target = path.join(targetDir, file);

      await copyFileIfChanged(source, target);
    }),
  );
}

function createSymbolId(filePath) {
  return `icon-${path
    .basename(filePath, '.svg')
    .toLowerCase()
    .replace(/[^a-z0-9_-]+/g, '-')
    .replace(/^-+|-+$/g, '')}`;
}

function getViewBox($, svg) {
  const viewBox = svg.attr('viewBox');

  if (viewBox) {
    return viewBox;
  }

  const width = Number.parseFloat(svg.attr('width') ?? '0');
  const height = Number.parseFloat(svg.attr('height') ?? '0');

  if (width > 0 && height > 0) {
    return `0 0 ${width} ${height}`;
  }

  return '0 0 24 24';
}

export async function generateSvgSprite() {
  await ensureDir(path.join(publicDir, 'img'));

  if (!(await pathExists(srcSvgDir))) {
    await writeFileIfChanged(
      path.join(publicDir, 'img', 'sprite.svg'),
      '<svg xmlns="http://www.w3.org/2000/svg"></svg>\n',
    );
    return;
  }

  const files = await fastGlob('**/*.svg', {
    cwd: srcSvgDir,
    onlyFiles: true,
  });

  const symbols = await Promise.all(
    files.map(async (file) => {
      const source = await fs.readFile(path.join(srcSvgDir, file), 'utf8');
      const $ = load(source, { xmlMode: true });
      const svg = $('svg').first();

      svg.find('[fill]:not([fill="none"])').attr('fill', 'currentColor');
      svg.find('[stroke]:not([stroke="none"])').attr('stroke', 'currentColor');
      svg.find('[style]').removeAttr('style');

      return `<symbol id="${createSymbolId(file)}" viewBox="${getViewBox($, svg)}">${svg.html() ?? ''}</symbol>`;
    }),
  );

  const sprite = `<?xml version="1.0" encoding="utf-8"?>\n<svg xmlns="http://www.w3.org/2000/svg">\n${symbols.join('\n')}\n</svg>\n`;

  await writeFileIfChanged(path.join(publicDir, 'img', 'sprite.svg'), sprite);
}

export async function syncPublic() {
  await ensureDir(publicDir);

  await copyFiles(srcImgDir, path.join(publicDir, 'img'), ['**/*', '!svg/**', '!**/.gitkeep']);

  const markerIconPath = path.join(srcSvgDir, 'mark.svg');

  if (await pathExists(markerIconPath)) {
    await copyFileIfChanged(markerIconPath, path.join(publicDir, 'img', 'mark.svg'));
  }

  await copyFiles(srcResourcesDir, path.join(publicDir, 'resources'));
  await copyFiles(srcStaticDir, publicDir);
  await copyFiles(srcVendorScriptsDir, path.join(publicDir, 'js', 'vendor'));
  await generateSvgSprite();
}
