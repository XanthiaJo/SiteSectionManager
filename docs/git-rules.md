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
| `docs`, `refactor`, `test`, `chore`, and other non-breaking commits | revision bump | These increment the fourth build segment when there is no newer release tag |

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

Versioning is handled by `build.ps1` and `release.ps1`.

The build scripts:

1. Read the latest `vX.Y.Z` tag as the release baseline.
2. Walk commit history since that tag.
3. Derive the next version from Conventional Commit messages.
4. Stamp the generated plugin header in `dist/site-section-manager/site-section-manager.php`.

## Tagging

Tags are the release milestone source of truth:

```text
git tag v0.2.0
git push origin v0.2.0
```

A tag pins the release version. `release.ps1` can create the matching tag automatically when the tree is clean.

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
