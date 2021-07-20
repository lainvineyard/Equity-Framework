<?php 
/**
 * Equity Framework
 *
 * WARNING: This file is part of the core Equity Framework. DO NOT edit this file under any circumstances.
 * Please do all modifications in the form of a child theme.
 *
 * @package Equity\General
 * @author  IDX, LLC
 * @license GPL-2.0+
 * @link    
 */

/**
 * Enable the author box for ALL users.
 *
 * @since 1.4.1
 *
 * @param array $args Optional. Arguments for enabling author box. Default is empty array.
 */
function equity_enable_author_box( $args = array() ) {

	$args = wp_parse_args( $args, array( 'type' => 'single' ) );

	if ( 'single' === $args['type'] )
		add_filter( 'get_the_author_equity_author_box_single', '__return_true' );
	elseif ( 'archive' === $args['type'] )
		add_filter( 'get_the_author_equity_author_box_archive', '__return_true' );

}

/**
 * Redirect the user to an admin page, and add query args to the URL string for alerts, etc.
 *
 * @since 1.0
 *
 * @param string $page       Menu slug.
 * @param array  $query_args Optional. Associative array of query string arguments (key => value). Default is an empty array.
 *
 * @return null Return early if first argument is falsy.
 */
function equity_admin_redirect( $page, array $query_args = array() ) {

	if ( ! $page )
		return;

	$url = html_entity_decode( menu_page_url( $page, 0 ) );

	foreach ( (array) $query_args as $key => $value ) {
		if ( empty( $key ) && empty( $value ) ) {
			unset( $query_args[$key] );
		}
	}

	$url = add_query_arg( $query_args, $url );

	wp_redirect( esc_url_raw( $url ) );

}

add_action( 'template_redirect', 'equity_custom_field_redirect' );
/**
 * Redirect singular page to an alternate URL.
 *
 */
function equity_custom_field_redirect() {

	if ( ! is_singular() )
		return;

	if ( $url = equity_get_custom_field( 'redirect' ) ) {

		wp_redirect( esc_url_raw( $url ), 301 );
		exit;

	}

}

/**
 * Prevents theme update prompt if there is a theme in the .org repo with the same name.
 *
 * @since  1.1.4
 * 
 * @param array  $r   request arguments
 * @param string $url request url
 * 
 * @link https://gist.github.com/jaredatch/f406d6b2ca543cdb4898
 *
 * @return array request arguments
 */
function equity_dont_update_theme($r, $url) {
	// If it's not a theme update request, bail.
	if ( 0 !== strpos( $url, 'https://api.wordpress.org/themes/update-check/1.1/' ) ) {
		return $r;
	}

	// Decode the JSON response
	$themes = json_decode( $r['body']['themes'] );

	// Remove the active parent and child themes from the check
	$parent = get_option( 'template' );
	$child = get_option( 'stylesheet' );
	unset( $themes->themes->$parent );
	unset( $themes->themes->$child );

	// Encode the updated JSON response
	$r['body']['themes'] = json_encode( $themes );

	return $r;
}

/**
 * Return a specific value from the associative array passed as the second argument to `add_theme_support()`.
 *
 * @since 1.0
 *
 * @param string $feature The theme feature.
 * @param string $arg     The theme feature argument.
 * @param string $default Fallback if value is blank or doesn't exist.
 *
 * @return mixed Return $default if theme doesn't support $feature, or $arg key doesn't exist.
 */
function equity_get_theme_support_arg( $feature, $arg, $default = '' ) {

	$support = get_theme_support( $feature );

	if ( ! $support || ! isset( $support[0] ) || ! array_key_exists( $arg, (array) $support[0] ) )
		return $default;

	return $support[0][ $arg ];

}

/**
 * Determine if theme support equity-accessibility is activated by the child theme.
 * Assumes the presence of a screen-reader-text class in the stylesheet (required generated class as from WordPress 4.2)
 *
 * Adds screen-reader-text by default.
 * Skip links to primary navigation, main content, sidebars and footer, semantic headings and a keyboard accessible dropdown menu
 * can be added as extra features as: 'skip-links', 'headings', 'drop-down-menu'
 *
 * @since 1.7.3
 *
 * @param string $arg Optional. Specific accessibility feature to check for support. Default is screen-reader-text.
 * @return bool `true` if current theme supports `equity-accessibility`, or a specific feature of it, `false` otherwise.
 */
function equity_a11y( $arg = 'screen-reader-text' ) {

	$feature = 'equity-accessibility';

	if ( 'screen-reader-text' === $arg ) {
		return current_theme_supports( $feature );
	}

	$support = get_theme_support( $feature );

	// No support for feature.
	if ( ! $support ) {
		return false;
	}

	// No args passed in to add_theme_support(), so accept none.
	if ( ! isset( $support[0] ) ) {
		return false;
	}

	// Support for specific arg found.
	if ( in_array( $arg, $support[0] ) ) {
		return true;
	}

	return false;

}

