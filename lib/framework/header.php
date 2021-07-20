<?php
/**
 * Equity Framework
 *
 * WARNING: This file is part of the core Equity Framework. DO NOT edit this file under any circumstances.
 * Please do all modifications in the form of a child theme.
 *
 * @package Equity\Header
 * @author  IDX, LLC
 * @license GPL-2.0+
 * @link    
 */

add_action( 'equity_doctype', 'equity_do_doctype' );

/**
 * HTML5 doctype markup.
 *
 * @since 1.0
 */
function equity_do_doctype() {

	?><!DOCTYPE html>
<html <?php language_attributes( 'html' ); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<?php

}

add_filter( 'the_generator', 'equity_generator' );
/**
 * Filter generator tag to include theme and version.
 *
 * @since 1.0
 */
function equity_generator( $generator ) {
	$generator .= "\r\n" . '<meta name="generator" content="' . PARENT_THEME_NAME . ' ' . PARENT_THEME_VERSION . '" />';
	return $generator;
}

add_action( 'get_header', 'equity_doc_head_control' );
/**
 * Conditionally add feed links if comments enabled for posts/pages.
 *
 * @since 1.0
 *
 * @uses equity_get_option() Get theme setting value
 */
function equity_doc_head_control() {

	if ( is_single() && ! equity_get_option( 'comments_posts' ) )
		remove_action( 'wp_head', 'feed_links_extra', 3 );

	if ( is_page() && ! equity_get_option( 'comments_pages' ) )
		remove_action( 'wp_head', 'feed_links_extra', 3 );

}

add_action( 'equity_meta', 'equity_responsive_viewport' );
/**
 * Added by default. Child theme will need to use remove_theme_support(`equity-responsive-viewport`) to disable.
 *
 * @since 1.0
 *
 * @return null Return early if child theme removes support.
 */
function equity_responsive_viewport() {

	echo '<meta name="viewport" content="width=device-width, initial-scale=1" />' . "\n";

}

add_action( 'wp_head', 'equity_load_favicon' );
/**
 * Echo favicon link if one is uploaded through customizer, if none uploaded, use icon from images dir.
 *
 * Falls back to Equity parent theme favicon.
 *
 * @since 1.0
 * @since  1.5.3 Uses has_site_icon and get_site_icon_url to retrieve image
 *
 * @uses CHILD_DIR
 * @uses CHILD_URL
 * @uses PARENT_URL
 */
function equity_load_favicon() {

	// If Site icon has been set in Customizer (WP 4.3+) use that
	if (has_site_icon()) {
		$favicon = get_site_icon_url(32, PARENT_URL . '/images/favicon.png');
	} else {
		// Check theme option and theme files for backwards compatibility
		$favicon = get_theme_mod('favicon');

		if ( ! empty( $favicon ) )
			$favicon = get_theme_mod('favicon');
		elseif ( file_exists( CHILD_DIR . '/images/favicon.ico' ) )
			$favicon = CHILD_URL . '/images/favicon.ico';
		elseif ( file_exists( CHILD_DIR . '/images/favicon.png' ) )
			$favicon = CHILD_URL . '/images/favicon.png';
		else
			$favicon = PARENT_URL . '/images/favicon.png';
	}

	echo '<link rel="Shortcut Icon" href="' . esc_url( $favicon ) . '" type="image/x-icon" />' . "\n";

}

add_action( 'wp_head', 'equity_load_touch_icon' );
/**
 * Echo mobile device touch icon link if one is uploaded through customizer.
 * Falls back to Equity theme touch icon.
 *
 * @since 1.0
 * @since  1.5.3 Uses has_site_icon and get_site_icon_url to retrieve image
 *
 * @uses PARENT_URL
 */
function equity_load_touch_icon() {

	if (has_site_icon()) {
		// For iOS
		echo '<link rel="apple-touch-icon" sizes="180x180" href="' . get_site_icon_url(180) . '" />' . "\n";
		// For Android and other mobile browsers
		echo '<link rel="icon" sizes="192x192" href="' . get_site_icon_url(192) . '" />' . "\n";
	} else {
		// Backwards compatibility
		$apple_touch_icon = get_theme_mod('apple_touch_icon', ( file_exists( CHILD_DIR . '/images/favicon.ico' ) ) ? CHILD_DIR . '/images/favicon.ico' : PARENT_URL . '/images/touch-icon.png');
		echo '<link rel="apple-touch-icon" href="' . esc_url( $apple_touch_icon ) . '" />' . "\n";
	}

}

add_action( 'wp_head', 'equity_do_meta_pingback' );
/**
 * Adds the pingback meta tag to the head so that other sites can know how to send a pingback to our site.
 *
 * @since 1.0
 */
