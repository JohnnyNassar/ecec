# Projects Page — Horizontal Filter Bar

The Projects page has a single horizontal filter bar above the grid:

```
[ Search here ........... ] [ By Type ▾ ] [ By Location ▾ ] [ SEARCH ]
```

All four controls narrow the same grid. Typing/selecting filters live. Filters intersect with each other AND with the theme's left category sidebar — an item is visible only if every active filter matches.

## What the user sees

- **Search here** — free text. Matches against project title (case-insensitive, substring).
- **By Type** — portfolio category slug (Hospitality, Healthcare, Data Centers, …).
- **By Location** — country (Saudi Arabia, UAE, Jordan, Other). See `portfolio-location` taxonomy below.
- **SEARCH** — manual trigger. Filtering is already live, so the button is mostly cosmetic and confirms the action.

## Files

All lives in `wp-content/themes/emaurri-child/`:

| File | Purpose |
|---|---|
| `functions.php` | Registers `portfolio-location` taxonomy, `[ecec_project_search]` shortcode, and enqueues the coordinator JS on post 122 only |
| `assets/project-filter.js` | The unified filter coordinator (see below) |
| `style.css` | Bar styling under `/* PROJECTS PAGE - Search / Type / Location bar */` |

## The `portfolio-location` taxonomy

Registered on `portfolio-item`. Four seeded terms:

| Slug | Name |
|---|---|
| `saudi-arabia` | Saudi Arabia |
| `uae` | UAE |
| `jordan` | Jordan |
| `other` | Other |

Client can add/rename terms through **Portfolio → Locations** in WP admin. The shortcode enforces a canonical display order (KSA → UAE → Jordan → Other) via a `$preferred_order` array in the shortcode — any user-added terms append after those four.

WordPress's `post_class()` automatically adds `portfolio-location-<slug>` to every portfolio article anywhere on the site, same pattern as the existing `portfolio-category-<slug>`. The filter coordinator uses these classes directly — no localized data map needed.

## The coordinator (`project-filter.js`)

Single source of truth for filter state on the Projects page. State sources:

| Source | Read from |
|---|---|
| Search text | `.ecec-ps-input` (input event) |
| Type dropdown | `.ecec-ps-select[data-role="type"]` (change event) |
| Location dropdown | `.ecec-ps-select[data-role="location"]` (change event) |
| Sidebar category | `.qodef-m-filter-item.qodef--active` `data-filter` attribute |
| Year (legacy) | `window.ECEC_ProjectFilter.state.year`, set by `timeline.js` if present |

On any change, `apply()` runs over every `article.qodef-grid-item` and sets `display:''` or `display:'none'` based on the intersection of all five conditions. The year state hook is retained for backward compatibility even though `[ecec_year_timeline]` is no longer placed on the page — the feature was removed 2026-04-21 (see `year-timeline-filter.md`).

Public surface: `window.ECEC_ProjectFilter = { state, ready, apply }`. Other scripts can mutate `state.<field>` and call `apply()` to force a repass.

## Seeding locations for existing projects

`_deploy/seed_project_locations.php` is the source of truth for the initial project→location mapping. It:

1. Ensures the 4 canonical terms exist
2. Assigns terms per a hardcoded mapping of post ID → location slug
3. Is idempotent — re-running reapplies the same state without errors

Mapping logic: live-page scrape where available (Wyndham, Lime Box, Desert Rock, etc.) + inference from client and project names. Projects outside the KSA/UAE/Jordan scope (AUIB Baghdad ×2, Sixty Iconic Cairo) are assigned `other`.

Current breakdown as of 2026-04-21: **13 KSA / 38 UAE / 0 Jordan / 3 Other** = 54 projects.

To reassign one project, edit it in WP admin — the Locations metabox is on the edit screen sidebar.

## Adding or removing projects

- **New project with a location checked:** picks up its `portfolio-location-<slug>` class automatically. No code change needed.
- **New project without a location:** shows in the grid but won't match any *location* filter. If the user selects a location dropdown value, that project will be hidden.
- **New term added via admin:** appears in the `By Location` dropdown on next page load (after the canonical four). `hide_empty` is `false` on the term query, so zero-project terms still appear.

## Shortcode placement

Post 122 (Projects page) Elementor data now contains three containers:

1. **Hero** (full-width bg image + headline + subtitle + tabs strip) — see `featured-projects.md`
2. **Featured Projects** (`#featured-projects` anchor) — 2-col lwkp-style grid
3. **Recent Projects** (`#recent-projects` anchor) — PORTFOLIO overline, "Our Recent Projects" h2, the **`[ecec_project_search]` bar**, and `[emaurri_core_portfolio_list ... enable_filter="yes"]`

The filter bar and the theme's left category sidebar coexist — client kept both per 2026-04-21 feedback. The sidebar is the primary type filter for quick use; the top-bar `By Type` is redundant-but-expected per the snippet Duaa sent.

## Styling notes

- Inputs/selects: 56px height, silver-sand border, 6px radius, Argentum Sans font
- Button: raisin-black bg with white text, 0.2em letter-spacing, Plus Jakarta Sans
- Dropdown caret: inline SVG data-URI (no icon font dependency)
- Responsive: stacks at `max-width: 1024px` — search takes full row, dropdowns each half, button full row

## Gotchas

- **Page ID is hard-coded** to `122` in the enqueue check. If the Projects page ID ever changes, update `ecec_enqueue_year_timeline_assets()`.
- **Script order matters.** `ecec-project-filter` must load BEFORE `ecec-year-timeline` — enforced via `wp_enqueue_script(..., array('ecec-project-filter'), ...)` dependency.
- **`get_terms` default ordering is alphabetical** — that's why the shortcode reorders explicitly. Removing the reorder block would give you Jordan, Other, Saudi, UAE — not what the client wants.
- **Dropdown `value=""` is the "all" state.** `matches*()` functions short-circuit return true when the state is falsy. Empty string handling matters.

## Deploying changes

Files to SFTP-upload to VPS:
- `wp-content/themes/emaurri-child/functions.php`
- `wp-content/themes/emaurri-child/style.css`
- `wp-content/themes/emaurri-child/assets/project-filter.js`
- `wp-content/themes/emaurri-child/assets/timeline.js`

Plus DB state (one-time):
- Run `_deploy/seed_project_locations.php` on the server (upload to `/var/www/html/ecec/_tmp/` first)
- Run `_deploy/add_project_search_to_projects.php` to inject the shortcode

Then clear Elementor caches:
```sql
DELETE FROM wp_postmeta WHERE meta_key IN ('_elementor_element_cache','_elementor_css');
```
```bash
rm -rf wp-content/uploads/elementor/css/*
```

`_deploy/deploy_project_filter.ps1` is the Posh-SSH wrapper that does all of the above in one pass.

## See also

- `featured-projects.md` — the hero + Featured section above this filter bar (the other half of this redesign)
- `single-project-blocks.md` — **next feature in the pipeline** (not yet built). Will replace the default Emaurri single-project layout with a magazine-style block system.