/**
 * Detect active plugin by constant, class or function existence.
 *
 * @since 1.0
 *
 * @param array $plugins Array of array for constants, classes and / or functions to check for plugin existence.
 *
 * @return boolean True if plugin exists or false if plugin constant, class or function not detected.
 */
function equity_detect_plugin( array $plugins ) {

	//* Check for classes
	if ( isset( $plugins['classes'] ) ) {
		foreach ( $plugins['classes'] as $name ) {
			if ( class_exists( $name ) )
				return true;
		}
	}

	//* Check for functions
	if ( isset( $plugins['functions'] ) ) {
		foreach ( $plugins['functions'] as $name ) {
			if ( function_exists( $name ) )
				return true;
		}
	}

	//* Check for constants
	if ( isset( $plugins['constants'] ) ) {
		foreach ( $plugins['constants'] as $name ) {
			if ( defined( $name ) )
				return true;
		}
	}

	//* No class, function or constant found to exist
	return false;

}

/**
 * Check that we're targeting a specific Equity admin page.
 *
 * The `$pagehook` argument is expected to be 'equity' although
 * others can be accepted.
 *
 * @since 1.0
 *
 * @global string $page_hook Page hook for current page.
 *
 * @param string $pagehook Page hook string to check.
 *
 * @return boolean Return true if the global $page_hook matches given $pagehook. False otherwise.
 */
function equity_is_menu_page( $pagehook = '' ) {

	global $page_hook;

	if ( isset( $page_hook ) && $page_hook === $pagehook )
		return true;

	//* May be too early for $page_hook
	if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === $pagehook )
		return true;

	return false;

}

/**
 * Check whether we are currently viewing the site via the WordPress Customizer.
 *
 * @since 1.0
 *
 * @global $wp_customize Customizer.
 *
 * @return boolean Return true if viewing page via Customizer, false otherwise.
 */
function equity_is_customizer() {

	global $wp_customize;

	return is_a( $wp_customize, 'WP_Customize_Manager' ) && $wp_customize->is_preview();

}

/**
 * Helper function to determine if the blog_page.php template should be used.
 *
 * @since 1.0
 * 
 * @uses get_post_meta() Get post metadata.
 * @uses get_queried_object_id() Get queried object ID.
 *
 * @return bool
 */
function equity_is_blog_template() {

	if ( 'page_blog.php' == get_post_meta( get_queried_object_id(), '_wp_page_template', true ) ) {
		return true;
	}

	return false;

}

/**
 * Get the `post_type` from the global `$post` if supplied value is empty.
 *
 * @since 1.0
 *
 * @param string $post_type_name Post type name.
 *
 * @return string
 */
function equity_get_global_post_type_name( $post_type_name = '' ) {

	if ( ! $post_type_name ) {
		$post_type_name = get_post_type();
	}
	return $post_type_name;

}

/**
 * Get list of custom post type objects which need an archive settings page.
 *
 * Archive settings pages are added for CPTs that:
 *
 * - are public,
 * - are set to show the UI,
 * - are set to show in the admin menu,
 * - have an archive enabled,
 * - not one of the built-in types,
 * - support "equity-cpt-archive-settings".
 *
 * This last item means that if you're using an archive template and don't want Equity interfering with it with these
 * archive settings, then don't add the support. This support check is handled in
 * {@link equity_has_post_type_archive_support()}.
 *
 * Applies the `equity_cpt_archives_args` filter, to change the conditions for which post types are deemed valid.
 *
 * The results are held in a static variable, since they won't change over the course of a request.
 *
 * @since 1.0
 *
 * @return array
 */
function equity_get_cpt_archive_types() {

	static $equity_cpt_archive_types;
	if ( $equity_cpt_archive_types )
		return $equity_cpt_archive_types;

	$args = apply_filters(
		'equity_cpt_archives_args',
		array(
			'public'       => true,
			'show_ui'      => true,
			'show_in_menu' => true,
			'has_archive'  => true,
			'_builtin'     => false,
		)
	);

	$equity_cpt_archive_types = get_post_types( $args, 'objects' );

	return $equity_cpt_archive_types;

}

/**
 * Get list of custom post type names which need an archive settings page.
 *
 * @since 1.0
 *
 * @uses equity_get_cpt_archive_types() Get list of custom post type objects which need an archive settings page.
 *
 * @return array Custom post type names.
 */
function equity_get_cpt_archive_types_names() {

	$post_type_names = array();
	foreach ( equity_get_cpt_archive_types() as $post_type )
		$post_type_names[] = $post_type->name;

	return $post_type_names;

}

