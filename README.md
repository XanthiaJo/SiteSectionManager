# Site Section Manager

Skeleton WordPress plugin for organizing a single site into section-scoped content without multisite.

Author: XanthiaJo

## What it adds

- A `Site Sections` admin page with a view/create table
- A `site_section` custom post type for defining sections
- A `Site Section` selector on Pages and Posts
- Section-scoped taxonomy placeholders for categories and tags
- Admin list filters for section-aware content browsing

## Intended next steps

- Add section-specific rewrite rules or front-end routing
- Add term meta fields for section assignment to categories and tags
- Add separate list-table views for section-specific content
- Add permissions if editors should only manage one section

## Build and release

- Edit the source files in the repo.
- Run `.\build.ps1` to generate `dist\site-section-manager.zip`.
- `build.ps1` is allowed on a dirty working tree and is the normal way to make a manual test package.
- The source plugin header stays at `0.0.0-dev`; release versions are stamped during packaging.
- The packaged version comes from Conventional Commits and the latest `vX.Y.Z` tag.
- `feat:` bumps the minor version.
- `fix:` bumps the patch version.
- `BREAKING CHANGE:` or `!:` bumps the major version.
- Other non-breaking commits increment the revision segment when there is no newer release tag.
- Run `.\release.ps1` to build, create the matching `vX.Y.Z` tag if needed, and rebuild the release package.
- `release.ps1` is for tagged releases and expects a clean working tree.

## Contributors

- XanthiaJo
- Codex
