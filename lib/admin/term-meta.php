<?php
/**
 * Equity Framework
 *
 * WARNING: This file is part of the core Equity Framework. DO NOT edit this file under any circumstances.
 * Please do all modifications in the form of a child theme.
 *
 * @package Equity\Admin
 * @author  IDX, LLC
 * @license GPL-2.0+
 * @link    
 */

add_action( 'admin_init', 'equity_add_taxonomy_archive_options' );
/**
 * Add the archive options to each custom taxonomy edit screen.
 *
 * @since 1.0
 *
 * @see equity_taxonomy_archive_options() Callback for headline and introduction fields.
 */
function equity_add_taxonomy_archive_options() {

	foreach ( get_taxonomies( array( 'public' => true ) ) as $tax_name )
		add_action( $tax_name . '_edit_form', 'equity_taxonomy_archive_options', 10, 2 );

}

/**
 * Echo headline and introduction fields on the taxonomy term edit form.
 *
 * If populated, the values saved in these fields may display on taxonomy archives.
 *
 * @since 1.0
 *
 * @see equity_add_taxonomy_archive_options() Callback caller.
 *
 * @param \stdClass $tag      Term object.
 * @param string    $taxonomy Name of the taxonomy.
 */
function equity_taxonomy_archive_options( $tag, $taxonomy ) {

	$tax = get_taxonomy( $taxonomy );
	?>
	<h3><?php echo esc_html( $tax->labels->singular_name ) . ' ' . __( 'Archive Settings', 'equity' ); ?></h3>
	<table class="form-table">
		<tbody>
			<tr class="form-field">
				<th scope="row" valign="top"><label for="equity-meta[headline]"><?php _e( 'Archive Headline', 'equity' ); ?></label></th>
				<td>
					<input name="equity-meta[headline]" id="equity-meta[headline]" type="text" value="<?php echo esc_attr( $tag->meta['headline'] ); ?>" size="40" />
					<p class="description"><?php _e( 'Leave empty if you do not want to display a headline.', 'equity' ); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top"><label for="equity-meta[intro_text]"><?php _e( 'Archive Intro Text', 'equity' ); ?></label></th>
				<td>
					<textarea name="equity-meta[intro_text]" id="equity-meta[intro_text]" rows="5" cols="50" class="large-text"><?php echo esc_textarea( $tag->meta['intro_text'] ); ?></textarea>
					<p class="description"><?php _e( 'Leave empty if you do not want to display any intro text.', 'equity' ); ?></p>
				</td>
			</tr>
		</tbody>
	</table>
	<?php

}

add_action( 'admin_init', 'equity_add_taxonomy_layout_options' );
/**
 * Add the layout options to each custom taxonomy edit screen.
 *
 * @since 1.0
 *
 * @see equity_taxonomy_layout_options() Callback for layout selector.
 */
function equity_add_taxonomy_layout_options() {

	foreach ( get_taxonomies( array( 'public' => true ) ) as $tax_name )
		add_action( $tax_name . '_edit_form', 'equity_taxonomy_layout_options', 10, 2 );

}

/**
 * Echo the layout options on the taxonomy term edit form.
 *
 * @since 1.0
 *
 * @uses equity_layout_selector() Layout selector.
 *
 * @see equity_add_taxonomy_layout_options() Callback caller.
 *
 * @param \stdClass $tag      Term object.
 * @param string    $taxonomy Name of the taxonomy.
 */
function equity_taxonomy_layout_options( $tag, $taxonomy ) {

	?>
	<h3><?php _e( 'Layout Settings', 'equity' ); ?></h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row" valign="top"><?php _e( 'Choose Layout', 'equity' ); ?></th>
				<td>
					<div class="equity-layout-selector">
						<p>
							<input type="radio" class="default-layout" name="equity-meta[layout]" id="default-layout" value="" <?php checked( $tag->meta['layout'], '' ); ?> />
							<label for="default-layout" class="default"><?php printf( __( 'Default Layout set in <a href="%s">Customize</a>', 'equity' ), esc_url( admin_url( 'customize.php' ) ) ); ?></label>
						</p>

						<p><?php equity_layout_selector( array( 'name' => 'equity-meta[layout]', 'selected' => $tag->meta['layout'], 'type' => 'site' ) ); ?></p>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
	<?php

}

