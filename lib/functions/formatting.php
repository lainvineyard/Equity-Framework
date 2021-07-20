<?php
/**
 * Equity Framework
 *
 * WARNING: This file is part of the core Equity Framework. DO NOT edit this file under any circumstances.
 * Please do all modifications in the form of a child theme.
 *
 * @package Equity\Formatting
 * @author  IDX, LLC
 * @license GPL-2.0+
 * @link    
 */

/**
 * Return a phrase shortened in length to a maximum number of characters.
 *
 * Result will be truncated at the last white space in the original string. In this function the word separator is a
 * single space. Other white space characters (like newlines and tabs) are ignored.
 *
 * If the first `$max_characters` of the string does not contain a space character, an empty string will be returned.
 *
 * @since 1.0
 *
 * @param string $text            A string to be shortened.
 * @param integer $max_characters The maximum number of characters to return.
 *
 * @return string Truncated string
 */
function equity_truncate_phrase( $text, $max_characters ) {

	$text = trim( $text );

	if ( mb_strlen( $text ) > $max_characters ) {
		//* Truncate $text to $max_characters + 1
		$text = mb_substr( $text, 0, $max_characters + 1 );

		//* Truncate to the last space in the truncated string
		$text = trim( mb_substr( $text, 0, mb_strrpos( $text, ' ' ) ) );
	}

	return $text;
}

/**
 * Return content stripped down and limited content.
 *
 * Strips out tags and shortcodes, limits the output to `$max_char` characters, and appends an ellipsis and more link to the end.
 *
 * @since 1.0
 *
 * @param integer $max_characters The maximum number of characters to return.
 * @param string  $more_link_text Optional. Text of the more link. Default is "(more...)".
 * @param bool    $stripteaser    Optional. Strip teaser content before the more text. Default is false.
 *
 * @return string Limited content.
 */
function get_the_content_limit( $max_characters, $more_link_text = '(more...)', $stripteaser = false ) {

	$content = get_the_content( '', $stripteaser );

	//* Strip tags and shortcodes so the content truncation count is done correctly
	$content = strip_tags( strip_shortcodes( $content ), apply_filters( 'get_the_content_limit_allowedtags', '<script>,<style>' ) );

	//* Remove inline styles / scripts
	$content = trim( preg_replace( '#<(s(cript|tyle)).*?</\1>#si', '', $content ) );

	//* Truncate $content to $max_char
	$content = equity_truncate_phrase( $content, $max_characters );

	//* More link?
	if ( $more_link_text ) {
		$link   = apply_filters( 'get_the_content_more_link', sprintf( '&#x02026; <a href="%s" class="more-link">%s</a>', get_permalink(), $more_link_text ), $more_link_text );
		$output = sprintf( '<p>%s %s</p>', $content, $link );
	} else {
		$output = sprintf( '<p>%s</p>', $content );
		$link = '';
	}

	return apply_filters( 'get_the_content_limit', $output, $content, $link, $max_characters );

}

/**
 * Echo the limited content.
 *
 * @since 1.0
 *
 * @uses get_the_content_limit() Return content stripped down and limited content.
 *
 * @param integer $max_characters The maximum number of characters to return.
 * @param string  $more_link_text Optional. Text of the more link. Default is "(more...)".
 * @param bool    $stripteaser    Optional. Strip teaser content before the more text. Default is false.
 */
function the_content_limit( $max_characters, $more_link_text = '(more...)', $stripteaser = false ) {

	$content = get_the_content_limit( $max_characters, $more_link_text, $stripteaser );
	echo apply_filters( 'the_content_limit', $content );

}

/**
 * Add `rel="nofollow"` attribute and value to links within string passed in.
 *
 * @since 1.0
 *
 * @uses equity_strip_attr() Remove any existing rel attribute from links.
 *
 * @param string $text HTML markup.
 *
 * @return string Amendment HTML markup.
 */
function equity_rel_nofollow( $text ) {

	$text = equity_strip_attr( $text, 'a', 'rel' );
	return stripslashes( wp_rel_nofollow( $text ) );

}

