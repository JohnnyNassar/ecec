# Year Timeline Filter — REMOVED 2026-04-21

**Status:** removed from the Projects page on 2026-04-21 per client request. The document below describes what it was and where traces remain, so future work doesn't get confused.

## What it was

A horizontal bar above the projects grid with one dot per year (2010, 2016–2024). Each dot's size was proportional to the number of projects from that year. Clicking a dot filtered the grid to projects from that year, stacking with the left category sidebar and (later) the horizontal search bar.

Rendered via `[ecec_year_timeline]` shortcode, which was placed in post 122's Elementor data between the hero and the portfolio list.

## Why it was removed

Client feedback on 2026-04-21: the Projects page layout was being redesigned to match [lwkp.com/projects](https://www.lwkp.com/projects), which has no year timeline concept. The year filter was deemed visual noise against the new hero + Featured/Recent layout.

Removal was done via `_deploy/remove_year_timeline_from_projects.php`, which strips the `[ecec_year_timeline]` widget from the Elementor JSON on post 122. Idempotent.

## What still exists in the code

For safety, the **implementation code was NOT deleted** — only the shortcode placement on the page. If the client reverses the decision, adding the bar back is a one-line Elementor insert.

Still present in `wp-content/themes/emaurri-child/`:

| File / section | Status |
|---|---|
| `functions.php` — `ecec_get_portfolio_year_map()`, `ecec_year_timeline_shortcode()`, `ecec_enqueue_year_timeline_assets()` | Active (shortcode still registered; enqueues on post 122 regardless) |
| `assets/timeline.js` | Active — delegates year state to `window.ECEC_ProjectFilter` coordinator if present |
| `style.css` — `/* PROJECTS PAGE - Year Timeline Filter */` block | Active (styles apply only if `.ecec-year-timeline` element renders) |

The filter coordinator (`project-filter.js`) still has year-intersection logic. `timeline.js` no longer owns an apply pass — it only toggles the active class on year dots and calls `coord.apply()`.

## To fully purge

If the client confirms the feature is permanently dead:

1. Remove the block between `// ─── Projects Year Timeline Filter ───` and the end of the enqueue function in `functions.php`
2. Delete `assets/timeline.js`
3. Remove the `/* PROJECTS PAGE - Year Timeline Filter */` CSS block from `style.css`
4. Remove `ecec-year-timeline` from the enqueue dependency list (was: `array('ecec-project-filter')` → dropping the enqueue entirely)
5. Drop the year-matching code from `project-filter.js` (`matchesYear`, `state.year`)

Nothing else in the codebase depends on these.

## To re-enable

```
# Re-run the original deploy script that injects the shortcode above the portfolio list:
php _deploy/add_year_timeline_to_projects.php
```

This will insert `[ecec_year_timeline]` above the `[emaurri_core_portfolio_list ...]` widget. Then clear Elementor caches.

## Related docs

- `projects-filter.md` — the current filter bar (search + type + location)
- `featured-projects.md` — the Featured/Recent layout that replaced the timeline as the top-of-page interest point
