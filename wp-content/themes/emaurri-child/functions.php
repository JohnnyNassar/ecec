<?php

if ( ! function_exists( 'emaurri_child_theme_enqueue_scripts' ) ) {
	/**
	 * Function that enqueue theme's child style
	 */
	function emaurri_child_theme_enqueue_scripts() {
		$main_style = 'emaurri-main';

		wp_enqueue_style( 'emaurri-child-style', get_stylesheet_directory_uri() . '/style.css', array( $main_style ) );
	}

	add_action( 'wp_enqueue_scripts', 'emaurri_child_theme_enqueue_scripts' );
}

// Footer layout: 3-column top area + 1-column bottom bar
function ecec_set_footer_columns( $config ) {
	$config['footer_top_sidebars_number']    = 3;
	$config['footer_bottom_sidebars_number'] = 1;
	return $config;
}
add_filter( 'emaurri_filter_page_footer_sidebars_config', 'ecec_set_footer_columns', 20 );

// ─── ECEC Brand Fonts ───────────────────────────────────────────────
// Replace default theme fonts with brand fonts:
// Plus Jakarta Sans (headings/UI) + Tajawal (Arabic) via Google Fonts
// Argentum Sans (body) via CDNFonts
add_filter( 'emaurri_filter_google_fonts_list', function () {
	return array( 'Plus Jakarta Sans', 'Tajawal' );
} );

function ecec_enqueue_brand_fonts() {
	wp_enqueue_style(
		'ecec-argentum-sans',
		'https://fonts.cdnfonts.com/css/argentum-sans',
		array(),
		null
	);
}
add_action( 'wp_enqueue_scripts', 'ecec_enqueue_brand_fonts', 5 );

// ─── Projects Year Timeline Filter ──────────────────────────────────
// [ecec_year_timeline] — renders a horizontal timeline above the portfolio grid.
// See docs/year-timeline-filter.md for details.

function ecec_get_portfolio_year_map() {
	static $cache = null;
	if ( $cache !== null ) return $cache;
	global $wpdb;
	$rows = $wpdb->get_results(
		"SELECT ID, YEAR(post_date) AS y
		 FROM {$wpdb->posts}
		 WHERE post_type='portfolio-item' AND post_status='publish'"
	);
	$map = array();
	foreach ( $rows as $r ) $map[ (int) $r->ID ] = (int) $r->y;
	return $cache = $map;
}

