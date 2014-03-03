<?php
/**
 * Writr Theme Customizer
 *
 * @package Writr
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function writr_customize_register( $wp_customize ) {
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
	$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

	// Create the Theme Options section
	$wp_customize->add_section( 'writr_theme_options', array(
		'title'             => __( 'Theme Options', 'writr' ),
		'priority'          => 200,
	) );
	$wp_customize->add_setting( 'writr_background_size', array(
		'default'		    => '',
		'type'			    => 'theme_mod',
		'capability'	    => 'edit_theme_options',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( 'writr_background_size', array(
		'label'			    => __( 'Full Page Background Image', 'writr' ),
		'section'		    => 'writr_theme_options',
		'type'              => 'checkbox',
		'priority'		    => 1,
	) );
	$wp_customize->add_setting( 'writr_wider_style', array(
		'default'		    => '',
		'type'			    => 'theme_mod',
		'capability'	    => 'edit_theme_options',
	) );
	$wp_customize->add_control( 'writr_wider_style', array(
		'label'			    => __( 'Wider Content Area', 'writr' ),
		'section'		    => 'writr_theme_options',
		'type'              => 'checkbox',
		'priority'		    => 2,
	) );
	$wp_customize->add_setting( 'writr_color_scheme', array(
		'default'		    => 'default',
		'type'			    => 'theme_mod',
		'capability'	    => 'edit_theme_options',
	) );
	$wp_customize->add_control( 'writr_color_scheme', array(
		'label'			    => __( 'Color Scheme', 'writr' ),
		'section'		    => 'writr_theme_options',
		'type'              => 'select',
		'choices'			=> array(
			'default'       => __( 'Turquoise', 'writr' ),
			'blue'          => __( 'Blue', 'writr' ),
			'green'         => __( 'Green', 'writr' ),
			'grey'          => __( 'Grey', 'writr' ),
			'purple'        => __( 'Purple', 'writr' ),
			'red'           => __( 'Red', 'writr' ),
		),
		'priority'		    => 3,
	) );
}
add_action( 'customize_register', 'writr_customize_register' );

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function writr_customize_preview_js() {
	wp_enqueue_script( 'writr_customizer', get_template_directory_uri() . '/js/customizer.js', array( 'customize-preview' ), '20130508', true );
}
add_action( 'customize_preview_init', 'writr_customize_preview_js' );
