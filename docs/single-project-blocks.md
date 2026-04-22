# Single-Project Content Blocks — PLANNING (not yet built)

**Status:** Plan parked 2026-04-21. Not built yet. Resume when client confirms scope.

Goal: give each portfolio-item single page a magazine-style layout matching lwkp.com/projects/difc-living. One template renders all 54 projects; per-project content comes from a structured repeater metabox the client manages from WP admin. No Elementor skill needed per-project.

See reference screenshots in `docs/proj.png`, `docs/proj1.png`, `docs/proj2.png` (from lwkp's DIFC Living page).

## Why this path (Path B)

Three paths were considered on 2026-04-21:

| Path | Approach | Rejected because |
|---|---|---|
| A | Elementor per project | 54 projects × Elementor editing is unsustainable for the client |
| B | Custom section-blocks metabox | **Chosen.** Consistent look, self-serve admin, scales. |
| C | Fixed 4-image template (Duaa's original ask) | Too rigid; lwkp reference has much richer content |

The client asked for the lwkp pattern, which means variable-length per-project content (some projects have 3 blocks, some have 10). Only Path B accommodates that while keeping the look uniform and the admin UX friendly.

## Block types — v1 proposed (6)

| Block | Fields client fills in | Frontend render |
|---|---|---|
| **Full image** | image, optional caption | Full-width image, subtle caption below |
| **Text paragraph** | rich text (WP editor) | Body-copy section, max-width contained |
| **Image + text split** | image, overline (small caps), heading, body, L/R toggle | Two-column 50/50, image on left by default |
| **Project data** | repeater of label + value rows (GFA: 56,000 sqm) | Overline "PROJECT DATA" + heading + facts list |
| **Pull quote** | quote text, optional attribution | Large italic, indented, ECEC typography |
| **Gallery** | multiple images | 2- or 3-col grid, lightbox on click |

Deferred for v2 (client ask): YouTube/Vimeo embed, standalone heading, spacer.

## Data model

- Single meta key: `_ecec_blocks` on `portfolio-item`
- Value: JSON-encoded array of block objects
- Shape: `[ { "type": "image_text_split", "image_id": 123, "overline": "VISION & CONTEXT", "heading": "Redefining...", "body": "...", "image_side": "left" }, ... ]`
- Order is array order (client drags to reorder in admin)

## Template strategy

Override single-portfolio-item via child theme:

```
wp-content/themes/emaurri-child/
├── single-portfolio-item.php        ← new override
├── template-parts/
│   └── blocks/
│       ├── full-image.php
│       ├── text-paragraph.php
│       ├── image-text-split.php
│       ├── project-data.php
│       ├── pull-quote.php
│       └── gallery.php
```

Flow in `single-portfolio-item.php`:
1. Hero (featured image + title + breadcrumb)
2. Loop over `_ecec_blocks` → `get_template_part('template-parts/blocks/' . $block['type'])`
3. Info sidebar (unchanged from theme default — Client/Contractor/Engineering/Completion Date)

## Migration of existing 54 projects

Run-once script: `_deploy/migrate_existing_to_blocks.php`. For each project, generate a default `_ecec_blocks` array:

1. First block: **Text paragraph** from the existing `post_content` (the description)
2. Second block: **Gallery** built from items in the existing `qodef_portfolio_media` repeater

Does NOT touch:
- Featured image (template renders it as the hero regardless)
- Info sidebar meta (`qodef_portfolio_info_items`)
- `post_content` (template ignores it; the migrated Text paragraph block is the source of truth going forward)

Dry-run flag so we preview before writing.

Rollback plan: SQL dump of `_ecec_blocks` meta + the entire `wp_postmeta` table for portfolio-items BEFORE the run, saved to `/root/backups/ecec_blocks_migration_<ts>.sql` on VPS.

## Admin UX

Custom metabox on the portfolio-item edit screen, replacing the theme's built-in Media repeater as the primary content tool. Info sidebar (Client, Contractor, Engineering) stays as-is in the existing Portfolio Settings → Info tab.

UI per block:
- Dropdown: "Block type"
- Fields appear below based on type (vanilla JS show/hide)
- Drag handle to reorder
- Remove button
- "Add block" button at bottom

No external deps — plain WP pattern with `wp_editor()` for rich text, `wp.media` for image pickers.

## Helper / onboarding — NOT YET DECIDED

Three options proposed, client to choose one:

| Option | What it is | Pros | Cons |
|---|---|---|---|
| Part E in portfolio-help.html | New steps 21-26 covering each block | Consistent with existing help | Help page is getting long |
| Standalone `portfolio-blocks-help.html` | Separate page linked from WP admin | Focused, shareable | Separate maintenance |
| Inline panel on edit screen | Collapsible "How to use content blocks" box at top of portfolio-item edit page | Help is right where needed | Slightly more code |

Current recommendation: **inline panel + Part E** — panel for in-context guidance, Part E for full reference.

## Open questions — RESUME HERE TOMORROW

Before writing a line of code, confirm with the client:

1. ✅ **Path B / block system chosen** — confirmed 2026-04-21
2. **6 block types as listed** — or add / remove any? Especially: do we need YouTube embed in v1?
3. **Auto-migrate existing 54 projects' content to blocks** — yes or no? If yes, the existing `post_content` description becomes a Text paragraph block and the Media repeater becomes a Gallery block.
4. **Helper format** — inline panel + Part E, standalone page, or just Part E?
5. **Info sidebar** — keep it as-is (Client/Contractor/Engineering in right sidebar) or integrate into a Project Data block?

## Effort estimate

~1 day of focused work:

| Task | Hours |
|---|---|
| Metabox + repeater + block-type dispatcher | 3-4 |
| 6 block-type field sets + save handlers | 2 |
| `single-portfolio-item.php` + 6 partial templates | 3 |
| CSS matching ECEC typography | 2 |
| Migration script (existing → blocks) | 2 |
| Helper content (inline panel + Part E) | 2 |

Local-only build first; deploy only after client sign-off on the visual result.

## Reference

- lwkp DIFC Living: https://www.lwkp.com/projects/difc-living — source layout
- `docs/proj.png` — hero split (image left / vision & context right)
- `docs/proj1.png` — full-width aerial + Project Data facts section
- `docs/proj2.png` — pull quote + Project Gallery start
- Emaurri theme default single-portfolio-item: `wp-content/plugins/emaurri-core/inc/post-types/portfolio/templates/single-portfolio-item.php` — we're overriding this
- Duaa's original simpler ask (still relevant if we ever do Path C instead): *"reduce images to 4, one above the text (largest), three below"*