function ecec_year_timeline_shortcode() {
	$map = ecec_get_portfolio_year_map();
	if ( empty( $map ) ) return '';
	$counts = array();
	foreach ( $map as $y ) {
		if ( ! $y ) continue;
		$counts[ $y ] = isset( $counts[ $y ] ) ? $counts[ $y ] + 1 : 1;
	}
	ksort( $counts );
	$years = array_keys( $counts );
	$max   = max( $counts );
	$total = array_sum( $counts );

	ob_start(); ?>
	<div class="ecec-year-timeline" role="tablist" aria-label="Filter projects by year">
		<button type="button" class="ecec-year-all qodef--active" data-year="all">
			<span class="ecec-year-all-label">All Years</span>
			<span class="ecec-year-all-count"><?php echo esc_html( $total ); ?> projects</span>
		</button>
		<div class="ecec-year-track">
			<div class="ecec-year-line"></div>
			<ol class="ecec-year-nodes">
			<?php foreach ( $years as $y ) :
				$c = $counts[ $y ];
				// Dot size scales 10–22px based on project count
				$size = 10 + round( 12 * ( $c / $max ) );
			?>
				<li>
					<button type="button"
						class="ecec-year-node"
						data-year="<?php echo esc_attr( $y ); ?>"
						aria-label="<?php echo esc_attr( sprintf( '%d — %d projects', $y, $c ) ); ?>">
						<span class="ecec-year-dot" style="width:<?php echo (int) $size; ?>px;height:<?php echo (int) $size; ?>px;"></span>
						<span class="ecec-year-label"><?php echo esc_html( $y ); ?></span>
						<span class="ecec-year-count"><?php echo esc_html( $c ); ?></span>
					</button>
				</li>
			<?php endforeach; ?>
			</ol>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ecec_year_timeline', 'ecec_year_timeline_shortcode' );

// Enqueue timeline JS only on the Projects page (post 122), with localized year map
function ecec_enqueue_year_timeline_assets() {
	if ( ! is_page( 122 ) ) return;
	// Coordinator loads first so timeline.js can delegate its apply pass to it
	wp_enqueue_script(
		'ecec-project-filter',
		get_stylesheet_directory_uri() . '/assets/project-filter.js',
		array(),
		'1.0.0',
		true
	);
	wp_enqueue_script(
		'ecec-year-timeline',
		get_stylesheet_directory_uri() . '/assets/timeline.js',
		array( 'ecec-project-filter' ),
		'1.1.0',
		true
	);
	wp_localize_script( 'ecec-year-timeline', 'ECEC_YEAR_MAP', ecec_get_portfolio_year_map() );
}
add_action( 'wp_enqueue_scripts', 'ecec_enqueue_year_timeline_assets', 20 );

// ─── Featured Projects ──────────────────────────────────────────────
// Boolean _ecec_featured meta on portfolio-item + metabox checkbox.
// Used by [ecec_featured_projects] and by the auto-applied featured badge
// (CSS on .ecec-featured class added to post_class() of marked projects).

function ecec_featured_meta_box() {
	add_meta_box(
		'ecec_featured_project',
		'Featured project',
		'ecec_featured_meta_box_html',
		'portfolio-item',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'ecec_featured_meta_box' );

function ecec_featured_meta_box_html( $post ) {
	wp_nonce_field( 'ecec_featured_save', 'ecec_featured_nonce' );
	$on = get_post_meta( $post->ID, '_ecec_featured', true ) === '1';
	?>
	<label style="display:flex;gap:8px;align-items:center;">
		<input type="checkbox" name="ecec_featured" value="1" <?php checked( $on ); ?>>
		<span>Show in the <strong>Featured Projects</strong> section on the Projects page</span>
	</label>
	<p style="color:#666;font-size:12px;margin:10px 0 0;">
		A star badge appears on the project card wherever it's displayed.
		Use the <strong>Page Attributes &rarr; Order</strong> field to control featured order
		(lower numbers appear first).
	</p>
	<?php
}

function ecec_featured_save_meta( $post_id ) {
	if ( ! isset( $_POST['ecec_featured_nonce'] )
		|| ! wp_verify_nonce( $_POST['ecec_featured_nonce'], 'ecec_featured_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	if ( ! empty( $_POST['ecec_featured'] ) ) {
		update_post_meta( $post_id, '_ecec_featured', '1' );
	} else {
		delete_post_meta( $post_id, '_ecec_featured' );
	}
}
add_action( 'save_post_portfolio-item', 'ecec_featured_save_meta' );

// Add ecec-featured class to featured portfolio cards wherever post_class() is called.
// This makes the badge appear in both the Featured section AND the Recent grid.
add_filter( 'post_class', function ( $classes, $class, $post_id ) {
	if ( get_post_type( $post_id ) !== 'portfolio-item' ) return $classes;
	if ( get_post_meta( $post_id, '_ecec_featured', true ) === '1' ) {
		$classes[] = 'ecec-featured';
	}
	return $classes;
}, 10, 3 );

// Admin column + toggle on Portfolio list table
add_filter( 'manage_portfolio-item_posts_columns', function ( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		$new[ $k ] = $v;
		if ( $k === 'title' ) $new['ecec_featured'] = 'Featured';
	}
	return $new;
} );
add_action( 'manage_portfolio-item_posts_custom_column', function ( $col, $post_id ) {
	if ( $col !== 'ecec_featured' ) return;
	$on = get_post_meta( $post_id, '_ecec_featured', true ) === '1';
	echo $on
		? '<span style="color:#e0a300;font-size:18px;" title="Featured">&#9733;</span>'
		: '<span style="color:#ccc;">&mdash;</span>';
}, 10, 2 );

// [ecec_featured_projects] — 2-column grid of featured portfolio items.
// LWKP-style card: image, title, location (taxonomy term), "View Project →" link.
function ecec_featured_projects_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'columns' => '2',
		'limit'   => '-1',
	), $atts, 'ecec_featured_projects' );

	$q = new WP_Query( array(
		'post_type'      => 'portfolio-item',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $atts['limit'],
		'meta_query'     => array( array(
			'key'     => '_ecec_featured',
			'value'   => '1',
		) ),
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'no_found_rows'  => true,
	) );
	if ( ! $q->have_posts() ) return '<p class="ecec-featured-empty">No featured projects yet. Tick the <strong>Featured project</strong> checkbox on a portfolio item to add one.</p>';

	$cols = max( 1, min( 3, (int) $atts['columns'] ) );
	ob_start(); ?>
	<div class="ecec-featured-grid ecec-featured-cols-<?php echo (int) $cols; ?>">
	<?php while ( $q->have_posts() ) : $q->the_post();
		$loc_terms = get_the_terms( get_the_ID(), 'portfolio-location' );
		$loc_name  = ( $loc_terms && ! is_wp_error( $loc_terms ) ) ? $loc_terms[0]->name : '';
		?>
		<article <?php post_class( 'ecec-featured-card' ); ?>>
			<a class="ecec-featured-media" href="<?php the_permalink(); ?>">
				<?php if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'large', array( 'loading' => 'lazy', 'class' => 'ecec-featured-img' ) );
				} ?>
			</a>
			<div class="ecec-featured-body">
				<h3 class="ecec-featured-title">
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</h3>
				<?php if ( $loc_name ) : ?>
					<p class="ecec-featured-meta"><?php echo esc_html( $loc_name ); ?></p>
				<?php endif; ?>
				<a class="ecec-featured-link" href="<?php the_permalink(); ?>">View Project <span aria-hidden="true">&rarr;</span></a>
			</div>
		</article>
	<?php endwhile; wp_reset_postdata(); ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ecec_featured_projects', 'ecec_featured_projects_shortcode' );

// ─── Projects Portfolio Location Taxonomy ───────────────────────────
// Registers portfolio-location on portfolio-item, with 3 seeded terms
// (Saudi Arabia, UAE, Jordan) plus "Other" for projects outside those.

function ecec_register_portfolio_location_taxonomy() {
	register_taxonomy( 'portfolio-location', array( 'portfolio-item' ), array(
		'labels' => array(
			'name'          => 'Locations',
			'singular_name' => 'Location',
			'menu_name'     => 'Locations',
			'all_items'     => 'All Locations',
			'edit_item'     => 'Edit Location',
			'view_item'     => 'View Location',
			'update_item'   => 'Update Location',
			'add_new_item'  => 'Add New Location',
			'new_item_name' => 'New Location Name',
			'search_items'  => 'Search Locations',
			'not_found'     => 'No locations found.',
		),
		'public'             => true,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_admin_column'  => true,
		'show_in_menu'       => true,
		'show_in_rest'       => true,
		'hierarchical'       => false,
		'rewrite'            => false,
		'capabilities'       => array(
			'manage_terms' => 'manage_options',
			'edit_terms'   => 'manage_options',
			'delete_terms' => 'manage_options',
			'assign_terms' => 'edit_posts',
		),
	) );
}
add_action( 'init', 'ecec_register_portfolio_location_taxonomy' );

