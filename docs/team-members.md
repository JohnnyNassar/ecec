# Team Members — People Page

The People page showcases the ECEC team using a custom post type (CPT). The client can add, edit, reorder, or remove members from WordPress admin without touching Elementor.

> **Editor's guide:** end-user instructions for adding/editing team members live at [`help/people.html`](../help/people.html). This doc covers the technical implementation.

## What the client sees in WP Admin

A new **Team Members** menu item in the sidebar (icon: groups) with:

- **All Team Members** — list view with photo, role, offices, department, and order columns
- **Add New Member** — a simple form with:
	- Title (the person's name)
	- Rich text editor (optional bio, not currently displayed on the card but available if the client asks)
	- **Team Member Details** box with:
		- **Tier** — `Senior team` / `Director` / `Founder / Managing Director`
		- **Department** — fixed dropdown: Business Development / Design Team / Project Management / Administration (used only when Tier = Senior team)
		- Role / Title (e.g. "Founder | Principal")
		- Office Locations (e.g. "Dubai | Riyadh | Amman" — separate with `|`)
		- Bio (Founder & Director only — ~100-150 words, displayed next to the photo on the Our Leaders rows)
	- **Featured image** box — the member photo
	- **Page Attributes → Order** — lower numbers appear first

Saving publishes the member and they appear on the People page automatically (no Elementor edits needed).

## What the visitor sees on /people/

Layout was redesigned 2026-05-17 per `docs/team page-01.jpg` mockup. The page is six top-to-bottom sections, all driven by shortcodes (no per-member Elementor edits needed):

**1. Hero band** — empty full-width gray strip under the site header (`.ecec-people-hero`). Reserved for a future hero image; client can drop an Elementor background image on the section without touching code.

**2. "OUR LEADERS" heading** — centered h2, canonical uppercase tracked-spacing style (`.ecec-people-leaders-heading` page-scoped CSS on `body.page-id-123`).

**3. Leadership rows** (`[ecec_team_leadership]`): `founder` records first, then `director`, each as a full-width row with a large square photo on one side and name + role + locations + bio narrative on the other. **Alternates sides** by CSS `:nth-child(even)` — first leader gets photo-left, second photo-right, third photo-left, etc. Stacks vertically below 600px. Default `min_directors=0` (no placeholders) since the page is leader-only at the top; pass `min_directors="N"` to pad with placeholder rows during recruitment.

**4-7. Department grids** — four blocks, each a light-gray left-aligned label heading (`.ecec-people-dept-label`) followed by an `[ecec_team_department]` 4-col grid filtered by department. Defaults: Business Development (min=3), Design Team (min=9), Project Management (min=3), Administration (min=3). Cards show photo + name + position only — locations are hidden inside `[data-dept]` grids to match the mockup.

Member visibility decision tree:
- Tier = `founder` or `director` → renders in section 3 (Our Leaders)
- Tier = `senior` AND Department = one of the 4 → renders in the matching department grid (4-7)
- Tier = `senior` AND Department empty → **invisible** on the page (client must pick a department)

**Placeholder padding** is per-department now. Cards fill any gap up to `min`, showing a stacked-images SVG icon plus "Name of employee / Position" labels (matching the mockup's literal placeholder copy). Cards auto-disappear once published members reach `min`.

**Legacy:** `[ecec_team_grid]` still exists (untiered + senior tier, no department filter) but is not used on the page anymore. Kept for ad-hoc embeds in other pages if needed; backwards-compat only.

## Files

All changes live in `wp-content/themes/emaurri-child/`:

| File | What's in it |
|---|---|
| `functions.php` | CPT registration, meta box, save handler, admin list columns, `[ecec_team_grid]` shortcode |
| `style.css` | Card grid layout, photo styling, typography, responsive breakpoints |

Nothing in `assets/` is needed — pure CSS, no JS.

## Shortcodes

### `[ecec_team_department dept="..."]` — per-department senior grid (current)

```
[ecec_team_department dept="design-team" columns="4" min="9"]
```

Attributes:
- `dept` — **required**, one of `business-development` | `design-team` | `project-management` | `administration`. Unknown slugs render an inline error string.
- `columns` — `1`..`4` (default `4`)
- `min` — minimum visible cards; placeholder cards fill the gap. Default `0`.
- `orderby` — WP_Query orderby (default `menu_order`)
- `order` — `ASC` / `DESC` (default `ASC`)

Cards render: photo (or stacked-images placeholder) + name + role. Office locations are deliberately hidden inside `[data-dept]` grids to match the mockup's compact card style. Filters to `_ecec_team_tier IN (senior, EMPTY)` so founder/director records never leak into a department grid.

### `[ecec_team_grid]` — image-only senior grid (legacy)

```
[ecec_team_grid columns="3" min="6"]
```

Attributes:
- `columns` — `1`, `2`, `3`, or `4` (default `3`)
- `min` — minimum visible cards. If real published members < min, the grid is padded with placeholder cards (added 2026-04-25). Default `0` = no padding.
- `orderby` — any WP_Query orderby (default `menu_order`)
- `order` — `ASC` / `DESC` (default `ASC`)

Filters its WP_Query to ONLY return `senior` tier members (or untiered, for backwards compat). Founders/directors render in `[ecec_team_leadership]` instead and are excluded here so they don't appear twice. Department filter is NOT applied — this shortcode returns every senior member regardless of department, so don't reuse it on the People page or members will double-render.

No longer placed on post 123 as of 2026-05-17 — replaced by four `[ecec_team_department]` calls. Still available for ad-hoc embeds elsewhere if a full-team grid is wanted (e.g. an About-page snippet).

### `[ecec_team_leadership]` — image+bio rows for founder & director tiers (added 2026-05-02)

```
[ecec_team_leadership min_directors="3"]
```

Attributes:
- `min_directors` — minimum director rows to show; placeholder rows fill any gap. Default `3` (so 1 founder + 3 director placeholders = 4 cards = 2 rows of 2 in the 2-up grid).

Renders all `founder` tier records first (each as a 2-up row entry with photo + bio), then all `director` tier records, then placeholder rows up to `min_directors`. If no founder AND no director AND `min_directors=0`, returns an empty string (the section disappears).

**Implementation gotcha:** when computing `placeholders_needed = $min - $real_count`, use `count($q->posts)` — `$q->found_posts` returns 0 if the WP_Query was built with `no_found_rows=true`.

## Data model

- Post type: `ecec_team_member`
- Public: `false` (no singular pages, no archive, not in search)
- Supports: title, editor, thumbnail, page-attributes
- Meta fields:
	- `_ecec_team_role` (string)
	- `_ecec_team_locations` (string, `|`-separated)
	- `_ecec_team_tier` (enum: `founder` | `director` | `senior` — default `senior`; added 2026-05-02)
	- `_ecec_team_bio` (sanitized textarea, 100-150 word narrative, only displayed for founder/director tiers; added 2026-05-02)
	- `_ecec_team_department` (enum: `business-development` | `design-team` | `project-management` | `administration` — empty by default; required for senior tier to appear on the page; added 2026-05-17)

## Deploying to VPS staging

Files to upload via SFTP:
- `wp-content/themes/emaurri-child/functions.php`
- `wp-content/themes/emaurri-child/style.css`

DB changes (run `_deploy/setup_team_and_people_page.php` against the VPS):
- Seeds the Khaled Al Assi team member (skips if any already exist)
- Swaps the People page container `5614a79` children for `[ecec_team_grid]`

After deploy:
- `chown -R www-data:www-data /var/www/html/ecec/wp-content/themes/emaurri-child/`
- Clear Elementor caches for post 123

## Adding more members (client workflow)

1. Go to **WP Admin → Team Members → Add New Member**
2. Enter the person's name as the title
3. Pick the **Tier**:
	- `Senior team` — appears in a department grid (which one depends on the Department field — see step 4)
	- `Director` — appears as a full-width image + bio row in the Our Leaders section
	- `Founder / Managing Director` — appears at the top of Our Leaders, before directors
4. **Department** (Senior team only) — pick one of: Business Development / Design Team / Project Management / Administration. If left blank, the senior-tier member will NOT appear on the page.
5. Fill in **Role**, **Office Locations**, and (for founder/director only) the **Bio** narrative — recommended 100-150 words
6. Click **Set featured image** and upload a photo (ideally square, 500×500 or larger)
7. In **Page Attributes → Order**, enter a number (1 = first, 2 = second, etc.) — ordering is per-section: Founder/Director rows alternate sides starting from the lowest order, and each department grid orders independently
8. **Publish**
9. Done — they appear on `/people/` immediately. Founder/Director records replace placeholder rows in the leadership section; senior-team records fill out their assigned department grid.

## Gotchas

- Photos are cropped to square via CSS `object-fit: cover`. Landscape/portrait photos will crop — advise the client to upload square-ish images.
- `menu_order` defaults to `0` for newly created posts. Two members with order `0` will fall back to alphabetical by title. Set explicit orders if precedence matters.
- The CPT is marked `public: false` — no single-member page, no archive, no search inclusion. If the client later wants single pages per person, flip `public`, `publicly_queryable`, and `has_archive`.
- Post ID `123` for the People page is hard-coded in `_deploy/setup_team_and_people_page.php`. No runtime check, it's only used during deployment.

## Quick removal (if client rejects)

1. Delete the CPT block from `functions.php` (everything between `// ─── Team Members CPT ───` and the end of the team-grid shortcode).
2. Restore the old Elementor widgets on container `5614a79` of post 123 from a backup, or re-enter an image + heading + text-editor manually.
3. Remove the `.ecec-team-*` CSS block from `style.css`.
4. Optionally delete the `ecec_team_member` posts (they'll be orphaned but harmless).