/**
 * Remove attributes from a HTML element.
 *
 * This function accepts a string of HTML, parses it for any elements in the `$elements` array, then parses each element
 * for any attributes in the `$attributes` array, and strips the attribute and its value(s).
 *
 * ~~~
 * // Strip class attribute from an anchor
 * equity_strip_attr(
 *     '<a class="my-class" href="http://google.com/">Google</a>',
 *     'a',
 *     'class'
 * );
 * // Strips class and id attributes from div and span elements
 * equity_strip_attr(
 *     '<div class="my-class" id="the-div"><span class="my-class" id="the-span"></span></div>',
 *     array( 'div', 'span' ),
 *     array( 'class', 'id' )
 * );
 * ~~~
 *
 * @since 1.0
 *
 * @param string       $text       A string of HTML formatted code.
 * @param array|string $elements   Elements that $attributes should be stripped from.
 * @param array|string $attributes Attributes that should be stripped from $elements.
 * @param boolean      $two_passes Whether the function should allow two passes.
 *
 * @return string HTML markup with attributes stripped.
 */
function equity_strip_attr( $text, $elements, $attributes, $two_passes = true ) {

	//* Cache elements pattern
	$elements_pattern = implode( '|', (array) $elements );

	//* Build patterns
	$patterns = array();
	foreach ( (array) $attributes as $attribute ) {
		//* Opening tags
		$patterns[] = sprintf( '~(<(?:%s)[^>]*)\s+%s=[\\\'"][^\\\'"]+[\\\'"]([^>]*[^>]*>)~', $elements_pattern, $attribute );

		//* Self closing tags
		$patterns[] = sprintf( '~(<(?:%s)[^>]*)\s+%s=[\\\'"][^\\\'"]+[\\\'"]([^>]*[^/]+/>)~', $elements_pattern, $attribute );
	}

	//* First pass
	$text = preg_replace( $patterns, '$1$2', $text );

	if ( $two_passes ) //* Second pass
		$text = preg_replace( $patterns, '$1$2', $text );

	return $text;

}

/**
 * Sanitize multiple HTML classes in one pass.
 *
 * Accepts either an array of `$classes`, or a space separated string of classes and sanitizes them using the
 * `sanitize_html_class()` WordPress function.
 *
 * @since 1.0
 *
 * @param $classes       array|string Classes to be sanitized.
 * @param $return_format string       Optional. The return format, 'input', 'string', or 'array'. Default is 'input'.
 *
 * @return array|string Sanitized classes.
 */
function equity_sanitize_html_classes( $classes, $return_format = 'input' ) {

	if ( 'input' === $return_format ) {
		$return_format = is_array( $classes ) ? 'array' : 'string';
	}

	$classes = is_array( $classes ) ? $classes : explode( ' ', $classes );

	$sanitized_classes = array_map( 'sanitize_html_class', $classes );

	if ( 'array' === $return_format )
		return $sanitized_classes;
	else
		return implode( ' ', $sanitized_classes );

}

/**
 * Return an array of allowed tags for output formatting.
 *
 * Mainly used by `wp_kses()` for sanitizing output.
 *
 * @since 1.0
 *
 * @return array Allowed tags.
 */
function equity_formatting_allowedtags() {

	return apply_filters(
		'equity_formatting_allowedtags',
		array(
			'a'          => array( 'href' => array(), 'title' => array(), ),
			'b'          => array(),
			'blockquote' => array(),
			'br'         => array(),
			'div'        => array( 'align' => array(), 'class' => array(), 'style' => array(), ),
			'em'         => array(),
			'i'          => array(),
			'p'          => array( 'align' => array(), 'class' => array(), 'style' => array(), ),
			'span'       => array( 'align' => array(), 'class' => array(), 'style' => array(), ),
			'strong'     => array(),

			//* <img src="" class="" alt="" title="" width="" height="" />
			//'img'        => array( 'src' => array(), 'class' => array(), 'alt' => array(), 'width' => array(), 'height' => array(), 'style' => array() ),
		)
	);

}

/**
 * Wrapper for `wp_kses()` that can be used as a filter function.
 *
 * @since 1.0
 *
 * @uses equity_formatting_allowedtags() List of allowed HTML elements.
 *
 * @param string $string Content to filter through kses.
 *
 * @return string
 */
function equity_formatting_kses( $string ) {

	return wp_kses( $string, equity_formatting_allowedtags() );

}