/**
 * Check if a post type supports an archive setting page.
 *
 * @since 1.0
 *
 * @uses equity_get_global_post_type_name()   Get the `post_type` from the global `$post` if supplied value is empty.
 * @uses equity_get_cpt_archive_types_names() Get list of custom post type names which need an archive settings page.
 *
 * @param string $post_type_name Post type name.
 *
 * @return bool True if custom post type name has support, false otherwise.
 */
function equity_has_post_type_archive_support( $post_type_name = '' ) {

	$post_type_name = equity_get_global_post_type_name( $post_type_name );

	return in_array( $post_type_name, equity_get_cpt_archive_types_names() ) &&
		post_type_supports( $post_type_name, 'equity-cpt-archives-settings' );

}

/**
 * Build links to install plugins.
 *
 * @since 1.0
 *
 * @param string $plugin_slug Plugin slug.
 * @param string $text        Plugin name.
 *
 * @return string              HTML markup for links.
 */
function equity_plugin_install_link( $plugin_slug = '', $text = '' ) {

	if ( is_main_site() ) {
		$url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $plugin_slug . '&TB_iframe=true&width=600&height=550' );
	}
	else {
		$url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $plugin_slug . '&TB_iframe=true&width=600&height=550' );
	}

	return sprintf( '<a href="%s" class="thickbox">%s</a>', esc_url( $url ), esc_html( $text ) );

}

if ( ! function_exists( 'equity_posted_on' ) ) :
/**
 * Prints HTML with meta information for the current post-date/time and author.
 */
function equity_posted_on() {
	$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time>';
	if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
		$time_string .= '<time class="updated" datetime="%3$s">%4$s</time>';
	}

	$time_string = sprintf( $time_string,
		esc_attr( get_the_date( 'c' ) ),
		esc_html( get_the_date() ),
		esc_attr( get_the_modified_date( 'c' ) ),
		esc_html( get_the_modified_date() )
	);

	$posted_on = sprintf(
		_x( 'Posted on %s', 'post date', 'equity' ),
		'<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">' . $time_string . '</a>'
	);

	$byline = sprintf(
		_x( 'by %s', 'post author', 'equity' ),
		'<span class="author vcard"><a class="url fn n" href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a></span>'
	);

	echo '<span class="posted-on">' . $posted_on . '</span><span class="byline"> ' . $byline . '</span>';

}
endif;

/**
 * Returns true if a blog has more than 1 category.
 *
 * @since 1.0
 * 
 * @return bool
 */
function equity_categorized_blog() {
	if ( false === ( $all_the_cool_cats = get_transient( 'equity_categories' ) ) ) {
		// Create an array of all the categories that are attached to posts.
		$all_the_cool_cats = get_categories( array(
			'fields'     => 'ids',
			'hide_empty' => 1,

			// We only need to know if there is more than one category.
			'number'     => 2,
		) );

		// Count the number of categories that are attached to the posts.
		$all_the_cool_cats = count( $all_the_cool_cats );

		set_transient( 'equity_categories', $all_the_cool_cats );
	}

	if ( $all_the_cool_cats > 1 ) {
		// This blog has more than 1 category so equity_categorized_blog should return true.
		return true;
	} else {
		// This blog has only 1 category so equity_categorized_blog should return false.
		return false;
	}
}

/**
 * Flush out the transients used in equity_categorized_blog.
 * 
 * @since 1.0
 */
function equity_category_transient_flusher() {
	// Like, beat it. Dig?
	delete_transient( 'equity_categories' );
}
add_action( 'edit_category', 'equity_category_transient_flusher' );
add_action( 'save_post',     'equity_category_transient_flusher' );

/**
 * Add listing post type as selection in Settings > Reading > Front Page
 * @since  1.5.6
 */
add_action( 'admin_head-options-reading.php', 'equity_modify_front_pages_dropdown' );
add_action( 'pre_get_posts', 'enable_front_page_listing' );
function equity_modify_front_pages_dropdown() {
	add_filter( 'get_pages', 'add_listing_to_dropdown' );
}
function add_listing_to_dropdown( $pages ) {
    $args = array(
        'post_type' => 'listing'
    );
    $items = get_posts($args);
    $pages = array_merge($pages, $items);

    return $pages;
}
function enable_front_page_listing( $query ) {
    if(0 != $query->query_vars['page_id']) {
        $query->query_vars['post_type'] = array( 'page', 'listing' );
        add_filter( 'template_include', 'equity_listing_home_template', 99 );
    }
}
function equity_listing_home_template( $template ) {
	if ( is_front_page() && get_post_type() == 'listing' ) {
		$new_template = locate_template( array( 'single-listing.php' ) );
		if ( '' != $new_template ) {
			return $new_template ;
		}
	}

	return $template;
}