// ─── Projects Search Bar ────────────────────────────────────────────
// [ecec_project_search] — horizontal filter bar above the portfolio grid.
// Fields: Search text / By Type (portfolio-category) / By Location
// (portfolio-location) / SEARCH button. Stacks with year timeline and
// left sidebar via project-filter.js coordinator.

function ecec_project_search_shortcode() {
	$types = get_terms( array(
		'taxonomy'   => 'portfolio-category',
		'hide_empty' => true,
	) );
	$locations_raw = get_terms( array(
		'taxonomy'   => 'portfolio-location',
		'hide_empty' => false,
	) );
	// Canonical display order for the dropdown
	$preferred_order = array( 'saudi-arabia', 'uae', 'jordan', 'other' );
	$locations = array();
	if ( ! is_wp_error( $locations_raw ) ) {
		$by_slug = array();
		foreach ( $locations_raw as $l ) $by_slug[ $l->slug ] = $l;
		foreach ( $preferred_order as $slug ) {
			if ( isset( $by_slug[ $slug ] ) ) {
				$locations[] = $by_slug[ $slug ];
				unset( $by_slug[ $slug ] );
			}
		}
		// Append any custom-added terms after the canonical four
		foreach ( $by_slug as $l ) $locations[] = $l;
	}
	ob_start(); ?>
	<form class="ecec-project-search" role="search" aria-label="Filter projects" onsubmit="return false;">
		<div class="ecec-ps-field ecec-ps-field--search">
			<input type="search"
				class="ecec-ps-input"
				placeholder="Search here"
				aria-label="Search projects">
		</div>
		<div class="ecec-ps-field ecec-ps-field--select">
			<select class="ecec-ps-select" data-role="type" aria-label="Filter by type">
				<option value="">By Type</option>
				<?php if ( ! is_wp_error( $types ) ) foreach ( $types as $t ) : ?>
					<option value="<?php echo esc_attr( $t->slug ); ?>"><?php echo esc_html( $t->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="ecec-ps-field ecec-ps-field--select">
			<select class="ecec-ps-select" data-role="location" aria-label="Filter by location">
				<option value="">By Location</option>
				<?php if ( ! is_wp_error( $locations ) ) foreach ( $locations as $l ) : ?>
					<option value="<?php echo esc_attr( $l->slug ); ?>"><?php echo esc_html( $l->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<button type="button" class="ecec-ps-button" data-role="search">SEARCH</button>
	</form>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ecec_project_search', 'ecec_project_search_shortcode' );


// ─── Team Members CPT ───────────────────────────────────────────────
// Custom post type for People page team roster.
// See docs/team-members.md for details.

function ecec_register_team_member_cpt() {
	register_post_type( 'ecec_team_member', array(
		'labels' => array(
			'name'                  => 'Team Members',
			'singular_name'         => 'Team Member',
			'menu_name'             => 'Team Members',
			'add_new'               => 'Add New Member',
			'add_new_item'          => 'Add New Team Member',
			'edit_item'             => 'Edit Team Member',
			'new_item'              => 'New Team Member',
			'view_item'             => 'View Team Member',
			'search_items'          => 'Search Team Members',
			'not_found'             => 'No team members found',
			'not_found_in_trash'    => 'No team members in trash',
			'all_items'             => 'All Team Members',
			'featured_image'        => 'Photo',
			'set_featured_image'    => 'Set photo',
			'remove_featured_image' => 'Remove photo',
			'use_featured_image'    => 'Use as photo',
		),
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_admin_bar'   => true,
		'menu_position'       => 22,
		'menu_icon'           => 'dashicons-groups',
		'capability_type'     => 'post',
		'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		'has_archive'         => false,
		'rewrite'             => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
	) );
}
add_action( 'init', 'ecec_register_team_member_cpt' );

// Meta box: role + locations
function ecec_team_member_meta_box() {
	add_meta_box(
		'ecec_team_member_details',
		'Team Member Details',
		'ecec_team_member_meta_box_html',
		'ecec_team_member',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'ecec_team_member_meta_box' );

function ecec_team_member_meta_box_html( $post ) {
	wp_nonce_field( 'ecec_team_member_save', 'ecec_team_member_nonce' );
	$role      = get_post_meta( $post->ID, '_ecec_team_role', true );
	$locations = get_post_meta( $post->ID, '_ecec_team_locations', true );
	?>
	<p>
		<label for="ecec_team_role"><strong>Role / Title</strong></label><br>
		<input type="text" id="ecec_team_role" name="ecec_team_role"
			value="<?php echo esc_attr( $role ); ?>"
			placeholder="e.g. Founder | Principal"
			style="width:100%;">
	</p>
	<p>
		<label for="ecec_team_locations"><strong>Office Locations</strong></label><br>
		<input type="text" id="ecec_team_locations" name="ecec_team_locations"
			value="<?php echo esc_attr( $locations ); ?>"
			placeholder="e.g. Dubai | Riyadh | Amman"
			style="width:100%;">
		<span style="color:#666;font-size:12px;">Separate multiple offices with <code>|</code></span>
	</p>
	<p style="color:#666;font-size:12px;">
		Use the <strong>Featured image</strong> box to upload the member photo.
		Use the <strong>Page Attributes &rarr; Order</strong> field to control display order
		(lower numbers appear first).
	</p>
	<?php
}

function ecec_team_member_save_meta( $post_id ) {
	if ( ! isset( $_POST['ecec_team_member_nonce'] )
		|| ! wp_verify_nonce( $_POST['ecec_team_member_nonce'], 'ecec_team_member_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	if ( isset( $_POST['ecec_team_role'] ) ) {
		update_post_meta( $post_id, '_ecec_team_role', sanitize_text_field( wp_unslash( $_POST['ecec_team_role'] ) ) );
	}
	if ( isset( $_POST['ecec_team_locations'] ) ) {
		update_post_meta( $post_id, '_ecec_team_locations', sanitize_text_field( wp_unslash( $_POST['ecec_team_locations'] ) ) );
	}
}
add_action( 'save_post_ecec_team_member', 'ecec_team_member_save_meta' );

// Admin list columns: photo + role + order
function ecec_team_member_admin_columns( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		if ( $k === 'title' ) {
			$new['ecec_photo'] = 'Photo';
		}
		$new[ $k ] = $v;
		if ( $k === 'title' ) {
			$new['ecec_role']     = 'Role';
			$new['ecec_location'] = 'Offices';
		}
	}
	$new['menu_order'] = 'Order';
	unset( $new['date'] );
	return $new;
}
add_filter( 'manage_ecec_team_member_posts_columns', 'ecec_team_member_admin_columns' );

function ecec_team_member_admin_column_content( $col, $post_id ) {
	if ( $col === 'ecec_photo' ) {
		if ( has_post_thumbnail( $post_id ) ) {
			echo get_the_post_thumbnail( $post_id, array( 60, 60 ), array( 'style' => 'border-radius:6px;' ) );
		} else {
			echo '<span style="color:#bbb;">—</span>';
		}
	} elseif ( $col === 'ecec_role' ) {
		echo esc_html( get_post_meta( $post_id, '_ecec_team_role', true ) ?: '—' );
	} elseif ( $col === 'ecec_location' ) {
		echo esc_html( get_post_meta( $post_id, '_ecec_team_locations', true ) ?: '—' );
	} elseif ( $col === 'menu_order' ) {
		$p = get_post( $post_id );
		echo (int) ( $p->menu_order ?? 0 );
	}
}
add_action( 'manage_ecec_team_member_posts_custom_column', 'ecec_team_member_admin_column_content', 10, 2 );

// ─── [ecec_team_grid] shortcode ───
function ecec_team_grid_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'columns' => '3',
		'orderby' => 'menu_order',
		'order'   => 'ASC',
	), $atts, 'ecec_team_grid' );

	$q = new WP_Query( array(
		'post_type'      => 'ecec_team_member',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => array( $atts['orderby'] => $atts['order'], 'title' => 'ASC' ),
		'no_found_rows'  => true,
	) );
	if ( ! $q->have_posts() ) return '<p class="ecec-team-empty">No team members yet.</p>';

	$cols = max( 1, min( 4, (int) $atts['columns'] ) );
	ob_start(); ?>
	<div class="ecec-team-grid ecec-team-columns-<?php echo (int) $cols; ?>">
	<?php while ( $q->have_posts() ) : $q->the_post();
		$role      = get_post_meta( get_the_ID(), '_ecec_team_role', true );
		$locations = get_post_meta( get_the_ID(), '_ecec_team_locations', true );
		?>
		<article class="ecec-team-card">
			<div class="ecec-team-photo">
				<?php if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'medium', array( 'loading' => 'lazy' ) );
				} else { ?>
					<div class="ecec-team-photo-placeholder" aria-hidden="true"></div>
				<?php } ?>
			</div>
			<h4 class="ecec-team-name"><?php the_title(); ?></h4>
			<?php if ( $role ) : ?>
				<p class="ecec-team-role"><?php echo esc_html( $role ); ?></p>
			<?php endif; ?>
			<?php if ( $locations ) : ?>
				<p class="ecec-team-location"><?php echo esc_html( $locations ); ?></p>
			<?php endif; ?>
		</article>
	<?php endwhile; wp_reset_postdata(); ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ecec_team_grid', 'ecec_team_grid_shortcode' );

