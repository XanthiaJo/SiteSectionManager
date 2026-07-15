# Simple Section Manager

Skeleton WordPress plugin for organizing a single site into section-scoped content without multisite.

Author: XanthiaJo & Codex

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
- Run `.\build.ps1` to generate `dist\simple-section-manager.zip`.
- The packaged plugin header version comes from `git describe`, not from a committed version bump.
- The working tree stays on `0.0.0-dev`; release versions are stamped only into the generated build.
