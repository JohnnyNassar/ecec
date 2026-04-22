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