// ─── Single-Project Content Blocks: admin metabox (drag-sort repeater UI) ─
// Stores a JSON array of block objects in `_ecec_blocks` meta on portfolio-item.
// Block schemas (v1):
//   { "type": "text-paragraph",    "body": "..." }
//   { "type": "full-image",        "image_id": 123, "caption": "..." }
//   { "type": "image-pair",        "image_id_left": 12, "image_id_right": 34, "caption_left": "...", "caption_right": "..." }
//   { "type": "image-text-split",  "image_id": 123, "overline": "...", "heading": "...", "body": "...", "image_side": "left"|"right" }
//   { "type": "project-data",      "overline": "PROJECT DATA", "heading": "...", "rows": [ { "label": "GFA", "value": "56,000 sqm" } ] }
//   { "type": "pull-quote",        "quote": "...", "attribution": "..." }
//   { "type": "gallery",           "image_ids": [12, 34, 56], "columns": 3 }
//
// The UI is rendered by assets/admin-blocks-repeater.js — it reads and writes a
// hidden textarea (`ecec_blocks_json`) which the save handler below sanitizes.
function ecec_blocks_add_metabox() {
	add_meta_box(
		'ecec-blocks-metabox',
		'ECEC Content Blocks',
		'ecec_blocks_render_metabox',
		'portfolio-item',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'ecec_blocks_add_metabox' );

function ecec_blocks_get_saved_json( $post_id ) {
	$raw = get_post_meta( $post_id, '_ecec_blocks', true );
	if ( is_array( $raw ) ) {
		return wp_json_encode( $raw, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}
	if ( is_string( $raw ) && $raw !== '' ) {
		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) ) {
			return wp_json_encode( $decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		}
		return $raw;
	}
	return '[]';
}

function ecec_blocks_render_metabox( $post ) {
	$value = ecec_blocks_get_saved_json( $post->ID );
	wp_nonce_field( 'ecec_blocks_save', 'ecec_blocks_nonce' );
	$block_types = [
		'text-paragraph'   => 'Text Paragraph',
		'full-image'       => 'Full-width Image',
		'image-pair'       => 'Image Pair (side by side)',
		'image-text-split' => 'Image + Text (split)',
		'project-data'     => 'Project Data Table',
		'pull-quote'       => 'Pull Quote',
		'gallery'          => 'Gallery',
	];
	?>
	<p style="margin: 0 0 10px;">Build the project page by stacking blocks. Drag the <span class="dashicons dashicons-menu" style="color:#8c8f94"></span> handle to reorder. Leave empty to fall back to the legacy description + media grid.</p>
	<div id="ecec-blocks-repeater">
		<div class="ecec-blocks-toolbar">
			<label class="ecec-blocks-toolbar-label" for="ecec_add_block_type">Add block:</label>
			<select id="ecec_add_block_type" class="ecec-add-block-type">
				<?php foreach ( $block_types as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="button" class="button button-primary ecec-add-block-btn">Add</button>
		</div>
		<div class="ecec-blocks-empty" style="display:none">
			<p>No blocks yet — pick a type above and click <strong>Add</strong>.</p>
		</div>
		<div class="ecec-blocks-list"></div>
	</div>
	<textarea name="ecec_blocks_json" id="ecec_blocks_json" aria-hidden="true" tabindex="-1"><?php echo esc_textarea( $value ); ?></textarea>
	<?php
}

function ecec_blocks_admin_enqueue( $hook ) {
	if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) { return; }
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || $screen->post_type !== 'portfolio-item' ) { return; }

	wp_enqueue_media();

	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();
	$js_path   = $theme_dir . '/assets/admin-blocks-repeater.js';
	$css_path  = $theme_dir . '/assets/admin-blocks-repeater.css';

	wp_enqueue_style(
		'ecec-blocks-repeater',
		$theme_uri . '/assets/admin-blocks-repeater.css',
		[],
		file_exists( $css_path ) ? filemtime( $css_path ) : null
	);

	wp_enqueue_script(
		'ecec-blocks-repeater',
		$theme_uri . '/assets/admin-blocks-repeater.js',
		[ 'jquery', 'jquery-ui-sortable', 'wp-util' ],
		file_exists( $js_path ) ? filemtime( $js_path ) : null,
		true
	);

	// Pre-resolve attachment thumbnails for all image IDs referenced in the
	// current block state, so the UI can show thumbs on first render without
	// an AJAX round-trip.
	$post_id   = get_the_ID();
	$image_ids = [];
	if ( $post_id ) {
		$raw     = get_post_meta( $post_id, '_ecec_blocks', true );
		$decoded = is_array( $raw ) ? $raw : ( is_string( $raw ) ? json_decode( $raw, true ) : [] );
		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $block ) {
				if ( ! is_array( $block ) ) { continue; }
				foreach ( [ 'image_id', 'image_id_left', 'image_id_right' ] as $k ) {
					if ( ! empty( $block[ $k ] ) ) { $image_ids[] = (int) $block[ $k ]; }
				}
				if ( ! empty( $block['image_ids'] ) && is_array( $block['image_ids'] ) ) {
					foreach ( $block['image_ids'] as $id ) { $image_ids[] = (int) $id; }
				}
			}
		}
	}
	$image_ids   = array_values( array_unique( array_filter( $image_ids ) ) );
	$attachments = [];
	foreach ( $image_ids as $id ) {
		$thumb = wp_get_attachment_image_url( $id, 'thumbnail' );
		if ( $thumb ) {
			$attachments[ $id ] = [
				'thumb' => $thumb,
				'title' => get_the_title( $id ),
			];
		}
	}
	wp_localize_script( 'ecec-blocks-repeater', 'ECEC_BLOCKS_BOOT', [
		'attachments' => (object) $attachments,
	] );
}
add_action( 'admin_enqueue_scripts', 'ecec_blocks_admin_enqueue' );

