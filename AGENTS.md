# Site Section Manager

This repository is a WordPress plugin for organizing one site into section-scoped content without using multisite.

If you are opening this project cold, the important mental model is:

- A `site_section` custom post type defines the sections.
- Regular `post` and `page` entries are linked to a section through `_ssm_section_id` post meta.
- Categories and tags can also be linked to a section through `ssm_section_id` term meta.
- The plugin adds custom admin workflows for creating, browsing, and filtering section-linked content.
- Each section is expected to have a linked `Home` page, used by the front-end global header.

## Entry Points

- Main bootstrap: [site-section-manager.php](E:/CodingProjects/site-section-manager/site-section-manager.php)
- Main plugin wiring: [includes/class-ssm-plugin.php](E:/CodingProjects/site-section-manager/includes/class-ssm-plugin.php)
- Content/domain logic: [includes/class-ssm-content.php](E:/CodingProjects/site-section-manager/includes/class-ssm-content.php)
- Admin facade: [includes/class-ssm-admin.php](E:/CodingProjects/site-section-manager/includes/class-ssm-admin.php)
- Section admin controller: [includes/class-ssm-section-admin-page.php](E:/CodingProjects/site-section-manager/includes/class-ssm-section-admin-page.php)
- Front-end header: [includes/class-ssm-frontend.php](E:/CodingProjects/site-section-manager/includes/class-ssm-frontend.php)

## Current Architecture

The plugin is intentionally split into small classes. Most files in `includes/` are kept below 300 lines.

### Admin

- `SSM_Admin` is a thin facade over the admin subsystems.
- `SSM_Content_Admin` delegates content admin concerns into:
  - `includes/admin/class-ssm-content-admin-display.php`
  - `includes/admin/class-ssm-content-admin-actions.php`
- `SSM_Section_Admin_Page` handles section page routing, section create/update/delete actions, and view resolution.
- `includes/admin/class-ssm-section-admin-page-renderer.php` contains section admin markup rendering.
- `includes/admin/class-ssm-section-admin-page-data.php` contains section counts, links, and dashboard data helpers.

### Front End

- `SSM_Frontend` enqueues the public stylesheet and renders a global top header on `wp_body_open`.
- The global header is built from section titles and links to each section's linked `Home` page.
- Front-end CSS lives in `assets/css/ssm-frontend.css`.

### Content Model

- Sections are stored as hidden admin-only `site_section` posts.
- A section home page is tracked with `_ssm_home_page_id` on the section post.
- A generated section home page is marked with `_ssm_is_section_home = 1`.
- Posts and pages are linked to sections through `_ssm_section_id`.
- Terms are linked through `ssm_section_id`.

## Important Behaviors

### Section Creation

Creating a section currently does all of the following:

1. Creates a `site_section` post.
2. Creates a `Home` page for that section.
3. Links that page back to the section with `_ssm_home_page_id`.
4. Marks the page as the section home page with `_ssm_is_section_home`.
5. Assigns the page to the section via `_ssm_section_id`.

If the section is created but the home page fails, the admin UI should show a warning notice.

### Unsectioned Content

The plugin treats content with no section assignment as an explicit `Unsectioned` bucket.

- `section_id=0` is meaningful.
- Admin list filters and counts should treat `0` as a real filter value, not as "empty".
- This has been a previous source of bugs, so be careful with `empty()` checks on `ssm_section_id`.

### Post/Page Admin Filters

Posts and pages are filtered in admin with:

- `pre_get_posts` for the actual list query
- `wp_count_posts` for the tab counters such as `All (n)` and `Published (n)`

If the row list and the counters disagree, inspect:

- [includes/admin/class-ssm-content-admin-actions.php](E:/CodingProjects/site-section-manager/includes/admin/class-ssm-content-admin-actions.php)

## Styling

- Admin styling: [assets/css/ssm-admin.css](E:/CodingProjects/site-section-manager/assets/css/ssm-admin.css)
- Front-end styling: [assets/css/ssm-frontend.css](E:/CodingProjects/site-section-manager/assets/css/ssm-frontend.css)

