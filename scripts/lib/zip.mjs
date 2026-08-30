/**
 * Cross-platform zip helper.
 *
 * Uses the system `zip` command when available (Linux/macOS, including
 * GitHub Actions runners). Falls back to PowerShell's Compress-Archive
 * on Windows when `zip` is not on PATH.
 */

import { execFileSync } from 'node:child_process';
import { existsSync, rmSync } from 'node:fs';
import { dirname, basename, join } from 'node:path';

function hasCommand(cmd) {
  try {
    if (process.platform === 'win32') {
      execFileSync('where', [cmd], { encoding: 'utf8', stdio: 'pipe' });
    } else {
      execFileSync('which', [cmd], { encoding: 'utf8', stdio: 'pipe' });
    }
    return true;
  } catch {
    return false;
  }
}

/**
 * Create a zip archive of the contents of sourceDir.
 *
 * @param {string} sourceDir - Directory whose contents will be zipped.
 * @param {string} zipPath - Destination .zip file path.
 */
export function createZipSync(sourceDir, zipPath) {
  if (existsSync(zipPath)) {
    rmSync(zipPath, { force: true });
  }

  if (hasCommand('zip')) {
    // zip -r creates the archive with paths relative to sourceDir.
    execFileSync(
      'zip',
      ['-r', '-q', zipPath, '.'],
      { cwd: sourceDir, stdio: 'pipe' },
    );
    return;
  }

  if (process.platform === 'win32') {
    // PowerShell Compress-Archive zips the folder contents.
    const parent = dirname(sourceDir);
    const folderName = basename(sourceDir);
    const script = `Compress-Archive -Path "${join(parent, folderName, '*')}" -DestinationPath "${zipPath}" -Force`;
    execFileSync('powershell', ['-NoProfile', '-Command', script], {
      stdio: 'pipe',
    });
    return;
  }

  throw new Error('No zip utility available. Install `zip` or run on Windows with PowerShell.');
}
