# Single-Project Content Blocks — SHIPPED

**Status:** Built and deployed to local + VPS staging. Live as of **2026-04-24**.
Migration of the remaining 53 projects to the block system is still **pending** (see "Next up" at the bottom).

Each portfolio-item single page is now assembled from stackable **content blocks** managed from WP admin. Seven block types cover text, images, galleries, quotes and data. Editors build pages by picking block types from a dropdown, filling fields, and dragging cards to reorder — no Elementor, no coding.

Reference layout matches lwkp.com/projects/difc-living. Screenshots saved at `docs/proj.png`, `docs/proj1.png`, `docs/proj2.png`.

## Why this path (Path B, chosen 2026-04-21)

| Path | Approach | Why not |
|---|---|---|
| A | Elementor per project | 54 projects × Elementor editing is unsustainable for the client |
| B | Custom section-blocks metabox | **Chosen.** Consistent look, self-serve admin, scales. |
| C | Fixed 4-image template (Duaa's original ask) | Too rigid; lwkp reference has much richer content |

## Block types (7 shipped)

| Block | Fields | Frontend render |
|---|---|---|
| **Text paragraph** | body (blank lines = paragraphs, HTML allowed) | Body copy, max-width contained. `wpautop` wraps paragraphs. |
| **Full-width image** | `image_id`, optional caption | Full-width image with subtle caption below |
| **Image pair** | `image_id_left`, `image_id_right`, captions | Two side-by-side images (added v1, not in original 6-block plan) |
| **Image + text split** | `image_id`, `overline`, `heading`, `body`, `image_side` (left\|right) | 50/50 two-column, image on left or right |
| **Project data** | `overline`, `heading`, `rows` (repeater of label/value) | "PROJECT DATA" strip, facts list |
| **Pull quote** | `quote`, `attribution` | Large italic, indented |
| **Gallery** | `image_ids[]`, `columns` (2 or 3) | Grid, drag-sort thumbs in admin |

Deferred to v2: YouTube/Vimeo embed, standalone heading, spacer, gallery lightbox.

## Data model

- **Meta key:** `_ecec_blocks` on post type `portfolio-item`
- **Value:** JSON-encoded array of block objects, one per entry
- **Order:** array order = render order (client drags cards to reorder)
- **Fallback:** if `_ecec_blocks` is empty, the template falls back to the legacy `post_content` + `qodef_portfolio_media` grid so non-migrated projects still render

Example:

```json
[
  { "type": "image-text-split", "image_id": 107, "overline": "VISION & CONTEXT",
    "heading": "Redefining the desert experience", "body": "…", "image_side": "left" },
  { "type": "pull-quote", "quote": "A paragraph-length quote…", "attribution": "Client, Title" },
  { "type": "gallery", "image_ids": [201, 202, 203, 204], "columns": 3 }
]
```

## Template files

```
wp-content/themes/emaurri-child/
├── single-portfolio-item.php              ← child theme override (beats plugin template
│                                            via standard WP hierarchy)
└── template-parts/blocks/
    ├── text-paragraph.php
    ├── full-image.php
    ├── image-pair.php
    ├── image-text-split.php
    ├── project-data.php
    ├── pull-quote.php
    └── gallery.php
```

Flow in `single-portfolio-item.php`:

1. Hero — featured image + title + breadcrumb (60vh, gradient overlay)
2. 2-column body: blocks stream on the left, sticky info sidebar on the right (reads existing `qodef_portfolio_info_items` meta, no migration)
3. Loop: `foreach ($blocks as $block) { get_template_part('template-parts/blocks/' . $block['type'], null, ['block' => $block]); }`
4. Footer navigation (prev/next project)

CSS: appended to `style.css` under section "Single-Project Page (block system)". Responsive: single-column below 900px.

## Admin UX — drag-sort repeater (shipped 2026-04-24)

The v1 plan proposed a JSON textarea PoC to be upgraded to a drag-sort UI after visual sign-off. The drag-sort UI shipped 2026-04-24; the JSON textarea is now hidden (source of truth, still submitted with the form so `ecec_blocks_save` sanitizer is unchanged).

Assets:

- `wp-content/themes/emaurri-child/assets/admin-blocks-repeater.js` (~475 lines, jQuery + jquery-ui-sortable + wp.media)
- `wp-content/themes/emaurri-child/assets/admin-blocks-repeater.css` (~230 lines, scoped under `#ecec-blocks-repeater`)

Features:

- Toolbar at the top with "Add block: <dropdown>" + Add button
- Stacked cards below, each with drag handle + type label + summary + **Edit** / **Duplicate** / **×** actions
- Click **Edit** → card expands to show type-specific fields
- **Duplicate** deep-clones the block and inserts it right after, auto-expands, scrolls into view
- Image fields use the WordPress media modal; thumbnails pre-resolved via `wp_localize_script` on initial render (no AJAX flash)
- Gallery block: multi-select media modal, sortable thumb grid, × to remove each
- Project-data block: inner sortable list of label/value rows with add/remove row buttons
- Status: valid/invalid JSON indicator removed — not needed once the UI owns state
- Summary line updates on every keystroke so editors can scan the stack without opening cards
- JS enqueued only on `portfolio-item` edit screens (`post.php` / `post-new.php` guarded by `get_current_screen()->post_type`)

Save handler (`ecec_blocks_save` on `save_post_portfolio-item`) is per-field sanitized: `wp_kses_post` for rich fields (body, quote), `sanitize_text_field` for plain fields, `(int)` for image IDs, forced valid enum for `image_side` / `columns`.

## Clone a project (shipped 2026-04-24)

Two entry points:

1. **Portfolio admin list** → each row's action menu (Edit / Quick Edit / Trash / View / **Clone as draft**). Filter hook: `post_row_actions`.
2. **Project edit screen** → Publish box in the sidebar has **Clone as draft** with a helper line and a `confirm()` prompt warning unsaved changes won't be copied. Hook: `post_submitbox_misc_actions`. Hidden on auto-drafts.

Both go to `admin-post.php?action=ecec_duplicate_portfolio&post=X` (nonce-checked). Handler:

- Creates a new `portfolio-item` with status `draft`, title suffixed " (Copy)"
- Copies content, excerpt, menu_order, comment_status, ping_status
- Copies all post meta — including `_ecec_blocks`, `qodef_portfolio_info_items`, `qodef_portfolio_media`, `_thumbnail_id`, `_ecec_featured`. Skips `_edit_lock` / `_edit_last`. Uses `maybe_unserialize` to avoid double-serialization.
- Copies all taxonomies (portfolio-category, portfolio-location, post_tag, etc.) via `wp_set_object_terms`
- Redirects to the new draft's edit screen

## Help page

`project-blocks-help.html` at the web root — animated, single-file HTML guide covering the whole editor experience:

- 15 sections (what's new, editor anatomy, 7 block types, add/edit/reorder/duplicate/remove, image & gallery, project-data rows, mark featured, clone project, create from scratch, troubleshooting)
- Sticky TOC sidebar with active-link highlighting on scroll
- Scroll-triggered fade-ins via IntersectionObserver
- Looping CSS `@keyframes` demos for add-block, edit-expand, reorder, duplicate, gallery-swap
- Brand palette + Plus Jakarta Sans, matching the rest of the ECEC admin guides
- Respects `prefers-reduced-motion`

Accessible at:
- Local: http://localhost/ecec/project-blocks-help.html
- VPS: http://207.180.196.39/ecec/project-blocks-help.html

## What changed from the original plan

| Original plan (2026-04-21) | What shipped |
|---|---|
| 6 block types | 7 — added **Image pair** (side-by-side) |
| JSON textarea PoC, drag-sort UI later | Drag-sort UI shipped directly after visual sign-off |
| Admin help: inline panel + Part E in portfolio-help.html | Standalone animated `project-blocks-help.html` instead |
| Info sidebar: "keep as-is or integrate into Project Data block?" | **Kept as-is.** `qodef_portfolio_info_items` still drives the sidebar. |
| Migrate all 54 projects during build | Migration **deferred** — only Desert Rock Resort seeded as the reference project |
| No project clone feature in scope | Added — "Clone as draft" from list + edit page |

## Migration — SHIPPED 2026-04-24 afternoon

Every portfolio-item without `_ecec_blocks` was converted to a single text-paragraph block seeded from its `post_content`. No gallery block was generated — investigation showed `qodef_portfolio_media` holds only ONE image per project and that image is identical to `_thumbnail_id` (the hero). Creating a Gallery from it would have duplicated the hero on the page.

### Script: `_deploy/migrate_existing_to_blocks.php`

- Dual-mode: HTTP (admin-auth required) and CLI (via `sudo -u www-data php ...`)
- Modes: `dry` (preview, no writes) · `confirm` (apply) · `undo` (rollback)
- Idempotent: skips projects that already have `_ecec_blocks` — Desert Rock Resort's handcrafted blocks are preserved
- Sets a provenance marker (`_ecec_blocks_migrated_at` = ISO timestamp) so `undo` can safely target only migrated projects and never touch handcrafted ones
- PowerShell driver: `_deploy/deploy_migrate_blocks.ps1` — uploads the script, dumps `wp_postmeta` for portfolio-items as a rollback safety net, runs via PHP CLI over SSH, verifies counts

### Results
- **Local:** 53 migrated, 1 skipped (Desert Rock), 54 total
- **VPS:** 80 migrated, 1 skipped (Desert Rock), 81 public portfolio-items with blocks. Total portfolio-items on VPS = 100 (19 are drafts/trash, ignored). Client added 27 new projects directly via VPS admin since last local sync — local is now out of sync.

Rollback command (VPS): `powershell.exe -ExecutionPolicy Bypass -File _deploy/deploy_migrate_blocks.ps1 -Undo`.

### Gallery lightbox + YouTube embed — v2

Deferred. Client hasn't asked for either yet.

## Deploy

Files (all admin-only, no DB changes):

- `wp-content/themes/emaurri-child/functions.php` — metabox render, save handler, enqueue hook, clone feature
- `wp-content/themes/emaurri-child/assets/admin-blocks-repeater.js`
- `wp-content/themes/emaurri-child/assets/admin-blocks-repeater.css`
- `wp-content/themes/emaurri-child/single-portfolio-item.php`
- `wp-content/themes/emaurri-child/template-parts/blocks/*.php`
- `wp-content/themes/emaurri-child/style.css` (appended block-system CSS)
- `project-blocks-help.html` (web root)

Deploy script: `_deploy/deploy_blocks_ui.ps1` — Posh-SSH via PowerShell. Must run as `powershell.exe -ExecutionPolicy Bypass -File _deploy/deploy_blocks_ui.ps1`.

Latest deploy backup: `/root/backups/functions_prerun_20260424_0618.php` + `/root/backups/emaurri-child_prerun_20260424_0618.tar.gz`. All 4 MD5s verified matching between local and VPS.

## Reference

- lwkp DIFC Living: https://www.lwkp.com/projects/difc-living — source layout
- `docs/proj.png` — hero split (image left / vision & context right)
- `docs/proj1.png` — full-width aerial + Project Data facts section
- `docs/proj2.png` — pull quote + Project Gallery
- Emaurri theme default single-portfolio-item (overridden by our child theme): `wp-content/plugins/emaurri-core/inc/post-types/portfolio/templates/single-portfolio-item.php`
- Reference single project (live): http://207.180.196.39/ecec/portfolio-item/desert-rock-resort/