function equity_do_meta_pingback() {

	if ( 'open' === get_option( 'default_ping_status' ) )
		echo '<link rel="pingback" href="' . get_bloginfo( 'pingback_url' ) . '" />' . "\n";

}

add_action( 'wp_head', 'equity_do_idx_feed_link' );
/**
 * Adds the IDX Sitemap XML link to document head if it exists
 *
 * @since 1.3
 */
function equity_do_idx_feed_link() {

	if ( ! equity_get_option( 'idx_feed_uri' ) == '' )
		echo '<link rel="sitemap" href="' . equity_get_option( 'idx_feed_uri' ) . '" title="Listings feed" />' . "\n";

}

add_filter( 'equity_header_scripts', 'do_shortcode' );
add_action( 'wp_head', 'equity_header_scripts' );
/**
 * Echo header scripts in to wp_head().
 *
 * Allows shortcodes.
 *
 * Applies `equity_header_scripts` filter on value stored in header_scripts setting.
 *
 * Also echoes scripts from the post's custom field.
 *
 * @since 1.0
 *
 * @uses equity_get_option()       Get theme setting value.
 * @uses equity_get_custom_field() Echo custom field value.
 */
function equity_header_scripts() {

	echo apply_filters( 'equity_header_scripts', equity_get_option( 'header_scripts' ) );

	//* If singular, echo scripts from custom field
	if ( is_singular() )
		equity_custom_field( '_equity_scripts' );

}

add_action( 'equity_before_header', 'equity_do_top_header', 10 );
/**
 * Echo the top header widget areas.
 *
 * @since 1.0
 *
 * @global $wp_registered_sidebars Holds all of the registered sidebars.
 *
 * @uses equity_markup() Apply contextual markup.
 */
function equity_do_top_header() {

	if ( ! current_theme_supports( 'equity-top-header-bar' ) )
		return;

	global $wp_registered_sidebars;

	do_action( 'equity_before_top_header' );

	echo '<div class="top-header contain-to-grid"><div class="row">';

	if ( equity_nav_menu_supported( 'top-header-left' ) || equity_nav_menu_supported( 'top-header-right' ) ) {
		equity_markup( array(
			'html5'   => '<nav %s data-topbar>',
			'context' => 'nav-top-header-bar',
		) );
		echo '<ul class="title-area"><li class="name"></li><li class="toggle-topbar menu-icon"><a href="#"><span>Menu</span></a></li></ul><section class="top-bar-section">';
	}

	if ( ( isset( $wp_registered_sidebars['top-header-left'] ) && is_active_sidebar( 'top-header-left' ) ) || has_action( 'equity_top_header_left' ) ) {

		$top_header_left_widget_widths = apply_filters( 'top_header_left_widget_widths', $top_header_left_widget_widths = 'small-12 medium-6 large-6' );

		equity_markup( array(
			'html5'   => '<aside class="widget-area columns ' . $top_header_left_widget_widths .'"><ul %s>',
			'context' => 'top-header-left',
		) );

			do_action( 'equity_top_header_left' );
			dynamic_sidebar( 'top-header-left' );

		equity_markup( array(
			'html5' => '</ul></aside>',
		) );
	} elseif ( equity_nav_menu_supported( 'top-header-left' ) ) {
		if ( has_nav_menu( 'top-header-left' ) ) {
			equity_nav_menu( array(
				'theme_location' => 'top-header-left',
				'menu_class'     => 'menu equity-nav-menu menu-top-header left',
				'walker'         => new Top_Bar_Walker,
			), false );
		} else {
			echo '<ul class="menu left"><li><a href="' . home_url() . '/wp-admin/nav-menus.php">' . __( 'Please Setup your Menus', 'equity' ) . '</a></li></ul>';
		}
	}

	if ( ( isset( $wp_registered_sidebars['top-header-right'] ) && is_active_sidebar( 'top-header-right' ) ) || has_action( 'equity_top_header_right' ) ) {

		$top_header_right_widget_widths = apply_filters( 'top_header_right_widget_widths', $top_header_right_widget_widths = 'small-12 medium-6 large-6' );

		equity_markup( array(
			'html5'   => '<aside class="widget-area columns ' . $top_header_right_widget_widths .'"><ul %s>',
			'context' => 'top-header-right',
		) );

			do_action( 'equity_top_header_right' );
			dynamic_sidebar( 'top-header-right' );

		equity_markup( array(
			'html5' => '</ul></aside>',
		) );
	} elseif ( equity_nav_menu_supported( 'top-header-right' ) ) {
		if ( has_nav_menu( 'top-header-right' ) ) {
			equity_nav_menu( array(
				'theme_location' => 'top-header-right',
				'menu_class'     => 'menu equity-nav-menu menu-top-header right',
				'walker'         => new Top_Bar_Walker,
			), false );
		} else {
			echo '<ul class="menu right"><li><a href="' . home_url() . '/wp-admin/nav-menus.php">' . __( 'Please Setup your Menus', 'equity' ) . '</a></li></ul>';
		}
	}

	if ( equity_nav_menu_supported( 'top-header-left' ) || equity_nav_menu_supported( 'top-header-right' ) )
		echo '</section><!-- .top-bar-section --></nav>';

	echo '</div><!-- .row --></div><!-- .top-header -->';

	do_action( 'equity_after_top_header' );

}

