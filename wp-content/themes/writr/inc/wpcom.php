<?php
/**
 * WordPress.com-specific functions and definitions
 * This file is centrally included from `wp-content/mu-plugins/wpcom-theme-compat.php`.
 *
 * @package Writr
 */

/*
 * De-queue Google fonts if custom fonts are being used instead.
 */
function writr_dequeue_fonts() {
	if ( class_exists( 'TypekitData' ) && class_exists( 'CustomDesign' ) && CustomDesign::is_upgrade_active() ) {
		$customfonts = TypekitData::get( 'families' );

		if ( $customfonts['site-title']['id'] && $customfonts['headings']['id'] && $customfonts['body-text']['id'] )
			wp_dequeue_style( 'writr-montserrat' );
	}
}
add_action( 'wp_enqueue_scripts', 'writr_dequeue_fonts' );

/**
 * Adds support for wp.com-specific theme functions
 */
function writr_add_wpcom_support() {
	global $themecolors;

	if ( ! isset( $themecolors ) ) {

		// Set a default theme color array.
		$themecolors = array(
			'bg'     => 'ffffff',
			'border' => 'ffffff',
			'text'   => '656565',
			'link'   => '1abc9c',
			'url'    => '1abc9c',
		);

	}

	// Add print stylesheet.
	add_theme_support( 'print-style' );

}
add_action( 'after_setup_theme', 'writr_add_wpcom_support' );

/**
 * Enqueue wp.com-specific styles
 */
function writr_wpcom_styles() {
	wp_enqueue_style( 'writr-wpcom', get_template_directory_uri() . '/inc/style-wpcom.css', '20131011' );
}
add_action( 'wp_enqueue_scripts', 'writr_wpcom_styles' );

/**
 * Enqueue wp.com-specific Customizer styles
 */
function writr_wpcom_customize_register() {
	wp_enqueue_style( 'writr-wpcom-customizer', get_template_directory_uri() . '/inc/style-customizer-wpcom.css', '20131206' );
}
add_action( 'customize_register', 'writr_wpcom_customize_register' );