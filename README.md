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
- **7 published pages — current page IDs (after rebuilds):**
  - Home → **119**
  - About Us → **382** (post 120 archived as "About Us (Archive)")
  - Why ECEC → **422** (NEW 2026-04-25, scrollytelling layout)
  - Projects (index) → **122**
  - Our Services → **328** (post 121 archived as "Our Services (Archive)")
  - People → **123**
  - Contact Us → **124**
  - Always lookup by slug before editing — don't trust the original 119–124 mapping for About Us or Our Services
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
| Team Members | `docs/team-members.md` | `ecec_team_member` CPT with `tier` (founder / director / senior) + `bio` + `department` meta. People page (rebuilt 2026-05-17 per `docs/team page-01.jpg` mockup) renders: gray hero band → "OUR LEADERS" heading → `[ecec_team_leadership]` (alternating full-width image+bio rows; even rows photo-right via CSS `:nth-child(even)`) → 4 department blocks (Business Development, Design Team, Project Management, Administration), each a light-gray label + `[ecec_team_department dept="..."]` grid. Senior members are invisible unless a department is assigned. |
| Single-project content blocks | `docs/single-project-blocks.md` | Magazine-style per-project layout. 7 block types (text / full-image / image-pair / image-text-split / project-data / pull-quote / gallery with **lightbox**) managed via drag-sort admin UI. Image partials render gray dashed-border placeholders when image_id=0. Default seed pattern v2 (2026-05-02): text → split (image LEFT) → split (image RIGHT) → full-image → project-data → pull-quote → gallery. Shipped 2026-04-24, layout v2 + lightbox shipped 2026-05-02. |
| Why ECEC scrollytelling | `docs/why-ecec.md` | Page (post 422 VPS / 383 local). Mirrors Emaurri's `portfolio/vertical-divided-list` demo. 5 hardcoded pillars (Proven Performance / Data-Driven Efficiency / Global Standards Local Insight / Collaborative Engagement / Comprehensive Expertise). The `[ecec_why]` shortcode emits the theme's `qodef-portfolio-exhibition` widget DOM, so theme CSS + JS drive the sticky-left + scrolling-right behaviour. Refactored 2026-04-28. |
| About Us rebuild | (no separate doc) | Post 382 — replaces archived 120. Chairs page-title hero + 2-col ABOUT ECEC intro + clients marquee + WHO WE ARE 2-col + 4-col Key Highlights cards (PNG numbers). Mirrors Emaurri demo About Us. |
| Clone as draft | `docs/single-project-blocks.md#clone-a-project` | Copy a whole portfolio project (blocks, images, meta, taxonomies) as a new draft — from the admin list row action or from inside the Publish sidebar |
| Services page (Elementor) | `portfolio-help.html` Part E | `/our-services/` — real Elementor page built from the Emaurri demo `/our-services/` XML export, with ECEC narrative. 4 blocks: Hero → Progress (3 bars) → Services Accordion (7 services) → Counters (4 animated). Block order swapped 2026-04-28 (accordion moved up before counters). Editable inline via Elementor. Previous approved layout preserved at `/our-services-legacy/`. |
| Home page 5-block layout | `_deploy/apply_home_page_v2.php` | Blog-list slider (Emaurri demo widget) → "We Design the Future" intro (2-col heading/narrative) → main-home RevSlider → clients marquee → 3 featured projects. Each block is a separate Elementor widget so the client can reorder/edit/remove any of them inline. Block 2's container max-width override (CSS, scoped to `body.page-id-119 .elementor-element-307c52e > .elementor-container`) lets the boxed section span up to 1600px on wide screens — fixes a cross-machine width-collapse bug reported 2026-04-27. |
| Clients marquee | functions.php `[ecec_clients_marquee]` | Infinite horizontal scroll of client logos, grayscale → color on hover, pause on hover. Renders an "OUR CLIENTS" heading above the strip via the `title="..."` shortcode attribute (default "Our Clients", pass `title=""` to hide). Logo row height uses fluid `clamp(80px, 7vw, 130px)` so logos scale with viewport. Logo list stored in `ecec_client_logo_ids` option; import via `_deploy/import_client_logos.php`. |
| Portfolio carousel | functions.php `[ecec_portfolio_carousel]` | Horizontal scroll-snap carousel of portfolio items with prev/next buttons. Shortcode stays registered (no longer on the home page after Block 1 switched to the blog slider — available for reuse elsewhere). |
| Year timeline filter | `docs/year-timeline-filter.md` | **REMOVED 2026-04-21.** Kept for reference only |