function ecec_blocks_save( $post_id ) {
	if ( ! isset( $_POST['ecec_blocks_nonce'] ) || ! wp_verify_nonce( $_POST['ecec_blocks_nonce'], 'ecec_blocks_save' ) ) { return; }
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
	if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
	if ( get_post_type( $post_id ) !== 'portfolio-item' ) { return; }

	$raw = isset( $_POST['ecec_blocks_json'] ) ? wp_unslash( $_POST['ecec_blocks_json'] ) : '';
	$raw = trim( $raw );

	if ( $raw === '' || $raw === '[]' ) {
		delete_post_meta( $post_id, '_ecec_blocks' );
		return;
	}
	$decoded = json_decode( $raw, true );
	if ( ! is_array( $decoded ) ) { return; } // Keep previous value; the frontend has a status indicator warning the editor.

	// Sanitize each block minimally (type is required; strings get wp_kses_post on rich fields).
	$clean = [];
	foreach ( $decoded as $block ) {
		if ( ! is_array( $block ) || empty( $block['type'] ) ) { continue; }
		$type = sanitize_key( $block['type'] );
		$b = [ 'type' => $type ];
		// Common sanitizers
		if ( isset( $block['body'] ) )        { $b['body']       = wp_kses_post( $block['body'] ); }
		if ( isset( $block['caption'] ) )     { $b['caption']    = sanitize_text_field( $block['caption'] ); }
		if ( isset( $block['overline'] ) )    { $b['overline']   = sanitize_text_field( $block['overline'] ); }
		if ( isset( $block['heading'] ) )     { $b['heading']    = sanitize_text_field( $block['heading'] ); }
		if ( isset( $block['image_id'] ) )    { $b['image_id']   = (int) $block['image_id']; }
		if ( isset( $block['image_id_left'] ) )  { $b['image_id_left']  = (int) $block['image_id_left']; }
		if ( isset( $block['image_id_right'] ) ) { $b['image_id_right'] = (int) $block['image_id_right']; }
		if ( isset( $block['caption_left'] ) )   { $b['caption_left']   = sanitize_text_field( $block['caption_left'] ); }
		if ( isset( $block['caption_right'] ) )  { $b['caption_right']  = sanitize_text_field( $block['caption_right'] ); }
		if ( isset( $block['image_side'] ) )  { $b['image_side'] = $block['image_side'] === 'right' ? 'right' : 'left'; }
		if ( isset( $block['quote'] ) )       { $b['quote']      = wp_kses_post( $block['quote'] ); }
		if ( isset( $block['attribution'] ) ) { $b['attribution'] = sanitize_text_field( $block['attribution'] ); }
		if ( isset( $block['columns'] ) )     { $b['columns']    = (int) $block['columns'] === 2 ? 2 : 3; }
		if ( isset( $block['image_ids'] ) && is_array( $block['image_ids'] ) ) {
			$b['image_ids'] = array_values( array_filter( array_map( 'intval', $block['image_ids'] ) ) );
		}
		if ( isset( $block['rows'] ) && is_array( $block['rows'] ) ) {
			$rows = [];
			foreach ( $block['rows'] as $row ) {
				if ( ! is_array( $row ) ) { continue; }
				$rows[] = [
					'label' => isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : '',
					'value' => isset( $row['value'] ) ? sanitize_text_field( $row['value'] ) : '',
				];
			}
			$b['rows'] = $rows;
		}
		$clean[] = $b;
	}
	update_post_meta( $post_id, '_ecec_blocks', wp_slash( wp_json_encode( $clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) );
}
add_action( 'save_post_portfolio-item', 'ecec_blocks_save' );

// ─── Portfolio: "Clone as draft" row action ──────────────────────────────
// Adds a Clone link under each portfolio-item in the admin list. Copies the
// post content, taxonomies, and all meta (including `_ecec_blocks`,
// `qodef_portfolio_info_items`, thumbnail) into a new draft, then redirects
// to the edit screen. Internal edit locks are skipped.
function ecec_portfolio_row_actions( $actions, $post ) {
	if ( ! isset( $post->post_type ) || $post->post_type !== 'portfolio-item' ) { return $actions; }
	if ( ! current_user_can( 'edit_post', $post->ID ) ) { return $actions; }
	$url = wp_nonce_url(
		admin_url( 'admin-post.php?action=ecec_duplicate_portfolio&post=' . $post->ID ),
		'ecec_duplicate_portfolio_' . $post->ID
	);
	$actions['ecec_duplicate'] = '<a href="' . esc_url( $url ) . '" aria-label="Clone this project as a draft">Clone as draft</a>';
	return $actions;
}
add_filter( 'post_row_actions', 'ecec_portfolio_row_actions', 10, 2 );

function ecec_duplicate_portfolio_handler() {
	if ( empty( $_GET['post'] ) ) { wp_die( 'No source project specified.' ); }
	$src_id = (int) $_GET['post'];
	check_admin_referer( 'ecec_duplicate_portfolio_' . $src_id );
	if ( ! current_user_can( 'edit_post', $src_id ) ) { wp_die( 'You are not allowed to clone this project.' ); }

	$src = get_post( $src_id );
	if ( ! $src || $src->post_type !== 'portfolio-item' ) { wp_die( 'Source is not a portfolio project.' ); }

	$new_id = wp_insert_post( [
		'post_type'    => 'portfolio-item',
		'post_status'  => 'draft',
		'post_title'   => $src->post_title . ' (Copy)',
		'post_content' => $src->post_content,
		'post_excerpt' => $src->post_excerpt,
		'post_author'  => get_current_user_id(),
		'menu_order'   => $src->menu_order,
		'comment_status' => $src->comment_status,
		'ping_status'    => $src->ping_status,
	], true );
	if ( is_wp_error( $new_id ) ) { wp_die( 'Clone failed: ' . esc_html( $new_id->get_error_message() ) ); }

	// Copy taxonomies (categories, locations, tags, …).
	$taxonomies = get_object_taxonomies( 'portfolio-item' );
	foreach ( $taxonomies as $tax ) {
		$terms = wp_get_object_terms( $src_id, $tax, [ 'fields' => 'ids' ] );
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			wp_set_object_terms( $new_id, array_map( 'intval', $terms ), $tax );
		}
	}

	// Copy post meta (skip WP-internal edit locks). `maybe_unserialize` avoids
	// double-serialization on array/object values.
	$skip_meta = [ '_edit_lock', '_edit_last' ];
	$meta = get_post_meta( $src_id );
	if ( is_array( $meta ) ) {
		foreach ( $meta as $key => $values ) {
			if ( in_array( $key, $skip_meta, true ) ) { continue; }
			foreach ( (array) $values as $v ) {
				add_post_meta( $new_id, $key, maybe_unserialize( $v ) );
			}
		}
	}

	wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new_id ) );
	exit;
}
add_action( 'admin_post_ecec_duplicate_portfolio', 'ecec_duplicate_portfolio_handler' );

