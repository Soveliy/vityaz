import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { defineConfig } from 'vite';
import { minify as minifyHtml } from 'html-minifier-terser';

import { projectRoot, srcDir, syncPublic } from './scripts/build-utils.mjs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

function normalizeSlashes(filePath) {
  return filePath.replaceAll(path.sep, '/');
}

async function fileExists(filePath) {
  try {
    await fs.access(filePath);
    return true;
  } catch {
    return false;
  }
}

function resolveIncludePath(includePath, currentDir) {
  if (path.isAbsolute(includePath)) {
    return path.join(srcDir, includePath);
  }

  return path.resolve(currentDir, includePath);
}

async function renderHtmlIncludes(html, currentDir, stack = []) {
  const includePattern = /@include\((['"])(.+?)\1\)/g;
  const matches = [...html.matchAll(includePattern)];

  let rendered = html;

  for (const match of matches) {
    const [fullMatch, , includePath] = match;
    const filePath = resolveIncludePath(includePath, currentDir);

    if (!(await fileExists(filePath))) {
      throw new Error(`HTML include not found: ${includePath}`);
    }

    if (stack.includes(filePath)) {
      const chain = [...stack, filePath]
        .map((item) => normalizeSlashes(path.relative(projectRoot, item)))
        .join(' -> ');
      throw new Error(`Circular HTML include: ${chain}`);
    }

    const fileContent = await fs.readFile(filePath, 'utf8');
    const nestedContent = await renderHtmlIncludes(fileContent, path.dirname(filePath), [
      ...stack,
      filePath,
    ]);

    rendered = rendered.replace(fullMatch, nestedContent);
  }

  return rendered;
}

function htmlIncludesPlugin() {
  const partialsDir = path.join(srcDir, 'partials');

  const isHtmlPartial = (filePath) => {
    const relativePath = path.relative(partialsDir, path.resolve(filePath));

    return (
      relativePath !== '' &&
      !relativePath.startsWith('..') &&
      !path.isAbsolute(relativePath) &&
      path.extname(relativePath) === '.html'
    );
  };

  return {
    name: 'warpoint-html-includes',
    configureServer(server) {
      const reloadPage = (filePath) => {
        if (isHtmlPartial(filePath)) {
          server.ws.send({ type: 'full-reload' });
        }
      };

      server.watcher.add(partialsDir);
      server.watcher.on('add', reloadPage);
      server.watcher.on('change', reloadPage);
      server.watcher.on('unlink', reloadPage);
    },
    transformIndexHtml: {
      order: 'pre',
      async handler(html, context) {
        const filename = context.filename ?? path.join(srcDir, 'index.html');
        return renderHtmlIncludes(html, path.dirname(filename), [filename]);
      },
    },
  };
}

function htmlMinifyPlugin(shouldMinify) {
  return {
    name: 'warpoint-html-minify',
    transformIndexHtml: {
      order: 'post',
      async handler(html) {
        if (!shouldMinify) {
          return html;
        }

        return minifyHtml(html, {
          collapseWhitespace: true,
          minifyCSS: true,
          minifyJS: true,
          removeComments: true,
        });
      },
    },
  };
}

function staticAssetsPlugin() {
  const watchedDirs = [
    path.join(srcDir, 'img'),
    path.join(srcDir, 'resources'),
    path.join(srcDir, 'static'),
    path.join(srcDir, 'js', 'vendor'),
  ];

  const isWatchedFile = (filePath) => {
    const absolutePath = path.resolve(filePath);
    return watchedDirs.some((dir) => absolutePath.startsWith(dir));
  };

  return {
    name: 'warpoint-static-assets',
    async buildStart() {
      await syncPublic();
    },
    configureServer(server) {
      const syncAndReload = async (filePath) => {
        if (!isWatchedFile(filePath)) {
          return;
        }

        await syncPublic();
        server.ws.send({ type: 'full-reload' });
      };

      server.watcher.add(watchedDirs);
      server.watcher.on('add', syncAndReload);
      server.watcher.on('change', syncAndReload);
      server.watcher.on('unlink', syncAndReload);
    },
  };
}

export default defineConfig(({ mode }) => {
  const isBackend = mode === 'backend';
  const shouldMinify = mode === 'production';

  return {
    root: 'src',
    envDir: projectRoot,
    base: './',
    publicDir: '../public',
    server: {
      host: '0.0.0.0',
      open: false,
      port: 3000,
      strictPort: false,
    },
    css: {
      devSourcemap: true,
    },
    build: {
      assetsDir: 'assets',
      emptyOutDir: true,
      minify: isBackend ? false : 'esbuild',
      outDir: isBackend ? '../backend' : '../dist',
      sourcemap: isBackend,
      rollupOptions: {
        input: {
          index: path.resolve(__dirname, 'src/index.html'),
        },
        output: {
          assetFileNames(assetInfo) {
            const name = assetInfo.name ?? '';

            if (name.endsWith('.css')) {
              return 'css/[name][extname]';
            }

            if (/\.(?:png|jpe?g|gif|webp|avif|svg)$/i.test(name)) {
              return 'img/[name][extname]';
            }

            if (/\.(?:woff2?|ttf|otf|eot)$/i.test(name)) {
              return 'resources/fonts/[name][extname]';
            }

            return 'assets/[name][extname]';
          },
          chunkFileNames: 'js/[name].js',
          entryFileNames: 'js/[name].js',
        },
      },
    },
    plugins: [staticAssetsPlugin(), htmlIncludesPlugin(), htmlMinifyPlugin(shouldMinify)],
  };
});
