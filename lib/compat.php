<?php
/**
 * Temporary compatibility shims for features present in Gutenberg, pending
 * upstream commit to the WordPress core source repository. Functions here
 * exist only as long as necessary for corresponding WordPress support, and
 * each should be associated with a Trac ticket.
 *
 * @package gutenberg
 */

/**
 * Filters allowed CSS attributes to include `flex-basis`, included in saved
 * markup of the Column block.
 *
 * This can be removed when plugin support requires WordPress 5.3.0+.
 *
 * @see https://core.trac.wordpress.org/ticket/47281
 * @see https://core.trac.wordpress.org/changeset/45363
 *
 * @since 5.7.0
 *
 * @param string[] $attr Array of allowed CSS attributes.
 *
 * @return string[] Filtered array of allowed CSS attributes.
 */
function gutenberg_safe_style_css_column_flex_basis( $attr ) {
	$attr[] = 'flex-basis';

	return $attr;
}
add_filter( 'safe_style_css', 'gutenberg_safe_style_css_column_flex_basis' );

/**
 * Adds a polyfill for the WHATWG URL in environments which do not support it.
 * The intention in how this action is handled is under the assumption that this
 * code would eventually be placed at `wp_default_packages_vendor`, which is
 * called as a result of `wp_default_packages` via the `wp_default_scripts`.
 *
 * This can be removed when plugin support requires WordPress 5.4.0+.
 *
 * @see https://core.trac.wordpress.org/ticket/49360
 * @see https://developer.mozilla.org/en-US/docs/Web/API/URL/URL
 * @see https://developer.wordpress.org/reference/functions/wp_default_packages_vendor/
 *
 * @since 7.3.0
 *
 * @param WP_Scripts $scripts WP_Scripts object.
 */
function gutenberg_add_url_polyfill( $scripts ) {
	// Only register polyfill if not already registered. This prevents handling
	// in an environment where core has updated to manage the polyfill. This
	// depends on the action being handled after default script registration.
	$is_polyfill_script_registered = (bool) $scripts->query( 'wp-polyfill-url', 'registered' );
	if ( $is_polyfill_script_registered ) {
		return;
	}

	gutenberg_register_vendor_script(
		$scripts,
		'wp-polyfill-url',
		'https://unpkg.com/polyfill-library@3.42.0/polyfills/URL/polyfill.js',
		array(),
		'3.42.0'
	);

	did_action( 'init' ) && $scripts->add_inline_script(
		'wp-polyfill',
		wp_get_script_polyfill(
			$scripts,
			array(
				'\'URL\' in window' => 'wp-polyfill-url',
			)
		)
	);
}
add_action( 'wp_default_scripts', 'gutenberg_add_url_polyfill', 20 );

/**
 * Adds a polyfill for DOMRect in environments which do not support it.
 *
 * This can be removed when plugin support requires WordPress 5.4.0+.
 *
 * @see gutenberg_add_url_polyfill
 * @see https://core.trac.wordpress.org/ticket/49360
 * @see https://developer.mozilla.org/en-US/docs/Web/API/DOMRect
 * @see https://developer.wordpress.org/reference/functions/wp_default_packages_vendor/
 *
 * @since 7.5.0
 *
 * @param WP_Scripts $scripts WP_Scripts object.
 */
function gutenberg_add_dom_rect_polyfill( $scripts ) {
	// Only register polyfill if not already registered. This prevents handling
	// in an environment where core has updated to manage the polyfill. This
	// depends on the action being handled after default script registration.
	$is_polyfill_script_registered = (bool) $scripts->query( 'wp-polyfill-dom-rect', 'registered' );
	if ( $is_polyfill_script_registered ) {
		return;
	}

	gutenberg_register_vendor_script(
		$scripts,
		'wp-polyfill-dom-rect',
		'https://unpkg.com/polyfill-library@3.42.0/polyfills/DOMRect/polyfill.js',
		array(),
		'3.42.0'
	);

	did_action( 'init' ) && $scripts->add_inline_script(
		'wp-polyfill',
		wp_get_script_polyfill(
			$scripts,
			array(
				'window.DOMRect' => 'wp-polyfill-dom-rect',
			)
		)
	);
}
add_action( 'wp_default_scripts', 'gutenberg_add_dom_rect_polyfill', 20 );

/**
 * Sets the current post for usage in template blocks.
 *
 * @return WP_Post|null The post if any, or null otherwise.
 */
function gutenberg_get_post_from_context() {
	// TODO: Without this temporary fix, an infinite loop can occur where
	// posts with post content blocks render themselves recursively.
	if ( is_admin() || defined( 'REST_REQUEST' ) ) {
		return null;
	}
	if ( ! in_the_loop() ) {
		rewind_posts();
		the_post();
	}
	return get_post();
}

/**
 * Shim that hooks into `pre_render_block` so as to override `render_block`
 * with a function that passes `render_callback` the block object as an
 * argument and adds support for block context attributes.
 *
 * @param string $pre_render The pre-rendered content. Defaults to null.
 * @param array  $block      The block being rendered.
 * @param array  $context    The block context.
 *
 * @return string String of rendered HTML.
 */
function gutenberg_provide_render_callback_with_block_object( $pre_render, $block, $context = array() ) {
	global $post;

	$source_block = $block;
	/** This filter is documented in src/wp-includes/blocks.php */
	$block         = apply_filters( 'render_block_data', $block, $source_block );
	$block_type    = WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );
	$is_dynamic    = $block['blockName'] && null !== $block_type && $block_type->is_dynamic();
	$block_content = '';
	$index         = 0;

	if ( ! is_array( $block['attrs'] ) ) {
		$block['attrs'] = array();
	}

	$block['context'] = array();
	$next_context     = $context;
	if ( $block_type ) {
		if ( isset( $block_type->context ) ) {
			foreach ( $block_type->context as $attribute_name => $block_names ) {
				$block_names = is_array( $block_names ) ? $block_names : array( $block_names );
				foreach ( $block_names as $block_name ) {
					if ( isset( $context[ $block_name ][ $attribute_name ] ) ) {
						$block['context'][ $attribute_name ] = $context[ $block_name ][ $attribute_name ];
					}
				}
			}
		}
		if ( isset( $block_type->attributes ) ) {
			foreach ( $block_type->attributes as $attribute_name => $attribute_config ) {
				if ( $attribute_config['context'] && isset( $block['attrs'][ $attribute_name ] ) ) {
					if ( ! isset( $next_context[ $block['blockName'] ] ) ) {
						$next_context[ $block['blockName'] ] = array();
					}
					$next_context[ $block['blockName'] ][ $attribute_name ] = $block['attrs'][ $attribute_name ];
				}
			}
		}
	}

	foreach ( $block['innerContent'] as $chunk ) {
		if ( is_string( $chunk ) ) {
			$block_content .= $chunk;
		} else {
			$inner_block    = $block['innerBlocks'][ $index++ ];
			$block_content .= gutenberg_provide_render_callback_with_block_object( null, $inner_block, $next_context );
		}
	}

	if ( $is_dynamic ) {
		$global_post = $post;

		$prepared_attributes = $block_type->prepare_attributes_for_render( $block['attrs'] );
		$block_content       = (string) call_user_func( $block_type->render_callback, $prepared_attributes, $block_content, $block );
		$post                = $global_post;
	}
	/** This filter is documented in src/wp-includes/blocks.php */
	return apply_filters( 'render_block', $block_content, $block );
}
add_filter( 'pre_render_block', 'gutenberg_provide_render_callback_with_block_object', 10, 2 );
