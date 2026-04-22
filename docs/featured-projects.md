# Featured Projects + Hero Section

The Projects page matches the [lwkp.com/projects](https://www.lwkp.com/projects) layout: full-width hero with image/headline, anchor-scroll tabs (FEATURED / RECENT PROJECTS), a Featured Projects 2-column grid, then the searchable Recent Projects grid below.

All admin-facing controls use standard WP UI — no Elementor knowledge needed for day-to-day content updates.

## What the user sees

```
┌────────────────────────────────────────────────────────┐
│  [ Full-width hero image with dark overlay ]           │
│                                                         │
│      Engineering the Systems Behind Great Places       │
│      For over two decades, ECEC has delivered…         │
└────────────────────────────────────────────────────────┘
                            FEATURED | RECENT PROJECTS
─────────────────────────────────────────────────────────
                                   #featured-projects ↓
Featured Projects

┌─────────────────────────┐ ┌─────────────────────────┐
│   [Desert Rock Resort]  │ │  [Royal Atlantis Resort]│
│   Desert Rock Resort    │ │  Royal Atlantis Resort  │
│   SAUDI ARABIA          │ │  UAE                    │
│   View Project →        │ │  View Project →         │
└─────────────────────────┘ └─────────────────────────┘
  (…and 4 more)
─────────────────────────────────────────────────────────
                                    #recent-projects ↓
PORTFOLIO
Our Recent Projects

[ Search here ........... ] [ By Type ] [ By Location ] [ SEARCH ]

[3-column grid of all 54 projects, filterable via bar + sidebar]
```

## Files

All under `wp-content/themes/emaurri-child/`:

| File | Purpose |
|---|---|
| `functions.php` | `_ecec_featured` metabox + save, admin column, `[ecec_featured_projects]` shortcode, `post_class` filter adding `.ecec-featured` |
| `style.css` | Sections: `PROJECTS PAGE - Hero / Tabs`, `PROJECTS PAGE - Featured Projects section`, featured badge overlay |

Plus deploy scripts in `_deploy/`:
- `seed_featured_projects.php` — marks the initial 6 projects as featured + sets their menu_order
- `add_hero_and_featured_to_projects.php` — injects hero container, tabs container, featured container into post 122; updates the existing recent container with anchor + headings
- `remove_year_timeline_from_projects.php` — strips `[ecec_year_timeline]` from the Elementor data
- `deploy_hero_featured.ps1` — Posh-SSH driver

## The "Featured" flag

Stored as `_ecec_featured = '1'` post meta on `portfolio-item` posts. Toggling is via:

- **A metabox** ("Featured project") in the sidebar of the portfolio-item edit screen, registered with `add_meta_box( 'ecec_featured_project', ..., 'side', 'high' )`
- **A star column** on the Portfolio list table (admin column + renderer hook) — yellow ★ if featured, em-dash otherwise

Save handler deletes the meta entirely when unchecked (doesn't write `'0'`), so `meta_query` with just `key + value='1'` stays correct.

A `post_class` filter adds the `ecec-featured` class to every portfolio-item article rendered anywhere on the site. This lets the CSS badge overlay apply automatically in the Recent grid without needing per-template edits.

## The `[ecec_featured_projects]` shortcode

```
[ecec_featured_projects columns="2"]
```

| Attribute | Default | Notes |
|---|---|---|
| `columns` | `2` | `1`, `2`, or `3`. Clamped. |
| `limit` | `-1` | How many featured items to show. `-1` = all. |

Query: `post_type=portfolio-item`, `meta_query key=_ecec_featured value='1'`, `orderby=menu_order ASC, date DESC`.

Card markup (per article):
```html
<article class="ecec-featured-card post-108 … ecec-featured">
  <a class="ecec-featured-media" href="…">[thumbnail]</a>
  <div class="ecec-featured-body">
    <h3 class="ecec-featured-title"><a href="…">Desert Rock Resort</a></h3>
    <p class="ecec-featured-meta">Saudi Arabia</p>  ← portfolio-location term name
    <a class="ecec-featured-link" href="…">View Project →</a>
  </div>
</article>
```

The meta line shows the **first `portfolio-location` term** attached to the project. If no location is assigned, the meta line is omitted entirely.

Empty state: if no featured items exist, the shortcode renders a small helper message explaining how to feature a project. The client never sees a silent empty section.

## The Featured badge

CSS-only `::before` pseudo-element on the image container of any article with `.ecec-featured`:

```
★ FEATURED  ← small black pill, top-left of the image
```

Two selectors target the badge:

- `.qodef-portfolio-list .qodef-grid-item.ecec-featured .qodef-e-image::before` — the Recent Projects theme grid
- `.ecec-featured-card .ecec-featured-media::before` — our custom Featured grid

And one suppressor — the badge is redundant *inside* the Featured section (every item there is featured):

```css
.ecec-featured-grid .ecec-featured-media::before { display: none; }
```

## The hero + tabs

Pure Elementor content, so the client can edit both through the page editor:

- **Hero container** — `content_width=full`, `min_height=60vh`, bg image = Desert Rock Resort's attachment + 50% black overlay, flex-centered. Contains two heading widgets (h1 + p subtitle).
- **Tabs container** — `content_width=boxed`, contains one HTML widget: `<nav class="ecec-projects-tabs">` with two anchor links (`#featured-projects`, `#recent-projects`).

Smooth-scroll is enabled globally via `html { scroll-behavior: smooth }`. Anchor targets get `scroll-margin-top: 120px` to offset under the sticky header.

The CSS identifier on each container is set via Elementor's `_element_id` setting:

| Container | `_element_id` (becomes `id="…"`) |
|---|---|
| Hero | `projects-hero` |
| Featured Projects | `featured-projects` |
| Recent Projects | `recent-projects` |

## Initial featured set

Seeded on first deploy via `seed_featured_projects.php`:

| Post ID | Project | menu_order | Sector | Country |
|---|---|---|---|---|
| 108 | Desert Rock Resort | 1 | Sustainability | KSA |
| 114 | Royal Atlantis Resort | 2 | Hospitality* | UAE |
| 112 | KAIA Terminal Complex | 3 | Governmental* | KSA |
| 60 | AMAALA Wellness Core Resort | 4 | Hospitality | KSA |
| 106 | Regalia Residential Tower | 5 | Residential | UAE |
| 38 | SEE Institute | 6 | Educational | UAE |

*Royal Atlantis and KAIA have incorrect `portfolio-category` assignments from the original theme migration (both tagged "sustainability") — pre-existing data issue, not introduced by this feature. Client should reassign in admin.

Re-running the seed script is safe (idempotent) but won't overwrite client-applied changes: `menu_order` is only set when the current order is `0`, so manual reorders survive re-seeding.

## How the client self-serves

All of the following happen in WP admin only — no code, no Elementor, no SFTP:

| Task | Where |
|---|---|
| Feature / unfeature a project | Portfolio → edit project → "Featured project" checkbox in sidebar, then Update |
| Reorder featured items | Portfolio → edit project → Page Attributes → Order (lower number = earlier). 1, 2, 3, … |
| See all featured at a glance | Portfolio → All Portfolio Items → scan the "Featured" column for ★ |
| Edit hero image | Pages → Projects → Edit with Elementor → click hero section → Style tab → Background image |
| Edit hero headline / subtitle | Same Elementor editor, click the text inline |
| Rename "Featured" / "Recent Projects" tabs | Same Elementor editor, click the HTML widget in the tabs container |

## Gotchas

- **`post_class` filter runs for every portfolio-item rendered** — including ones inside the single-project page. Badge CSS is scoped to `.qodef-portfolio-list .qodef-grid-item` and `.ecec-featured-card` to avoid appearing where it shouldn't.
- **The Emaurri Core list shortcode doesn't support `meta_query`** out of the box, which is why we wrote a custom `[ecec_featured_projects]` rather than parameterizing the existing list.
- **Hero attachment ID is set at inject time** from `get_post_thumbnail_id(108)` on whichever environment runs the script. Local and VPS have the same portfolio-item post IDs (unlike attachments), so the thumbnail lookup is consistent across environments.
- **Elementor `_element_id` is what becomes the `id` attribute** on the wrapper `<section>`. This is how the `#featured-projects` and `#recent-projects` anchors work.
- **`scroll-margin-top: 120px`** on anchor targets is tuned to the current sticky header height. If the header grows/shrinks, bump this value.

## Deploying changes

Same pattern as any child-theme change: upload changed files, clear Elementor caches, chown, verify.

Deploy helper: `_deploy/deploy_hero_featured.ps1` handles it end to end, including a Phase-8 HTTP verify pass with 12 content checks.

## See also

- `projects-filter.md` — the search bar inside the Recent Projects section (the other half of this redesign)
- `single-project-blocks.md` — **next feature in the pipeline** (not yet built). Will replace the default Emaurri single-project layout with a magazine-style block system so each project page can match lwkp.com/projects/difc-living
