<?php
/**
 * Jetpack Compatibility File
 * See: http://jetpack.me/
 *
 * @package Writr
 */

function writr_jetpack_setup() {

	/**
	 * Add theme support for Infinite Scroll.
	 * See: http://jetpack.me/support/infinite-scroll/
	 */
	add_theme_support( 'infinite-scroll', array(
		'container' => 'main',
		'footer'    => 'page',
	) );

	/**
	 * Add theme support for Social Links.
	 * See: http://jetpack.me/support/social-links/
	 */
	add_theme_support( 'social-links', array(
    	'facebook',
    	'twitter',
    	'google_plus',
    	'linkedin',
    	'tumblr',
	) );

}
add_action( 'after_setup_theme', 'writr_jetpack_setup' );

/**
 * Unregister Gallery Widget. Not working properly.
 */
function writr_unregister_widget() {

	if ( function_exists( 'wpcom_gallery_widget_init' ) )
		unregister_widget( 'WPCOM_Gallery_Widget' );

	if ( function_exists( 'jetpack_gallery_widget_init' ) )
		unregister_widget( 'Jetpack_Gallery_Widget' );

}
add_action( 'widgets_init', 'writr_unregister_widget', 99 );