add_action( 'equity_header', 'equity_header_markup_open', 5 );
/**
 * Echo the opening structural markup for the header.
 *
 * @since 1.0
 *
 * @uses equity_markup()          Apply contextual markup.
 * @uses equity_structural_wrap() Maybe add opening .wrap div tag with header context.
 */
function equity_header_markup_open() {

	equity_markup( array(
		'html5'   => '<header %s>',
		'context' => 'site-header',
	) );

	equity_structural_wrap( 'header' );

}

add_action( 'equity_header', 'equity_header_markup_close', 15 );
/**
 * Echo the opening structural markup for the header.
 *
 * @since 1.0
 *
 * @uses equity_structural_wrap() Maybe add closing .wrap div tag with header context.
 * @uses equity_markup()          Apply contextual markup.
 */
function equity_header_markup_close() {

	equity_structural_wrap( 'header', 'close' );
	equity_markup( array(
		'html5' => '</header> <!-- header .row -->',
	) );

}

add_action( 'equity_header', 'equity_do_header' );
/**
 * Echo the default header, including the .title-area div, along with .site-title and .site-description, as well as the .widget-area.
 *
 * Does the `equity_site_title`, `equity_site_description` and `equity_header_right` actions.
 *
 * @since 1.0
 *
 * @global $wp_registered_sidebars Holds all of the registered sidebars.
 *
 * @uses equity_markup() Apply contextual markup.
 */
function equity_do_header() {

	global $wp_registered_sidebars;

	equity_markup( array(
		'html5'      => '<div %s>',
		'context'    => 'title-area',
	) );
	do_action( 'equity_site_title' );
	do_action( 'equity_site_description' );
	echo '</div>';

	if ( ( isset( $wp_registered_sidebars['header-right'] ) && is_active_sidebar( 'header-right' ) ) ) {
		equity_markup( array(
			'html5'   => '<aside %s>',
			'context' => 'header-widget-area',
		) );

			add_filter( 'wp_nav_menu_args', 'equity_header_menu_args' );
			add_filter( 'wp_nav_menu', 'equity_header_menu_wrap' );
			dynamic_sidebar( 'header-right' );
			remove_filter( 'wp_nav_menu_args', 'equity_header_menu_args' );
			remove_filter( 'wp_nav_menu', 'equity_header_menu_wrap' );

		equity_markup( array(
			'html5' => '</aside>',
		) );
	} elseif ( has_action( 'equity_header_right' ) ) {

		do_action( 'equity_header_right' );

	} elseif ( equity_nav_menu_supported( 'header-right' ) ) {
		if ( has_nav_menu( 'header-right' ) ) {
			equity_markup( array(
				'html5'   => '<nav %s>',
				'context' => 'nav-header-right',
			) );

			equity_nav_menu( array(
				'theme_location'  => 'header-right',
				'menu_class'      => 'menu equity-nav-menu menu-header-right',
				'link_before'     => equity_markup(
					array(
						'open'    => '<span %s>',
						'context' => 'nav-link-wrap',
						'echo'    => true,
					)
				),
				'link_after'     => equity_markup(
					array(
						'close'   => '</span>',
						'context' => 'nav-link-wrap',
						'echo'    => true,
					)
				),
				'echo'           => 1,
			), false );

			equity_markup( array(
				'html5' => '</nav>',
			) );
		}
	}
}

add_action( 'equity_site_title', 'equity_do_site_title' );
/**
 * Echo the site title into the header.
 *
 * This will either be wrapped in an `h1` or `p` element depending on if viewing home page or not.
 *
 * Applies the `equity_do_title` filter before echoing.
 *
 * @since 1.0
 */