## Custom Shortcodes

| Shortcode | Where it's placed | What it renders |
|---|---|---|
| `[ecec_project_search]` | Projects page (post 122) | Horizontal Search / By Type / By Location / SEARCH bar |
| `[ecec_featured_projects columns="2"]` | Projects page Featured section | 2-col grid of projects where `_ecec_featured = 1` |
| `[ecec_team_grid columns="3" min="6"]` | (legacy — no longer on the People page) | Grid of all senior-tier team members regardless of department. Kept for ad-hoc embeds elsewhere. |
| `[ecec_team_department dept="..." columns="4" min="3"]` | People page (post 123), one per department section | Senior-tier grid filtered by `_ecec_team_department` meta. Cards show photo + name + role. `min` pads with "Name of employee / Position" placeholder cards. Departments: `business-development`, `design-team`, `project-management`, `administration`. Added 2026-05-17. |
| `[ecec_team_leadership]` | People page (post 123) | Alternating full-width image+bio rows for `founder` then `director` tier members. Default `min_directors=0` (no placeholders). |
| `[ecec_portfolio_carousel posts_per_page="8"]` | Home page block 1 | Horizontal scroll-snap carousel of portfolio items |
| `[ecec_clients_marquee]` | Home page block 4, About Us (382) | Infinite marquee of client logos (grayscale, color on hover). Logos in `ecec_client_logo_ids` option. Default height 45px. |
| `[ecec_why]` | Why ECEC page (post 422 VPS / 383 local) | Outputs the theme's `qodef-portfolio-exhibition` widget DOM with 5 hardcoded ECEC pillars. Theme JS handles sticky-swap + bg-text animation. Each pillar's `image` field accepts a URL (empty = placeholder). |

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
| `wp-content/themes/emaurri-child/functions.php` | Footer layout, brand fonts, taxonomies, metaboxes, shortcodes, enqueue, block repeater metabox, clone-project handler |
| `wp-content/themes/emaurri-child/style.css` | ECEC brand identity CSS (colors, fonts, Projects page styling, featured cards, badges, single-project block system) |
| `wp-content/themes/emaurri-child/single-portfolio-item.php` | Child theme override — magazine-style single-project template rendering `_ecec_blocks` |
| `wp-content/themes/emaurri-child/template-parts/blocks/*.php` | 7 block partials (text, full-image, image-pair, image-text-split, project-data, pull-quote, gallery) |
| `wp-content/themes/emaurri-child/assets/gallery-lightbox.js` | Vanilla JS lightbox for gallery blocks. Click-to-enlarge with prev/next, ESC + arrow keys, touch swipe. Enqueued only on `is_singular('portfolio-item')`. Added 2026-05-02. |
| `wp-content/themes/emaurri-child/fonts.css` | Self-hosted webfont @font-face rules (Plus Jakarta Sans + Argentum Sans). Generated 2026-05-04 — replaces external Google Fonts + CDNFonts requests. Cache-busted via `filemtime()`. |
| `wp-content/themes/emaurri-child/assets/fonts/*` | Self-hosted woff/woff2 binaries — 4 PJS variable subsets, 18 Argentum weights+styles. Defends against corporate firewalls blocking external font CDNs. |
| `wp-content/themes/emaurri-child/assets/admin-blocks-repeater.js` | Drag-sort content blocks UI on the portfolio-item edit screen (jQuery UI + wp.media) |
| `wp-content/themes/emaurri-child/assets/admin-blocks-repeater.css` | Styles for the block repeater metabox |
| `wp-content/themes/emaurri-child/assets/project-filter.js` | Unified Projects-page filter coordinator (search / type / location / sidebar) |
| `wp-content/themes/emaurri-child/assets/timeline.js` | Retired year-timeline toggle handler (no page uses the shortcode anymore) |
| `wp-content/themes/emaurri-child/assets/why-ecec.js` | **Dead code as of 2026-04-28.** No longer enqueued — theme's `emaurri-core.js` handles the scrollytelling now. File kept on disk for history; safe to delete. |
| `wp-content/plugins/emaurri-core/inc/core-dashboard/class-emaurricore-dashboard.php` | Patched license bypass |
| `help/` | **NEW 2026-04-28 — Help Center.** Animated multi-page admin guide hub. Open at `/help/index.html`. 8 page-by-page guides (projects, home, about-us, why-ecec, services, people, contact, settings) + shared `_styles.css` / `_scripts.js`. Brand-colored hero with particle background, sticky TOC + reading-progress bar per page, scroll-revealed sections, animated mock admin UI on the projects guide (drag-block animation, featured-toggle button, upload area). Self-contained — no WP dependency, runs as static files. |
| `portfolio-help.html` | Older admin guide: Part A (slider) + B (portfolio projects) + C (team) + D (Projects page layout). Superseded by `/help/` but still deployed for backward links. |
| `project-blocks-help.html` | Older animated admin guide for the block editor + clone feature (16 sections, CSS-animated demos). Superseded by `/help/projects.html`. Still deployed for backward links. |
| `developer-guide.html` (in `docs/` + deployed to VPS) | Technical reference for the dev. Brief history, site map (URLs ↔ post IDs), all custom shortcodes, CPTs, block system, scrollytelling architecture, brand variables, Elementor schema gotchas, 10 critical lessons, backup paths, deployment method |
| `_deploy/` | Idempotent PHP deploy scripts + PowerShell Posh-SSH drivers |
| `_theme_backup/` | Scraped content backups from ecec.co |
| `docs/` | Feature design docs (see table above) |

