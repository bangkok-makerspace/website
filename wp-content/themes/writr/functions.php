<?php
/**
 * Writr functions and definitions
 *
 * @package Writr
 */

/**
 * Set the content width based on the theme's design and stylesheet.
 */
if ( ! isset( $content_width ) )
	$content_width = 460; /* pixels */

/**
 * Adjust the content width for if the Wider Content Area option is ticked.
 */
function writr_set_content_width() {
	global $content_width;

	if ( get_theme_mod( 'writr_wider_style' ) )
		$content_width = 720;
}
add_action( 'template_redirect', 'writr_set_content_width' );

if ( ! function_exists( 'writr_setup' ) ) :
/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which runs
 * before the init hook. The init hook is too late for some features, such as indicating
 * support post thumbnails.
 */
function writr_setup() {
	/**
	 * Make theme available for translation.
	 * Translations can be filed in the /languages/ directory.
	 * If you're building a theme based on Writr, use a find and replace
	 * to change 'writr' to the name of your theme in all the template files.
	 */
	load_theme_textdomain( 'writr', get_template_directory() . '/languages' );

	/**
	 * Add default posts and comments RSS feed links to head.
	 */
	add_theme_support( 'automatic-feed-links' );

	/**
	 * Editor styles for the win
	 */
	if ( get_theme_mod( 'writr_wider_style' ) )
		add_editor_style( 'editor-style-wider.css' );
	else
		add_editor_style( 'editor-style.css' );

	/**
	 * Enable support for Post Thumbnails on posts and pages.
	 *
	 * @link http://codex.wordpress.org/Function_Reference/add_theme_support#Post_Thumbnails
	 */
	add_theme_support( 'post-thumbnails' );

	$image_width = 767;
	if ( get_theme_mod( 'writr_wider_style' ) )
		$image_width = 800;
	add_image_size( 'featured-image', $image_width, 9999 );

	/**
	 * This theme uses wp_nav_menu() in one location.
	 */
	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'writr' ),
	) );

	/**
	 * Enable support for Post Formats.
	 */
	add_theme_support( 'post-formats', array(
		'aside',
		'image',
		'video',
		'quote',
		'link',
		'gallery',
		'audio',
		'status',
		'chat',
	) );

	/**
	 * Setup the WordPress core custom background feature.
	 */
	add_theme_support( 'custom-background', apply_filters( 'writr_custom_background_args', array(
		'default-color' => '303030',
		'default-image' => '',
	) ) );
}
endif; // writr_setup
add_action( 'after_setup_theme', 'writr_setup' );

/**
 * Register widgetized area and update sidebar with default widgets.
 */
function writr_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Sidebar', 'writr' ),
		'id'            => 'sidebar-1',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h1 class="widget-title">',
		'after_title'   => '</h1>',
	) );
}
add_action( 'widgets_init', 'writr_widgets_init' );

/**
 * Register Google fonts.
 */
function writr_fonts() {
	/* translators: If there are characters in your language that are not supported
	   by Montserrat, translate this to 'off'. Do not translate into your own language. */
	if ( 'off' !== _x( 'on', 'Montserrat font: on or off', 'writr' ) ) {

		$protocol = is_ssl() ? 'https' : 'http';

		wp_register_style( 'writr-montserrat', "$protocol://fonts.googleapis.com/css?family=Montserrat:400,700", array(), null );

	}
}
add_action( 'init', 'writr_fonts' );

/**
 * Enqueue scripts and styles.
 */
function writr_scripts() {
	wp_enqueue_style( 'writr-montserrat' );

	if ( wp_style_is( 'genericons', 'registered' ) )
		wp_enqueue_style( 'genericons' );
	else
		wp_enqueue_style( 'genericons', get_template_directory_uri() . '/css/genericons.css', array(), null );

	wp_enqueue_style( 'writr-style', get_stylesheet_uri() );

	$colorscheme = get_theme_mod( 'writr_color_scheme' );
	if ( $colorscheme && 'default' !== $colorscheme )
		wp_enqueue_style( 'writr-color-scheme', get_template_directory_uri() . '/css/' . $colorscheme . '.css' , array(), null );

	if ( get_theme_mod( 'writr_wider_style' ) )
		wp_enqueue_style( 'writr-wider-style', get_template_directory_uri() . '/css/wider.css' , array(), null );

	wp_enqueue_script( 'writr-navigation', get_template_directory_uri() . '/js/navigation.js', array(), '20120206', true );

	wp_enqueue_script( 'writr-skip-link-focus-fix', get_template_directory_uri() . '/js/skip-link-focus-fix.js', array(), '20130115', true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) )
		wp_enqueue_script( 'comment-reply' );

	if ( is_singular() && wp_attachment_is_image() )
		wp_enqueue_script( 'writr-keyboard-image-navigation', get_template_directory_uri() . '/js/keyboard-image-navigation.js', array( 'jquery' ), '20120202' );

	wp_enqueue_script( 'writr-script', get_template_directory_uri() . '/js/writr.js', array( 'jquery' ), '20130905', true );

}
add_action( 'wp_enqueue_scripts', 'writr_scripts' );


/**
 * Enqueue Google fonts style to admin screen for custom header display.
 */
function writr_admin_fonts( $hook_suffix ) {
	if ( 'appearance_page_custom-header' != $hook_suffix )
		return;

	wp_enqueue_style( 'writr-montserrat' );

}
add_action( 'admin_enqueue_scripts', 'writr_admin_fonts' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Custom functions that act independently of the theme templates.
 */
require get_template_directory() . '/inc/extras.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( file_exists( get_template_directory() . '/inc/jetpack.php' ) )
	require get_template_directory() . '/inc/jetpack.php';