/**
 * Calculate the time difference - a replacement for `human_time_diff()` until it is improved.
 *
 * Based on BuddyPress function `bp_core_time_since()`, which in turn is based on functions created by
 * Dunstan Orchard - http://1976design.com
 *
 * This function will return an text representation of the time elapsed since a
 * given date, giving the two largest units e.g.:
 *
 *  - 2 hours and 50 minutes
 *  - 4 days
 *  - 4 weeks and 6 days
 *
 * @since 1.0
 *
 * @param $older_date int Unix timestamp of date you want to calculate the time since for`
 * @param $newer_date int Optional. Unix timestamp of date to compare older date to. Default false (current time)`
 *
 * @return str The time difference
 */
function equity_human_time_diff( $older_date, $newer_date = false ) {

	//* If no newer date is given, assume now
	$newer_date = $newer_date ? $newer_date : time();

	//* Difference in seconds
	$since = absint( $newer_date - $older_date );

	if ( ! $since )
		return '0 ' . _x( 'seconds', 'time difference', 'equity' );

	//* Hold units of time in seconds, and their pluralised strings (not translated yet)
	$units = array(
		array( 31536000, _nx_noop( '%s year', '%s years', 'time difference', 'equity' ) ),  // 60 * 60 * 24 * 365
		array( 2592000, _nx_noop( '%s month', '%s months', 'time difference', 'equity' ) ), // 60 * 60 * 24 * 30
		array( 604800, _nx_noop( '%s week', '%s weeks', 'time difference', 'equity' ) ),    // 60 * 60 * 24 * 7
		array( 86400, _nx_noop( '%s day', '%s days', 'time difference', 'equity' ) ),       // 60 * 60 * 24
		array( 3600, _nx_noop( '%s hour', '%s hours', 'time difference', 'equity' ) ),      // 60 * 60
		array( 60, _nx_noop( '%s minute', '%s minutes', 'time difference', 'equity' ) ),
		array( 1, _nx_noop( '%s second', '%s seconds', 'time difference', 'equity' ) ),
	);

	//* Step one: the first unit
	for ( $i = 0, $j = count( $units ); $i < $j; $i++ ) {
		$seconds = $units[$i][0];

		//* Finding the biggest chunk (if the chunk fits, break)
		if ( ( $count = floor( $since / $seconds ) ) != 0 )
			break;
	}

	//* Translate unit string, and add to the output
	$output = sprintf( translate_nooped_plural( $units[$i][1], $count, 'equity' ), $count );

	//* Note the next unit
	$ii = $i + 1;

	//* Step two: the second unit
	if ( $ii < $j ) {
		$seconds2 = $units[$ii][0];

		//* Check if this second unit has a value > 0
		if ( ( $count2 = floor( ( $since - ( $seconds * $count ) ) / $seconds2 ) ) !== 0 )
			//* Add translated separator string, and translated unit string
			$output .= sprintf( ' %s ' . translate_nooped_plural( $units[$ii][1], $count2, 'equity' ),	_x( 'and', 'separator in time difference', 'equity' ),	$count2	);
	}

	return $output;

}

/**
 * Mark up content with code tags.
 *
 * Escapes all HTML, so `<` gets changed to `&lt;` and displays correctly.
 *
 * Used almost exclusively within labels and text in user interfaces added by Equity.
 *
 * @since 1.0
 *
 * @param  string $content Content to be wrapped in code tags.
 *
 * @return string Content wrapped in code tags.
 */
function equity_code( $content ) {

	return '<code>' . esc_html( $content ) . '</code>';

}

/**
 * Removes extra HTML tags added by WP
 *
 * Used when shortcodes are wrapped in another shortcode, like rows and columns
 *
 * @since 1.0
 *
 * @param  string $content Content to be cleaned of extra WP inserted markup
 */
function equity_clean_shortcode($content){
      $fix = array (
                        '<br>' => '', 
                        '<br/>' => '', 
                        '&nbsp;' => '', 
                        '<p>' => '', 
                        '</p>' => '', 
                        '<p></p>' => '', 
                        '<p>[' => '[', 
                        ']</p>' => ']', 
                        ']<br />' => ']'
       );
    $content = strtr($content, $fix);
    return do_shortcode( $content );
}
