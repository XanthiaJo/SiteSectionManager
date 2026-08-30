/**
 * Package the Site Section Manager plugin into a distributable zip.
 *
 * Replicates the logic of scripts/build.ps1 in Node.js so it can run in a
 * cross-platform GitHub Actions environment.
 *
 * Steps:
 *   1. Read the release version from --version (or .github/release-plan.json).
 *   2. Copy plugin files into dist/site-section-manager/.
 *   3. Stamp the version in the plugin header and SSM_VERSION constant.
 *   4. Zip the package folder.
 */

import { execFileSync } from 'node:child_process';
import {
  cpSync,
  existsSync,
  mkdirSync,
  readFileSync,
  rmSync,
  writeFileSync,
} from 'node:fs';
import { resolve, join, dirname } from 'node:path';
import { createZipSync } from './lib/zip.mjs';

function parseArgs(argv) {
  const args = {};
  for (let i = 2; i < argv.length; i++) {
    const arg = argv[i];
    if (!arg.startsWith('--')) continue;

    const eq = arg.indexOf('=');
    if (eq !== -1) {
      args[arg.slice(2, eq)] = arg.slice(eq + 1);
      continue;
    }

    const key = arg.slice(2);
    const next = argv[i + 1];
    if (next && !next.startsWith('--')) {
      args[key] = next;
      i++;
    } else {
      args[key] = true;
    }
  }
  return args;
}

const PACKAGE_NAME = 'site-section-manager';
const PLUGIN_FILE = 'site-section-manager.php';
const ITEMS_TO_COPY = ['assets', 'includes', 'README.md'];

const args = parseArgs(process.argv);
const root = resolve(args.root || '.');
const outputDir = resolve(args.outputDir || join(root, 'dist'));

let version = args.version;
if (!version) {
  const planPath = resolve(root, '.github/release-plan.json');
  if (existsSync(planPath)) {
    const plan = JSON.parse(readFileSync(planPath, 'utf8'));
    if (plan.releaseTag) {
      version = plan.releaseTag.replace(/^v/, '');
    }
  }
}

if (!version) {
  console.error('No version provided. Use --version=X.Y.Z or run release-plan first.');
  process.exit(1);
}

const stageDir = outputDir;
const packageDir = join(stageDir, PACKAGE_NAME);
const zipPath = join(stageDir, `${PACKAGE_NAME}.zip`);

if (existsSync(stageDir)) {
  rmSync(stageDir, { recursive: true, force: true });
}

mkdirSync(packageDir, { recursive: true });

for (const item of ITEMS_TO_COPY) {
  const src = join(root, item);
  if (!existsSync(src)) {
    console.warn(`Warning: ${item} not found, skipping.`);
    continue;
  }
  cpSync(src, join(packageDir, item), { recursive: true });
}

const pluginSource = join(root, PLUGIN_FILE);
const pluginTarget = join(packageDir, PLUGIN_FILE);
let pluginContent = readFileSync(pluginSource, 'utf8');

pluginContent = pluginContent.replace(
  /^ \* Version: .*$/m,
  ` * Version: ${version}`,
);
pluginContent = pluginContent.replace(
  /^define\( 'SSM_VERSION', '.*?' \);$/m,
  `define( 'SSM_VERSION', '${version}' );`,
);

writeFileSync(pluginTarget, pluginContent);

createZipSync(packageDir, zipPath);

const gitDescribe = (() => {
  try {
    return execFileSync('git', ['-C', root, 'describe', '--tags', '--always', '--dirty'], {
      encoding: 'utf8',
    }).trim();
  } catch {
    return '';
  }
})();

console.log(`Built package: ${zipPath}`);
console.log(`Git describe: ${gitDescribe}`);
console.log(`Release version: ${version}`);
