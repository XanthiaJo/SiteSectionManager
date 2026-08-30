# Site Section Manager

A WordPress plugin that lets you split a single site into distinct sections — each with its own pages, posts, categories, and navigation — without setting up WordPress multisite.

## Why use it

Most WordPress sites are one big bucket of content. If you run something like a magazine with separate departments, a business with distinct product areas, or a community site with sub-communities, you probably want visitors to feel like they've moved into a focused subsite when they enter a section — while you keep managing everything from one WordPress install.

Site Section Manager gives you that structure without the overhead of multisite. You create sections in the admin, assign pages and posts to them, and the plugin handles section-scoped browsing, filtering, and navigation for you.

## What it does

### Sections

- Create named sections from a dedicated **Site Sections** admin screen.
- Each section gets its own **Home** page automatically, linked back to the section.
- Sections are stored as a hidden custom post type, so they don't clutter your normal content lists.

### Content assignment

- A **Site Section** selector appears on the edit screen for pages and posts, so you can assign content to a section.
- Categories and tags can also be linked to a section.
- Content with no section assignment is treated as an explicit **Unsectioned** bucket — it's a real filter value, not just "everything else."

### Admin browsing

- Filter the posts and pages list by section, including the Unsectioned bucket.
- Section-aware count tabs (All, Published, etc.) that respect the active section filter.
- A section workspace screen showing summary cards, section pages, and section posts in dedicated tables.

### Front-end navigation

- A thin global header bar sits at the top of every page, linking to each section's home page. It's intentionally lightweight so it works on top of any theme.
- The plugin can also feed your theme's existing primary menu location, swapping in section-specific navigation as visitors move between sections.
- Each section's menu can be auto-generated from its pages, or manually curated from the section admin screen.
- The site title shown to visitors updates to reflect the current section context.

## Installation

1. Download the latest `site-section-manager-X.Y.Z.zip` from the [releases page](https://github.com/XanthiaJo/SiteSectionManager/releases).
2. In your WordPress admin, go to **Plugins → Add New → Upload Plugin**.
3. Choose the zip file and click **Install Now**.
4. Activate the plugin.
5. Go to **Site Sections** in the admin sidebar to create your first section.

## Requirements

- WordPress 5.5 or later (uses `wp_body_open` for the global header).
- A theme that calls `wp_body_open()` for the full global header experience. If your theme doesn't, the header falls back to rendering at the footer.

## Development

### Repository structure

```
site-section-manager.php     Plugin bootstrap
includes/                    PHP classes (plugin, admin, content, frontend, navigation)
assets/css/                  Admin and front-end stylesheets
scripts/                     Build, release, and CI scripts (PowerShell + Node.js)
.github/workflows/           GitHub Actions release workflow
```

### Local packaging

```powershell
# Build a test package (works on a dirty working tree)
.\scripts\build.ps1

# Create a tagged release (requires a clean working tree)
.\scripts\release.ps1
```

The Node.js equivalents used by CI:

```bash
npm run release-notes:ai   # Generate AI-rewritten release notes (needs OPENROUTER_API_KEY)
npm run release-plan       # Plan the next release tag from conventional commits
npm run package            # Package the plugin zip into dist/
```

### Releases

Releases are created manually via the **Release** GitHub Actions workflow
(`workflow_dispatch`), not on every push. The workflow:

1. Rewrites commit messages into user-facing release notes using OpenRouter.
2. Derives the next version from [Conventional Commits](https://www.conventionalcommits.org/) since the last tag.
3. Creates the git tag and GitHub Release.
4. Packages the plugin and uploads the zip as a release asset.

Versioning uses pure semver — `feat:` bumps minor, `fix:` bumps patch, breaking changes bump major. See [docs/git-rules.md](docs/git-rules.md) for the full rules.

## Contributors

- XanthiaJo
- Codex