// Clone button inside the Publish sidebar on the portfolio-item edit screen.
// Hidden on auto-drafts so users don't try to clone a post before it has any
// saved content.
function ecec_portfolio_submitbox_clone_button( $post ) {
	if ( ! isset( $post->post_type ) || $post->post_type !== 'portfolio-item' ) { return; }
	if ( ! current_user_can( 'edit_post', $post->ID ) ) { return; }
	if ( $post->post_status === 'auto-draft' ) { return; }
	$url = wp_nonce_url(
		admin_url( 'admin-post.php?action=ecec_duplicate_portfolio&post=' . $post->ID ),
		'ecec_duplicate_portfolio_' . $post->ID
	);
	?>
	<div class="misc-pub-section ecec-clone-section">
		<span class="dashicons dashicons-admin-page" style="color:#8c8f94; vertical-align:middle; margin-right:2px;"></span>
		<a href="<?php echo esc_url( $url ); ?>" class="ecec-clone-link" onclick="return confirm('Clone the last saved version of this project as a new draft?\n\nUnsaved changes on this screen will NOT be copied — save first if you want to include them.');">Clone as draft</a>
		<p class="description" style="margin: 6px 0 0 0; font-size: 11px; color: #646970;">Creates a new project with all blocks, info, images, and categories copied over.</p>
	</div>
	<?php
}
add_action( 'post_submitbox_misc_actions', 'ecec_portfolio_submitbox_clone_button' );

