/**
 * Plan the next semver tag from Conventional Commits since the latest tag.
 *
 * Uses pure 3-segment semver: only feat (minor), fix (patch), and breaking
 * changes (major) trigger a release. docs/refactor/test/chore and other
 * non-breaking commits do not produce a release on their own.
 *
 * This script is intentionally side-effect-light: it writes a JSON plan and a
 * markdown release body, but does not create tags or releases itself.
 */

import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';

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

function git(root, ...args) {
  return execFileSync('git', ['-C', root, ...args], {
    encoding: 'utf8',
    maxBuffer: 50 * 1024 * 1024,
  });
}

function parseSemverTag(tag) {
  const match = /^v(\d+)\.(\d+)\.(\d+)$/.exec(tag.trim());
  if (!match) return null;
  return [Number(match[1]), Number(match[2]), Number(match[3])];
}

function compareSemver(a, b) {
  for (let i = 0; i < 3; i++) {
    if (a[i] !== b[i]) return a[i] - b[i];
  }
  return 0;
}

function formatSemver(version) {
  return `v${version[0]}.${version[1]}.${version[2]}`;
}

function bumpSemver(version, bumpType) {
  switch (bumpType) {
    case 'major':
      return [version[0] + 1, 0, 0];
    case 'minor':
      return [version[0], version[1] + 1, 0];
    case 'patch':
      return [version[0], version[1], version[2] + 1];
    default:
      return version.slice();
  }
}

function getCommitBump(subject) {
  if (/BREAKING CHANGE|!:/i.test(subject)) return 'major';
  if (/^feat(\([^)]+\))?:/i.test(subject)) return 'minor';
  if (/^fix(\([^)]+\))?:/i.test(subject)) return 'patch';
  return 'none';
}

function getChangelogGroup(subject) {
  if (/BREAKING CHANGE|!:/i.test(subject)) return 'breaking';
  if (/^feat(\([^)]+\))?:/i.test(subject)) return 'feature';
  if (/^fix(\([^)]+\))?:/i.test(subject)) return 'fix';
  if (/^docs(\([^)]+\))?:/i.test(subject)) return 'docs';
  if (/^refactor(\([^)]+\))?:/i.test(subject)) return 'refactor';
  if (/^test(\([^)]+\))?:/i.test(subject)) return 'test';
  if (/^chore(\([^)]+\))?:/i.test(subject)) return 'chore';
  return 'other';
}

function humanizeCommitSubject(subject) {
  const cleaned = subject.replace(/^(?:[a-z]+(?:\([^)]+\))?!?:\s*|BREAKING CHANGE:?\s*)/i, '').trim();
  if (cleaned === '') return subject.trim();
  return cleaned.charAt(0).toUpperCase() + cleaned.slice(1);
}

function loadAiReleaseNotes(root) {
  const notesPath = resolve(root, 'release-notes.ai.json');
  if (!existsSync(notesPath)) return {};

  const data = JSON.parse(readFileSync(notesPath, 'utf8'));
  return data && data.notes && typeof data.notes === 'object' ? data.notes : {};
}

function findLatestTag(root, headRef) {
  const output = git(root, 'tag', '--merged', headRef, '--list', 'v*');
  const tags = [];
  for (const line of output.split('\n')) {
    const tag = line.trim();
    if (tag === '') continue;
    const version = parseSemverTag(tag);
    if (!version) continue;
    tags.push({ tag, version });
  }

  if (tags.length === 0) return null;

  tags.sort((a, b) => compareSemver(b.version, a.version));
  return tags[0];
}

function getCommitsSince(root, latestTag, headRef) {
  const rangeArgs = latestTag ? [`${latestTag}..${headRef}`] : [headRef];
  const output = git(
    root,
    'log',
    ...rangeArgs,
    '--reverse',
    '--date=short',
    '--pretty=format:%H%x1f%ad%x1f%s%x1f%B%x1e',
    '--',
    '.',
  );

  const commits = [];
  for (const record of output.split('\x1e')) {
    if (record.trim() === '') continue;

    const parts = record.split('\x1f', 4);
    if (parts.length !== 4) continue;

    const subject = parts[2].trim();
    if (/^chore\(release-notes\):/i.test(subject)) continue;

    commits.push({
      sha: parts[0].trim(),
      date: parts[1].trim(),
      subject,
      body: parts[3],
    });
  }
  return commits;
}

