# Why ECEC — scrollytelling page

New page added 2026-04-25 mirroring Emaurri's `portfolio/vertical-divided-list` demo. Sticky-left titles + scrolling-right images. As the user scrolls, the visible title in the left column swaps to match the slide currently in viewport center.

- **Live URL:** http://207.180.196.39/ecec/why-ecec/
- **Post ID:** 422
- **Slug:** `/why-ecec/`
- **In nav:** position 3 (Home → About Us → **Why ECEC** → Projects → ...)
- **Theme page-title bar hidden** via CSS (page goes straight into the scroll content)

## What's on the page

5 hardcoded pillars (from the company profile PDF "Driving Innovation" section):

1. Proven Performance
2. Data-Driven Efficiency
3. Global Standards Local Insight
4. Collaborative Engagement
5. Comprehensive Expertise

Each pillar has:
- Numbered badge ("01", "02", ...)
- Title (Plus Jakarta Sans bold uppercase slate-gray, with letter/word spacing for unification)
- "Explore" CTA → `/projects/`

The right column shows a placeholder image per pillar (gray dashed-border box, 3:4 portrait aspect). To swap for real images, see "Replacing placeholder images" below.

## Architecture

```
[ecec_why] shortcode (functions.php)
    │
    ├── <div class="ecec-why-info-holder">  ← LEFT, position: sticky, top: 140px
    │     ├── .ecec-why-info-item.is-active (idx=0)  Proven Performance
    │     ├── .ecec-why-info-item (idx=1)             Data-Driven Efficiency
    │     ├── ...
    │     (only one visible at a time via opacity transition)
    │
    └── <div class="ecec-why-main">  ← RIGHT, scrolls normally
          ├── <article data-idx="0">  ← placeholder image
          ├── <article data-idx="1">
          ├── ...
```

`assets/why-ecec.js` runs an IntersectionObserver. As each `.ecec-why-slide` enters viewport center (rootMargin `-30% 0px -30% 0px`), the JS picks the closest one and sets `.is-active` on the matching info item.

## Files

| File | What's in it |
|---|---|
| `wp-content/themes/emaurri-child/functions.php` | `ecec_why_shortcode()` + `add_shortcode('ecec_why', …)` + per-page JS enqueue with `is_page('why-ecec')` |
| `wp-content/themes/emaurri-child/assets/why-ecec.js` | IntersectionObserver, swap-active logic |
| `wp-content/themes/emaurri-child/style.css` | `.ecec-why-*` classes, sticky overrides for `body.page-id-422` |
| `_deploy/build_why_ecec.php` | One-shot script that creates post 422 + sets Elementor data + adds to nav menu |
| `_deploy/fix_why_ecec.php` | Resaves Elementor data with the correct schema (used after the first attempt's `layout: full_width` broke Elementor's lazy render) |

## Critical CSS gotcha — position: sticky

Qode theme's `#qodef-page-wrapper` has `overflow: hidden`. This breaks `position: sticky` on every descendant — sticky resolves to the wrapper's scroll context which doesn't actually scroll. To make sticky work on this page, the CSS scopes an `overflow: visible !important` override to `body.page-id-422` on every wrapper id (`#qodef-page-wrapper`, `#qodef-page-inner`, `#qodef-page-outer`, `#qodef-page-content`, `#qodef-page-content-section`).

## Critical Elementor gotcha — `_elementor_edit_mode`

When creating the page programmatically, `_elementor_edit_mode` post meta MUST be `'builder'` for Elementor to render the `_elementor_data` on the frontend. `update_post_meta()` after `wp_insert_post()` sometimes silently fails (an Elementor save filter intervenes). The reliable fix is direct SQL:

```sql
INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
VALUES (422, '_elementor_edit_mode', 'builder')
ON DUPLICATE KEY UPDATE meta_value = 'builder';
```

When debugging an Elementor page that "isn't rendering" (200 OK but empty content area), check this first.

## Replacing placeholder images

The 5 image slots are currently **hardcoded** as placeholder divs in `ecec_why_shortcode()`. Two ways to swap for real images:

**Quick (5-min):** edit the foreach in `functions.php` to render `<img src="…" />` instead of the placeholder div. Upload the 5 images to `/wp-content/uploads/why-ecec/` and reference by URL.

**Better (~30-min refactor):** add a Settings page or use a `get_option()` call to fetch 5 attachment IDs the client can manage from WP admin. Then the client can swap images without touching code. Schema:

```php
$image_ids = get_option( 'ecec_why_pillar_images', [ 0, 0, 0, 0, 0 ] );
foreach ( $image_ids as $idx => $att_id ) {
    if ( $att_id ) {
        echo wp_get_attachment_image( $att_id, 'large', false, [ 'class' => 'ecec-why-image' ] );
    } else {
        echo '<div class="ecec-block-placeholder ecec-why-placeholder">…</div>';
    }
}
```

## Recommended image specs

- Portrait orientation
- ~600 × 800 px or larger
- JPEG, optimized (~150KB per image)

## Removing this page

If the client decides to drop Why ECEC entirely:
1. Delete post 422 (or move to draft).
2. Remove the menu item: `Appearance → Menus → Main Menu → Why ECEC → Remove`.
3. Optionally remove `ecec_why_shortcode()` + the JS enqueue block from `functions.php` and the `.ecec-why-*` CSS from `style.css`.