// ─── Services: Vertical Divided List shortcode ──────────────────────────
// Renders ECEC's 7 service offerings as a vertical list with thin dividers
// between items. Service copy matches ecec.co verbatim. Content is
// hard-coded here for PoC; move to CPT when/if client wants self-edit.
function ecec_services_list_shortcode( $atts ) {
	$services = [
		[
			'name' => 'Structural Engineering',
			'body' => 'Our team of experienced structural engineers provides innovative and cost-effective solutions for a wide range of structural projects. We specialize in the design and analysis of buildings, bridges, towers, and other structures.',
		],
		[
			'name' => 'MEP Engineering',
			'body' => 'We offer MEP design and engineering services that cover all aspects of building services, including HVAC systems, electrical systems, plumbing systems, fire protection systems, and more.',
		],
		[
			'name' => 'Acoustic Engineering',
			'body' => 'Our acoustic engineers provide expert advice on noise and vibration control, acoustic design, and environmental acoustics. We offer comprehensive solutions that optimize acoustical performance in buildings, transportation systems, and other structures.',
		],
		[
			'name' => 'Building Information Modeling',
			'body' => 'Our BIM services cover the entire project lifecycle, from concept design to construction and maintenance. We use the latest BIM software to create 3D models that allow for accurate visualization, clash detection, and coordination.',
		],
		[
			'name' => 'ICT Solutions & Services',
			'body' => 'We offer a wide range of ICT solutions and services, including network design and implementation, software development, cybersecurity, and IT consulting. Our team is committed to providing cutting-edge technology solutions that enhance productivity and efficiency.',
		],
		[
			'name' => 'Security Consulting & Engineering',
			'body' => 'Our security experts provide comprehensive security consulting and engineering services, including risk assessments, security system design, and physical security solutions. We work with clients to develop customized security solutions that meet their unique needs and mitigate potential threats.',
		],
		[
			'name' => 'Supplementary Engineering Services',
			'body' => 'We work closely with our network of associates to provide supplementary engineering services, including fire and life safety consultancy, specialist lighting consultancy, vertical transportation, and waste management. Our team ensures that all aspects of the project are completed to the highest standards of quality and efficiency.',
		],
	];

	ob_start();
	?>
	<section class="ecec-services-list" aria-label="ECEC engineering services">
		<ol class="ecec-services-list__items">
			<?php foreach ( $services as $i => $svc ) :
				$index = sprintf( '%02d', $i + 1 );
				?>
				<li class="ecec-services-list__item">
					<div class="ecec-services-list__index" aria-hidden="true"><?php echo esc_html( $index ); ?></div>
					<div class="ecec-services-list__name"><?php echo esc_html( $svc['name'] ); ?></div>
					<div class="ecec-services-list__body"><?php echo esc_html( $svc['body'] ); ?></div>
					<div class="ecec-services-list__arrow" aria-hidden="true">&rarr;</div>
				</li>
			<?php endforeach; ?>
		</ol>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ecec_services_list', 'ecec_services_list_shortcode' );

// ─── Clients Marquee ─────────────────────────────────────────────────────
// Infinite horizontal scroll of client-brand logos. Logos are imported once
// via _deploy/import_client_logos.php and their attachment IDs stored in the
// `ecec_client_logo_ids` option. Displayed greyscale, color on hover, pauses
// on hover.
function ecec_clients_marquee_shortcode( $atts ) {
	$atts = shortcode_atts( [
		'speed' => '50',  // seconds for one full loop
		'height' => '60', // logo row height in px
	], $atts, 'ecec_clients_marquee' );

	$ids = get_option( 'ecec_client_logo_ids', [] );
	if ( ! is_array( $ids ) || empty( $ids ) ) {
		return '<p class="ecec-clients-empty">No client logos imported yet. Run <code>_deploy/import_client_logos.php</code> first.</p>';
	}

	$logos = [];
	foreach ( $ids as $id ) {
		$src = wp_get_attachment_image_url( $id, 'medium' );
		if ( ! $src ) { continue; }
		$logos[] = [
			'src' => $src,
			'alt' => get_the_title( $id ),
		];
	}
	if ( empty( $logos ) ) { return ''; }

	ob_start();
	$speed  = max( 10, (int) $atts['speed'] );
	$height = max( 20, (int) $atts['height'] );
	?>
	<section class="ecec-clients-marquee" aria-label="<?php esc_attr_e( 'Clients and partners', 'emaurri-child' ); ?>" style="--ecec-clients-speed: <?php echo (int) $speed; ?>s; --ecec-clients-h: <?php echo (int) $height; ?>px;">
		<div class="ecec-clients-track">
			<?php // Render the list twice for a seamless infinite loop (CSS shifts 50%). ?>
			<?php for ( $pass = 0; $pass < 2; $pass++ ) : ?>
				<ul class="ecec-clients-lane"<?php if ( $pass === 1 ) echo ' aria-hidden="true"'; ?>>
					<?php foreach ( $logos as $logo ) : ?>
						<li class="ecec-clients-item">
							<img src="<?php echo esc_url( $logo['src'] ); ?>" alt="<?php echo esc_attr( $pass === 0 ? $logo['alt'] : '' ); ?>" loading="lazy">
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endfor; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ecec_clients_marquee', 'ecec_clients_marquee_shortcode' );

// ─── Portfolio Carousel (home page block 1) ──────────────────────────────
// Horizontally-scrolling selection of portfolio items, styled like the
// Emaurri demo blog-list carousel. Uses CSS scroll-snap — no JS library,
// works on touch + mouse + keyboard (prev/next buttons).
function ecec_portfolio_carousel_shortcode( $atts ) {
	$atts = shortcode_atts( [
		'posts_per_page' => '8',
		'orderby'        => 'menu_order',
		'category'       => '',  // portfolio-category slug filter, optional
	], $atts, 'ecec_portfolio_carousel' );

	$args = [
		'post_type'      => 'portfolio-item',
		'post_status'    => 'publish',
		'posts_per_page' => max( 1, (int) $atts['posts_per_page'] ),
		'orderby'        => [ 'menu_order' => 'ASC', 'date' => 'DESC' ],
		'no_found_rows'  => true,
	];
	if ( $atts['category'] !== '' ) {
		$args['tax_query'] = [ [
			'taxonomy' => 'portfolio-category',
			'field'    => 'slug',
			'terms'    => sanitize_title( $atts['category'] ),
		] ];
	}
	$q = new WP_Query( $args );
	if ( ! $q->have_posts() ) { return '<p class="ecec-pcar-empty">No portfolio items yet.</p>'; }

	ob_start(); ?>
	<section class="ecec-pcar" aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Featured projects', 'emaurri-child' ); ?>">
		<div class="ecec-pcar__track" tabindex="0">
			<?php while ( $q->have_posts() ) : $q->the_post();
				$cats = get_the_terms( get_the_ID(), 'portfolio-category' );
				$cat_name = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->name : '';
				?>
				<article class="ecec-pcar__item">
					<a class="ecec-pcar__link" href="<?php the_permalink(); ?>">
						<div class="ecec-pcar__media">
							<?php if ( has_post_thumbnail() ) {
								the_post_thumbnail( 'large', [ 'class' => 'ecec-pcar__img', 'loading' => 'lazy' ] );
							} ?>
						</div>
						<div class="ecec-pcar__meta">
							<?php if ( $cat_name ) : ?>
								<span class="ecec-pcar__cat"><?php echo esc_html( $cat_name ); ?></span>
							<?php endif; ?>
							<h3 class="ecec-pcar__title"><?php the_title(); ?></h3>
						</div>
					</a>
				</article>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
		<button type="button" class="ecec-pcar__nav ecec-pcar__nav--prev" aria-label="Previous">&larr;</button>
		<button type="button" class="ecec-pcar__nav ecec-pcar__nav--next" aria-label="Next">&rarr;</button>
	</section>
	<script>
	(function(){
		var el = document.currentScript.previousElementSibling;
		var track = el.querySelector('.ecec-pcar__track');
		var prev  = el.querySelector('.ecec-pcar__nav--prev');
		var next  = el.querySelector('.ecec-pcar__nav--next');
		function step() { return (track.querySelector('.ecec-pcar__item') || {}).offsetWidth || 320; }
		prev && prev.addEventListener('click', function(){ track.scrollBy({ left: -step()*1.05, behavior: 'smooth' }); });
		next && next.addEventListener('click', function(){ track.scrollBy({ left:  step()*1.05, behavior: 'smooth' }); });
	})();
	</script>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ecec_portfolio_carousel', 'ecec_portfolio_carousel_shortcode' );