The front-end header is intentionally lightweight so it can sit on top of arbitrary themes with minimal assumptions.

## Release and Versioning

This project uses Conventional Commits to derive packaged release versions.

- Source plugin version stays `0.0.0-dev`.
- Built package version is stamped during packaging.
- Release docs: [docs/git-rules.md](E:/CodingProjects/site-section-manager/docs/git-rules.md)

### GitHub Actions release (primary path)

The release workflow at [.github/workflows/release.yml](E:/CodingProjects/site-section-manager/.github/workflows/release.yml)
is triggered **manually** via `workflow_dispatch`, not on push. It:

1. Generates AI-rewritten release notes from commit messages using OpenRouter (`scripts/generate-ai-release-notes.mjs`).
2. Plans the next semver tag from Conventional Commits (`scripts/release-plan.mjs`).
3. Creates the git tag and GitHub Release with the generated notes.
4. Packages the plugin into a zip (`scripts/package-plugin.mjs`) and uploads it as a release asset.

The workflow accepts a `force_release` input to force a patch release even when
no release-worthy commits are found.

Required repository secrets/variables:
- `OPENROUTER_API_KEY` (secret) — API key for the AI release-note rewrite.
- `OPENROUTER_MODEL` (variable, optional) — model name, defaults to `openrouter/free`.

### Local scripts (manual packaging)

- Build script: [build.ps1](E:/CodingProjects/site-section-manager/build.ps1) — works on a dirty working tree.
- Release script: [release.ps1](E:/CodingProjects/site-section-manager/release.ps1) — requires a clean working tree, creates tags.
- Node.js scripts (used by the workflow, also runnable locally):
  - `npm run release-notes:ai` — generate AI release notes (needs `OPENROUTER_API_KEY`).
  - `npm run release-plan` — plan the next release tag and notes.
  - `npm run package` — package the plugin zip into `dist/`.

### Versioning rules

Pure 3-segment semver (`vX.Y.Z`):

- `feat:` bumps minor
- `fix:` bumps patch
- breaking change bumps major
- `docs`, `refactor`, `chore`, `test` and similar non-breaking commits do **not** trigger a release

Generated release output in `dist/` should not be committed.
Generated release artifacts (`release-notes.ai.json`, `.github/release-notes.md`, `.github/release-plan.json`) are gitignored.

## Repository and Naming

- Plugin display name: `Site Section Manager`
- Repo name on GitHub/GitLab: `SiteSectionManager`
- Packaged folder/zip name: `site-section-manager`
- Text domain: `site-section-manager`

These names are intentionally not all identical, so do not "normalize" them blindly without checking build and plugin constraints.

## Known Gaps / Likely Next Work

- There is not yet a full front-end section routing system.
- The global header currently links to section home pages but does not enforce section-aware theming or templates.
- No automated test suite is set up in this repo yet.
- Theme compatibility for the global header should be verified in a real WordPress install.

## Practical First Checks For Any New Task

If you need to debug or extend this plugin, start here:

1. Read [site-section-manager.php](E:/CodingProjects/site-section-manager/site-section-manager.php) and [includes/class-ssm-plugin.php](E:/CodingProjects/site-section-manager/includes/class-ssm-plugin.php).
2. Check whether the task is admin-side, content-model, or front-end.
3. If the task involves section creation or navigation, inspect [includes/class-ssm-content.php](E:/CodingProjects/site-section-manager/includes/class-ssm-content.php).
4. If the task involves the section admin screen, inspect [includes/class-ssm-section-admin-page.php](E:/CodingProjects/site-section-manager/includes/class-ssm-section-admin-page.php) and the renderer/data classes.
5. If the task involves post/page list filters or counts, inspect [includes/admin/class-ssm-content-admin-actions.php](E:/CodingProjects/site-section-manager/includes/admin/class-ssm-content-admin-actions.php).
