# ECEC Engineering Consultants — WordPress Migration

Migrating **ecec.co** from its current theme to the **Emaurri theme** (by Qode Interactive).

Progress tracker in `PROGRESS.md`. Feature-specific docs in `docs/`.

## Local Setup

- **XAMPP:** D:\xampp, site at D:\xampp\htdocs\ecec
- **Database:** ecec_wp (MySQL root, no password)
- **WP Admin:** http://localhost/ecec/wp-admin/ (admin / admin123)
- **PHP:** max_execution_time increased to 300s in php.ini

## Theme & Plugins

- **Theme:** Emaurri Child (active), Emaurri parent v1.5
- **Plugins:** Elementor, Qode Framework, Emaurri Core, RevSlider 6.7.33, Qi Addons, Contact Form 7, Yoast SEO
- **License bypass:** `wp-content/plugins/emaurri-core/inc/core-dashboard/class-emaurricore-dashboard.php` — `get_code()` patched to allow localhost AND 207.180.196.39

## Content

- **54 portfolio projects** with images, descriptions, sidebar meta (qodef_portfolio_info_items), and qodef_portfolio_media
- **10 portfolio categories (sectors):** Commercial & Retail, Data Centers, Educational, Governmental Sector, Healthcare, Hospitality, Industrial & Distribution, Master Planning, Residential, Sustainability
- **4 portfolio locations (countries):** Saudi Arabia, UAE, Jordan, Other
- **6 featured projects** marked via `_ecec_featured` meta (surface in the Projects page Featured section)
- **6 pages (Elementor):** Home (119), About Us (120), Our Services (121), Projects (122), People (123), Contact Us (124)
- **Team roster** managed via `ecec_team_member` CPT → rendered on People page via `[ecec_team_grid]`
- **RevSlider:** "main-home" with ECEC project images
- **Brand fonts:** Plus Jakarta Sans (headings/UI), Argentum Sans (body), Tajawal (Arabic)
- **Brand palette** from official PDF: White, Platinum, Isabelline, Silver Sand, Slate Gray, Raisin Black

## Custom Features

Each of these is documented in more detail under `docs/`:

| Feature | Docs | What it does |
|---|---|---|
| Horizontal filter bar | `docs/projects-filter.md` | Search + By Type + By Location on the Projects page. Stacks with left category sidebar. |
| Featured Projects + hero | `docs/featured-projects.md` | lwkp.com-style Projects page: hero, tabs, Featured section, badge overlay |
| Team Members | `docs/team-members.md` | `ecec_team_member` CPT + `[ecec_team_grid]` shortcode for People page |
| Single-project blocks | `docs/single-project-blocks.md` | **PLANNED, not yet built.** Magazine-style per-project layout with structured content blocks |
| Year timeline filter | `docs/year-timeline-filter.md` | **REMOVED 2026-04-21.** Kept for reference only |

## Custom Shortcodes

| Shortcode | Where it's placed | What it renders |
|---|---|---|
| `[ecec_project_search]` | Projects page (post 122) | Horizontal Search / By Type / By Location / SEARCH bar |
| `[ecec_featured_projects columns="2"]` | Projects page Featured section | 2-col grid of projects where `_ecec_featured = 1` |
| `[ecec_team_grid columns="3"]` | People page (post 123) | Grid of team members from the CPT |

## Contact Forms

- **Form 6** ("Contact form 1") and **Form 132** ("Contact Form") — both send to info@ecec.co

## SEO (Yoast)

- Meta titles, descriptions, and focus keywords configured for all 6 pages
- 54 portfolio items have auto-generated meta descriptions
- Site tagline: "MEP, ICT & Sustainability Engineering Consultancy"
- Company schema, social/OG, portfolio templates all configured
- Sitemap, schema/structured data, breadcrumbs enabled

## Key Files

| File | Purpose |
|------|---------|
| `wp-content/themes/emaurri-child/functions.php` | Footer layout, brand fonts, taxonomies, metaboxes, shortcodes, enqueue |
| `wp-content/themes/emaurri-child/style.css` | ECEC brand identity CSS (colors, fonts, Projects page styling, featured cards, badges) |
| `wp-content/themes/emaurri-child/assets/project-filter.js` | Unified filter coordinator (search / type / location / year / sidebar) |
| `wp-content/themes/emaurri-child/assets/timeline.js` | Year-timeline toggle handler (delegates to coordinator; timeline shortcode itself is not placed on any page) |
| `wp-content/plugins/emaurri-core/inc/core-dashboard/class-emaurricore-dashboard.php` | Patched license bypass |
| `portfolio-help.html` | Admin guide: Part A (slider) + B (portfolio projects) + C (team) + D (Projects page layout) |
| `_deploy/` | Idempotent PHP deploy scripts + PowerShell Posh-SSH drivers |
| `_theme_backup/` | Scraped content backups from ecec.co |
| `docs/` | Feature design docs (see table above) |

## Environments

| Environment | URL | Status |
|-------------|-----|--------|
| Local | http://localhost/ecec/ | Fully working, feature work happens here first |
| VPS Staging | http://207.180.196.39/ecec/ | Synced through 2026-04-21 (hero + featured + filter bar) |
| Production | https://ecec.co | Not yet migrated (old theme) |
