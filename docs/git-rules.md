# Git Rules

- This project uses [Conventional Commits](https://www.conventionalcommits.org/) to drive release versioning.
- Do not commit generated release output in `dist/`.
- Keep the source plugin header at `0.0.0-dev`; release versions are stamped during packaging.
- Use more than one commit if needed across multiple files.
- Do not commit before being asked to do so.

## Commit Message Format

```
<type>(<scope>): <description>

<optional body>
```

- The **type** is mandatory and determines the version impact.
- The **scope** is optional but encouraged for clarity, for example `admin`, `css`, or `build`.
- The **description** should be lowercase, imperative, and concise.
- The **body** is optional but encouraged unless it duplicates the description.
- Bullet points are preferred in the body.

### Footers

Avoid adding non-functional footers such as `Generated with ...` or `Co-Authored-By: ...` to commit messages.

Functional footers are allowed only when they carry meaning for the project:

- `BREAKING CHANGE:` to signal a breaking change
- `Signed-off-by:` if the project requires DCO sign-off

## Commit Types and Version Impact

| Type | Version impact | Notes |
|------|----------------|-------|
| `feat` | minor bump | Adds user-facing functionality |
| `fix` | patch bump | Corrects a bug |
| any type with `BREAKING CHANGE:` or `!` | major bump | Backward-incompatible change |
| `docs`, `refactor`, `test`, `chore`, and other non-breaking commits | no release | These do not trigger a version bump or release on their own |

If no release tag exists yet, packaging starts from `0.0.0`.

## Breaking Changes

To signal a breaking change, either:

- Add `BREAKING CHANGE:` in the commit body footer, or
- Add `!` after the type/scope, for example `feat(admin)!: redesign section routing`

## Examples

```text
feat(admin): add unsectioned dashboard state
fix(build): correct release package name
docs(readme): update release workflow
refactor(view): split admin page template
test(content): cover unsectioned page filtering
chore(build): regenerate release zip
feat(admin)!: remove legacy section routing
```

## Versioning Mechanics

Versioning uses pure 3-segment semver (`vX.Y.Z`). Only `feat`, `fix`, and
breaking changes produce a release. Other commit types (`docs`, `refactor`,
`test`, `chore`) do not bump the version or trigger a release on their own.

There are two paths to a release:

### GitHub Actions (primary release path)

The `.github/workflows/release.yml` workflow is triggered **manually** via
`workflow_dispatch` (not on push). It:

1. Generates AI-rewritten release notes from commit messages using OpenRouter.
2. Plans the next semver tag from Conventional Commits since the latest tag.
3. Creates the git tag and GitHub Release with the generated notes.
4. Packages the plugin into a zip and uploads it as a release asset.

The workflow accepts a `force_release` input to force a patch release even
when no release-worthy commits are found.

### Local scripts (manual packaging)

- `scripts/build.ps1` packages the plugin for local testing. It derives the version
  from conventional commits and stamps it in the plugin header. It works on
  a dirty working tree.
- `scripts/release.ps1` is the stricter local release flow. It requires a clean
  working tree and creates the matching git tag.
- `npm run release-notes:ai`, `npm run release-plan`, and `npm run package`
  are Node.js equivalents used by the GitHub Actions workflow.

## Dirty Working Trees

- `scripts/build.ps1` is allowed to run on a dirty working tree.
- This is the expected path for manual packaging while iterating locally.
- `scripts/release.ps1` is different: it creates or validates release tags, so it still requires a clean tree.

## Tagging

Tags are the release milestone source of truth:

```text
git tag v0.2.0
git push origin v0.2.0
```

A tag pins the release version. `scripts/release.ps1` can create the matching tag automatically when the tree is clean.

## Scopes

Common scopes used in this project:

- `admin` - admin page rendering and workflow
- `assets` - static files shipped in the plugin
- `build` - packaging, release, and versioning scripts
- `content` - section content models and filters
- `css` - admin and plugin styling
- `docs` - README and project documentation
- `view` - admin templates and rendered screens

Scopes are not enforced. Use whatever best describes the area of change.
