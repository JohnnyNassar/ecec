# Team Members — People Page

The People page showcases the ECEC team using a custom post type (CPT). The client can add, edit, reorder, or remove members from WordPress admin without touching Elementor.

## What the client sees in WP Admin

A new **Team Members** menu item in the sidebar (icon: groups) with:

- **All Team Members** — list view with photo, role, offices, and order columns
- **Add New Member** — a simple form with:
	- Title (the person's name)
	- Rich text editor (optional bio, not currently displayed on the card but available if the client asks)
	- **Team Member Details** box with:
		- Role / Title (e.g. "Founder | Principal")
		- Office Locations (e.g. "Dubai | Riyadh | Amman" — separate with `|`)
	- **Featured image** box — the member photo
	- **Page Attributes → Order** — lower numbers appear first

Saving publishes the member and they appear on the People page automatically (no Elementor edits needed).

## What the visitor sees on /people/

A responsive grid of cards above the existing "OUR EXPERT TEAM" / "WHO WE ARE" headings. Each card has:

- Photo (220px square, 12px rounded corners)
- Name (Plus Jakarta Sans, 20px)
- Role (uppercase, letter-spaced, slate-gray)
- Location(s)

Grid is 3 columns on desktop, 2 on tablet, 1 on mobile.

## Files

All changes live in `wp-content/themes/emaurri-child/`:

| File | What's in it |
|---|---|
| `functions.php` | CPT registration, meta box, save handler, admin list columns, `[ecec_team_grid]` shortcode |
| `style.css` | Card grid layout, photo styling, typography, responsive breakpoints |

Nothing in `assets/` is needed — pure CSS, no JS.

## Shortcode

```
[ecec_team_grid columns="3"]
```

Attributes:
- `columns` — `1`, `2`, `3`, or `4` (default `3`)
- `orderby` — any WP_Query orderby (default `menu_order`)
- `order` — `ASC` / `DESC` (default `ASC`)

Placed in the People page (post 123) inside container `5614a79`, replacing the previously hardcoded image + heading + text-editor widgets.

## Data model

- Post type: `ecec_team_member`
- Public: `false` (no singular pages, no archive, not in search)
- Supports: title, editor, thumbnail, page-attributes
- Meta fields:
	- `_ecec_team_role` (string)
	- `_ecec_team_locations` (string, `|`-separated)

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
3. Fill in **Role** and **Office Locations**
4. Click **Set featured image** and upload a photo (ideally square, 500×500 or larger)
5. In **Page Attributes → Order**, enter a number (1 = first, 2 = second, etc.)
6. **Publish**
7. Done — they appear on `/people/` immediately

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