function equity_do_site_title() {

	//* Set what goes inside the wrapping tags, 
	if( current_theme_supports('site-logo') && has_custom_logo() == true ) {
		$logo_id = get_theme_mod('custom_logo');
		$logo_image = wp_get_attachment_image($logo_id, 'full', 0, array('alt' => esc_attr( get_bloginfo( 'name' ) ), 'class' => 'site-logo'));
	} elseif(current_theme_supports('site-logo') && function_exists('jetpack_has_site_logo') && jetpack_has_site_logo() == true) {
		$logo_image = jetpack_get_site_logo();
	} else {
		//legacy theme mod
		$logo_image = get_theme_mod( 'logo_image' );
	}
	
	if( $logo_image ) {
		$inside = sprintf( '<a href="%s" title="%s"><img src="%s" alt="%s" /><span class="hide">%s</span></a>', trailingslashit( home_url() ), esc_attr( get_bloginfo( 'name' ) ), $logo_image, esc_attr( get_bloginfo( 'name' ) ), get_bloginfo( 'name' ) );
	} else {
		$inside = sprintf( '<a href="%s" title="%s">%s</a>', trailingslashit( home_url() ), esc_attr( get_bloginfo( 'name' ) ), get_bloginfo( 'name' ) );
	}

	//* Determine which wrapping tags to use
	$wrap = is_front_page() ? 'h1' : 'p';

	//* Build the title
	$title  = sprintf( "<{$wrap} %s>", equity_attr( 'site-title' ) );
	$title .= "{$inside}</{$wrap}>";

	//* Echo (filtered)
	echo apply_filters( 'equity_do_title', $title, $inside, $wrap );

}

add_action( 'equity_site_description', 'equity_do_site_description' );
/**
 * Echo the site description into the header.
 *
 * Applies the `equity_do_description` filter before echoing.
 *
 * @since 1.0
 */
function equity_do_site_description() {

	//* Set what goes inside the wrapping tags
	$inside = esc_html( get_bloginfo( 'description' ) );

	//* Determine which wrapping tags to use
	$wrap = is_home() ? 'h2' : 'p';

	//* Build the description
	$description  = sprintf( "<{$wrap} %s>", equity_attr( 'site-description' ) );
	$description .= "{$inside}</{$wrap}>";

	//* Echo (filtered)
	echo apply_filters( 'equity_do_description', $description, $inside, $wrap );

}

/**
 * Sets a common class, `.equity-nav-menu`, for the custom menu widget if used in the header right sidebar.
 *
 * @since 1.0
 *
 * @param  array $args Header menu args.
 *
 * @return array $args Modified header menu args.
 */
function equity_header_menu_args( $args ) {

	$args['menu_class'] .= ' equity-nav-menu';

	return $args;

}

/**
 * Wrap the header navigation menu in its own nav tags with markup API.
 *
 * @since 1.0
 *
 * @param  $menu Menu output.
 *
 * @return string $menu Modified menu output.
 */
function equity_header_menu_wrap( $menu ) {

	return sprintf( '<nav %s>', equity_attr( 'nav-header-right' ) ) . $menu . '</nav>';

}

add_action( 'equity_before_header', 'equity_skip_links', 5 );
/**
 * Add skip links for screen readers and keyboard navigation.
 *
 * @since  1.7.3
 *
 * @return void Return early if skip links are not supported.
 */
function equity_skip_links() {

	if ( ! equity_a11y( 'skip-links' ) ) {
		return;
	}

	// Call function to add IDs to the markup.
	equity_skiplinks_markup();

	// Determine which skip links are needed.
	$links = array();

	if ( equity_nav_menu_supported( 'main' ) && has_nav_menu( 'main' ) ) {
		$links['equity-nav-main'] =  __( 'Skip to main navigation', 'equity' );
	}

	if ( equity_nav_menu_supported( 'header-right' ) && has_nav_menu( 'header-right' ) ) {
		$links['equity-nav-header-right'] =  __( 'Skip to main navigation', 'equity' );
	}

	$links['equity-content'] = __( 'Skip to content', 'equity' );

	if ( 'full-width-content' != equity_site_layout() ) {
		$links['equity-sidebar-primary'] = __( 'Skip to primary sidebar', 'equity' );
	}

	$footer_widgets = get_theme_mod( 'footer_widgets' );
	if ( $footer_widgets > 0 && is_active_sidebar( 'footer-1' ) ) {
		$links['equity-footer-widgets'] = __( 'Skip to footer', 'equity' );
	}

	 /**
	 * Filter the skip links.
	 *
	 * @since 1.7.3
	 *
	 * @param array $links {
	 *     Default skiplinks.
	 *
	 *     @type string HTML ID attribute value to link to.
	 *     @type string Anchor text.
	 * }
	 */
	$links = (array) apply_filters( 'equity_skip_links_output', $links );

	// Write HTML, skiplinks in a list.
	$skiplinks = '<ul class="equity-skip-link">';

	// Add markup for each skiplink.
	foreach ( $links as $key => $value ) {
		$skiplinks .= '<li><a href="' . esc_url( '#' . $key ) . '" class="screen-reader-shortcut"> ' . $value . '</a></li>';
	}

	$skiplinks .= '</ul>';

	echo $skiplinks;

}