## Environments

| Environment | URL | Status |
|-------------|-----|--------|
| Local | http://localhost/ecec/ | Fully working, feature work happens here first |
| VPS Staging | http://207.180.196.39/ecec/ | Synced through 2026-05-17. Includes About Us rebuild (post 382), Why ECEC scrollytelling (post 422), People page **v2 redesign** (alternating leader rows + 4 department grids, deployed 2026-05-17 — see `docs/team page-01.jpg`), Projects Recent grid switched to `load-more/12` pagination, Services page cleaned of inherited "cozy" qodef demo watermark, About Us page-title bar text hidden + highlight card body text centered, 39 new client logos, menu rewire, Contact Us typography, all 81 portfolio-items have breadcrumb + auto-filled sidebar, developer guide live at `/developer-guide.html` |
| Production | https://ecec.co | Not yet migrated (old theme) |

## Open issues

- **RevSlider Block 3 overlay misalignment on wide viewports** — slider text overlays use absolute pixel positions keyed to a Grid Size baseline; on viewports wider than the baseline the slide image scales but the layer offsets don't, so text drifts off the image. Fix is in-admin via WP Admin → Slider Revolution → main-home → Layout (set Grid Size larger or switch layers to %-based positions). No code change required.
- **Logo placeholder** — header still shows the Emaurri demo wordmark; needs replacement with the official ECEC logo (deferred — see `help/settings.html#header-logo`).

## Recently resolved

- **2026-04-28** — Cross-machine width mismatch (Block 2 narrowing on wide screens). Fixed by overriding the boxed Elementor container's `max-width` for the home page Block 2 section so it can grow to 1600px, plus restructuring Block 2's HTML to use the 2-col `.ecec-home-intro__row` grid that the CSS expected. See `docs/client-display-issue.md`.