const args = parseArgs(process.argv);
const root = args.root ? resolve(args.root) : process.cwd();
const headRef = args.head || 'HEAD';
const notesPath = args.notes ? resolve(args.notes) : resolve(root, '.github/release-notes.md');
const jsonPath = args.json ? resolve(args.json) : resolve(root, '.github/release-plan.json');
const latestTag = findLatestTag(root, headRef);
if (!latestTag) {
  const plan = {
    latestTag: null,
    latestVersion: null,
    nextVersion: null,
    releaseTag: null,
    bumpType: 'none',
    shouldRelease: false,
    commitCount: 0,
    notesPath,
    reason: 'No reachable semver tag found. Create a v0.1.0 baseline tag before enabling automatic releases.',
  };

  const notes = [
    '# No release planned',
    '',
    plan.reason,
    '',
  ];

  mkdirSync(dirname(notesPath), { recursive: true });
  writeFileSync(notesPath, notes.join('\n'), 'utf8');
  mkdirSync(dirname(jsonPath), { recursive: true });
  writeFileSync(jsonPath, `${JSON.stringify(plan, null, 2)}\n`, 'utf8');
  console.log(JSON.stringify(plan, null, 2));
  process.exit(0);
}

const commits = getCommitsSince(root, latestTag.tag, headRef);
const aiReleaseNotes = loadAiReleaseNotes(root);

let bumpType = 'none';
for (const commit of commits) {
  const commitBump = getCommitBump(commit.subject);
  if (commitBump === 'major') {
    bumpType = 'major';
    break;
  }
  if (commitBump === 'minor' && bumpType !== 'major') {
    bumpType = 'minor';
  } else if (commitBump === 'patch' && bumpType === 'none') {
    bumpType = 'patch';
  }
}

const nextVersion = bumpSemver(latestTag.version, bumpType);
const releaseTag = formatSemver(nextVersion);
const shouldRelease = bumpType !== 'none';

const groupLabels = {
  breaking: 'Breaking Changes',
  feature: 'Features',
  fix: 'Fixes',
  docs: 'Documentation',
  refactor: 'Refactors',
  test: 'Tests',
  chore: 'Maintenance',
  other: 'Other Changes',
};
const groupOrder = ['breaking', 'feature', 'fix', 'docs', 'refactor', 'test', 'chore', 'other'];
const grouped = Object.fromEntries(groupOrder.map((group) => [group, []]));

for (const commit of commits) {
  const group = getChangelogGroup(commit.subject);
  const aiNote = aiReleaseNotes[commit.sha] || {};
  grouped[group].push({
    title: aiNote.title || humanizeCommitSubject(commit.subject),
    details: Array.isArray(aiNote.details) ? aiNote.details : [],
  });
}

const notes = [];
notes.push(`# ${releaseTag}`);
notes.push('');
notes.push(latestTag.tag ? `Changes since ${latestTag.tag}.` : 'Initial tagged release.');
notes.push('');

if (!shouldRelease) {
  notes.push('No release-worthy Conventional Commit changes were found since the last release tag.');
  notes.push('');
} else {
  for (const group of groupOrder) {
    const items = grouped[group];
    if (items.length === 0) continue;

    notes.push(`## ${groupLabels[group]}`);
    notes.push('');
    for (const item of items) {
      notes.push(`- ${item.title}`);
      for (const detail of item.details) {
        notes.push(`  - ${detail}`);
      }
    }
    notes.push('');
  }
}

notes.push('---');
notes.push('');
notes.push(`_Reworded for readability from ${commits.length} commits by openrouter/free_`);
notes.push('');

mkdirSync(dirname(notesPath), { recursive: true });
writeFileSync(notesPath, notes.join('\n'), 'utf8');

const plan = {
  latestTag: latestTag.tag,
  latestVersion: formatSemver(latestTag.version),
  nextVersion: releaseTag,
  releaseTag,
  bumpType,
  shouldRelease,
  commitCount: commits.length,
  notesPath,
};

mkdirSync(dirname(jsonPath), { recursive: true });
writeFileSync(jsonPath, `${JSON.stringify(plan, null, 2)}\n`, 'utf8');
console.log(JSON.stringify(plan, null, 2));
