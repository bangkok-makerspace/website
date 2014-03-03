<?php
/**
 * Custom functions that act independently of the theme templates
 *
 * Eventually, some of the functionality here could be replaced by core features
 *
 * @package Writr
 */

/**
 * Get our wp_nav_menu() fallback, wp_page_menu(), to show a home link.
 */
function writr_page_menu_args( $args ) {
	$args['show_home'] = true;
	return $args;
}
add_filter( 'wp_page_menu_args', 'writr_page_menu_args' );

/**
 * Adds custom classes to the array of body classes.
 */
function writr_body_classes( $classes ) {
	// Adds a class of group-blog to blogs with more than 1 published author
	if ( is_multi_author() )
		$classes[] = 'group-blog';

	// Adds a class if Full Page Background Image option is ticked
	if ( '' != get_theme_mod( 'writr_background_size' ) )
		$classes[] = 'custom-background-size';

	$colorscheme = get_theme_mod( 'writr_color_scheme' );
	if ( $colorscheme && 'default' !== $colorscheme )
		$classes[] = 'color-scheme-' . $colorscheme;

	// Adds a class to control the sidebar status
	$classes[] = 'sidebar-closed';

	return $classes;
}
add_filter( 'body_class', 'writr_body_classes' );

/**
 * Filter in a link to a content ID attribute for the next/previous image links on image attachment pages
 */
function writr_enhanced_image_navigation( $url, $id ) {
	if ( ! is_attachment() && ! wp_attachment_is_image( $id ) )
		return $url;

	$image = get_post( $id );
	if ( ! empty( $image->post_parent ) && $image->post_parent != $id )
		$url .= '#main';

	return $url;
}
add_filter( 'attachment_link', 'writr_enhanced_image_navigation', 10, 2 );

/**
 * Filters wp_title to print a neat <title> tag based on what is being viewed.
 */
function writr_wp_title( $title, $sep ) {
	global $page, $paged;

	if ( is_feed() )
		return $title;

	// Add the blog name
	$title .= get_bloginfo( 'name' );

	// Add the blog description for the home/front page.
	$site_description = get_bloginfo( 'description', 'display' );
	if ( $site_description && ( is_home() || is_front_page() ) )
		$title .= " $sep $site_description";

	// Add a page number if necessary:
	if ( $paged >= 2 || $page >= 2 )
		$title .= " $sep " . sprintf( __( 'Page %s', 'writr' ), max( $paged, $page ) );

	return $title;
}
add_filter( 'wp_title', 'writr_wp_title', 10, 2 );

/**
 * Returns the URL from the post.
 *
 * @uses get_the_link() to get the URL in the post meta (if it exists) or
 * the first link found in the post content.
 *
 * Falls back to the post permalink if no URL is found in the post.
 *
 * @return string URL
 */
function writr_get_link_url() {
	$content = get_the_content();
	$has_url = get_url_in_content( $content );

	return ( $has_url ) ? $has_url : apply_filters( 'the_permalink', get_permalink() );
}

/**
 * Use &hellip; instead of [...] for excerpts.
 */
function writr_excerpt_more( $more ) {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'writr_excerpt_more' );

/**
 * Wrap more link
 */
function writr_more_link( $link ) {
	return '<span class="more-link-wrapper">' . $link . '</span>';
}
add_filter( 'the_content_more_link', 'writr_more_link' );

/**
 * Adds a wrapper to videos
 *
 * @return string
 */
function writr_responsive_videos_embed_html( $html ) {

	if ( empty( $html ) || ! is_string( $html ) )
		return $html;

	wp_enqueue_script( 'writr-responsive-videos', get_template_directory_uri() . '/js/responsive-videos.js', array( 'jquery', 'underscore' ), '20130102', true );

	$html = '<div class="video-wrapper">' . $html . '</div>';
	return $html;

}
add_filter( 'embed_oembed_html', 'writr_responsive_videos_embed_html', 10, 3 );
add_filter( 'video_embed_html', 'writr_responsive_videos_embed_html' );

/**
 * Decrease caption width for non-full-width images.
 */
function writr_shortcode_atts_caption( $attr ) {
	global $content_width;

	if ( isset( $attr['width'] ) && $attr['width'] < $content_width )
		$attr['width'] -= 10;

	return $attr;
}
add_filter( 'shortcode_atts_caption', 'writr_shortcode_atts_caption' );

/**
 * Creates an HTML list of nav menu items that introduces multi-levels.
 */
class writr_nav_walker extends Walker_Nav_Menu {

	// Each time an element is the child of the prior element, this is called.
	function start_lvl( &$output, $depth = 0, $args = array() ) {

		if ( $depth >= 1 )
			$output .= apply_filters( 'walker_nav_menu_start_lvl', '<ul class="dropdown-menu submenu-hide">', $depth, $args );
		else
			$output .= apply_filters( 'walker_nav_menu_start_lvl', '<ul class="dropdown-menu">', $depth, $args );

	}

	// Each time an individual element is processed, start_el is called.
	function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {

		$css_classes = implode( ' ', $object->classes );
		$target = isset( $object->target ) && '' != $object->target ? ' target="_blank" ' : '';

		// If the current menu item has children, we need to set the proper class names on the list items and the anchors. Parent menu items can't have blank targets.
		if ( $args->has_children ) {

			if ( $object->menu_item_parent == 0 ) {

				$menu_item = get_permalink() == $object->url ? '<li class="dropdown ' . $css_classes . '">' : '<li class="dropdown ' . $css_classes . '">';
				$menu_item .= '<a href="' . $object->url . '" class="dropdown-toggle"' . '>';

			} else {

				$menu_item = '<li class="dropdown submenu ' . $css_classes . '">';
				$menu_item .= '<a href="' . $object->url . '" class="dropdown-toggle"' . $target . '>';

			}

		} else {

			$menu_item = get_permalink() == $object->url ? '<li class="active ' . $css_classes . '">' : '<li class="' . $css_classes . '">';
			$menu_item .= '<a href="' . $object->url . '"' . $target . '>';

		}

		// Render the actual menu title
		$menu_item .= $object->title;

		// Close the anchor
		$menu_item .= '</a>';

		$output .= apply_filters ( 'nav_walker_start_el', $menu_item, $object, $depth, $args );

	}

	// Set a value in the element's arguments that allow us to determine if the current menu item has children.
	function display_element( $element, &$children_elements, $max_depth, $depth = 0, $args, &$output ) {

		$id_field = $this->db_fields['id'];
		if ( is_object( $args[0] ) )
			$args[0]->has_children = ! empty( $children_elements[$element->$id_field] );

		return parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );

	}

	// Each time an element is processed, end_el is called after start_el.
	function end_el( &$output, $object, $depth = 0, $args = array() ) {

		$output .= apply_filters( 'nav_walker_end_el', '</li>', $object, $depth, $args );

	}

	// Each time an element is no longer below on of the current parents, this is called.
	function end_lvl( &$output, $depth = 0, $args = array() ) {

		$output .= apply_filters( 'nav_walker_end_lvl', '</ul>', $depth, $args );

	}

}