add_filter( 'get_term', 'equity_get_term_filter', 10, 2 );
/**
 * Merge term meta data into options table.
 *
 * Equity is forced to create its own term-meta data structure in the options table, since it is not support in core WP.
 *
 * Applies `equity_term_meta_defaults`, `equity_term_meta_{field}` and `equity_term_meta` filters.
 *
 * @since 1.0
 *
 * @param object $term     Database row object.
 * @param string $taxonomy Taxonomy name that $term is part of.
 *
 * @return object $term Database row object.
 */
function equity_get_term_filter( $term, $taxonomy ) {

	//* Do nothing, if $term is not object
	if ( ! is_object( $term ) ) {
		return $term;
	}

	//* Do nothing, if called in the context of creating a term via an ajax call
	if ( did_action( 'wp_ajax_add-tag' ) ) {
		return $term;
	}


	$db = get_option( 'equity-term-meta' );
	$term_meta = isset( $db[$term->term_id] ) ? $db[$term->term_id] : array();

	$term->meta = wp_parse_args( $term_meta, apply_filters( 'equity_term_meta_defaults', array(
		'headline'            => '',
		'intro_text'          => '',
		'display_title'       => 0, //* vestigial
		'display_description' => 0, //* vestigial
		'layout'              => '',
	) ) );

	//* Sanitize term meta
	foreach ( $term->meta as $field => $value )
		$term->meta[$field] = apply_filters( "equity_term_meta_{$field}", stripslashes( wp_kses_decode_entities( $value ) ), $term, $taxonomy );

	$term->meta = apply_filters( 'equity_term_meta', $term->meta, $term, $taxonomy );

	return $term;

}

add_filter( 'get_terms', 'equity_get_terms_filter', 10, 2 );
/**
 * Add Equity term-meta data to functions that return multiple terms.
 *
 * @since 1.0
 *
 * @param array  $terms    Database row objects.
 * @param string $taxonomy Taxonomy name that $terms are part of.
 *
 * @return array $terms Database row objects.
 */
function equity_get_terms_filter( array $terms, $taxonomy ) {

	foreach( $terms as $term )
		$term = equity_get_term_filter( $term, $taxonomy );

	return $terms;

}

add_action( 'edit_term', 'equity_term_meta_save', 10, 2 );
/**
 * Save term meta data.
 *
 * Fires when a user edits and saves a term.
 *
 * @since 1.0
 *
 * @uses equity_formatting_kses() Equity whitelist for wp_kses.
 *
 * @param integer $term_id Term ID.
 * @param integer $tt_id   Term Taxonomy ID.
 */
function equity_term_meta_save( $term_id, $tt_id ) {

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		return;

	$term_meta = (array) get_option( 'equity-term-meta' );

	$term_meta[$term_id] = isset( $_POST['equity-meta'] ) ? (array) $_POST['equity-meta'] : array();

	if ( ! current_user_can( 'unfiltered_html' ) && isset( $term_meta[$term_id]['archive_description'] ) )
		$term_meta[$term_id]['archive_description'] = equity_formatting_kses( $term_meta[$term_id]['archive_description'] );

	update_option( 'equity-term-meta', $term_meta );

}

add_action( 'delete_term', 'equity_term_meta_delete', 10, 2 );
/**
 * Delete term meta data.
 *
 * Fires when a user deletes a term.
 *
 * @since 1.0
 *
 * @param integer $term_id Term ID.
 * @param integer $tt_id   Taxonomy Term ID.
 */
function equity_term_meta_delete( $term_id, $tt_id ) {

	$term_meta = (array) get_option( 'equity-term-meta' );

	unset( $term_meta[$term_id] );

	update_option( 'equity-term-meta', (array) $term_meta );

}
