<?php
/**
 * Sample implementation of the Custom Header feature
 * http://codex.wordpress.org/Custom_Headers
 *
 * @package Writr
 */

/**
 * Setup the WordPress core custom header feature.
 *
 * @uses writr_header_style()
 * @uses writr_admin_header_style()
 * @uses writr_admin_header_image()
 *
 * @package Writr
 */
function writr_custom_header_setup() {
	add_theme_support( 'custom-header', apply_filters( 'writr_custom_header_args', array(
		'default-image'          => writr_get_default_header_image(),
		'default-text-color'     => 'fff',
		'width'                  => 120,
		'height'                 => 120,
		'flex-width'             => true,
		'flex-height'            => true,
		'wp-head-callback'       => 'writr_header_style',
		'admin-head-callback'    => 'writr_admin_header_style',
		'admin-preview-callback' => 'writr_admin_header_image',
	) ) );
}
add_action( 'after_setup_theme', 'writr_custom_header_setup' );

/**
 * A default header image
 *
 * Use the admin email's gravatar as the default header image.
 */
function writr_get_default_header_image() {

	// Get default from Discussion Settings.
	$default = get_option( 'avatar_default', 'mystery' ); // Mystery man default
	if ( 'mystery' == $default )
		$default = 'mm';
	elseif ( 'gravatar_default' == $default )
		$default = '';

	$protocol = ( is_ssl() ) ? 'https://secure.' : 'http://';
	$url = sprintf( '%1$sgravatar.com/avatar/%2$s/', $protocol, md5( get_option( 'admin_email' ) ) );
	$url = add_query_arg( array(
		's' => 120,
		'd' => urlencode( $default ),
	), $url );

	return esc_url_raw( $url );
} // writr_get_default_header_image

if ( ! function_exists( 'writr_header_style' ) ) :
/**
 * Styles the header image and text displayed on the blog
 *
 * @see writr_custom_header_setup().
 */
function writr_header_style() {
	$header_text_color = get_header_textcolor();

	// If no custom options for text are set, let's bail
	// get_header_textcolor() options: HEADER_TEXTCOLOR is default, hide text (returns 'blank') or any hex value
	if ( HEADER_TEXTCOLOR == $header_text_color )
		return;
	// If we get this far, we have custom styles. Let's do this.
	?>
	<style type="text/css">
	<?php
		// Has the text been hidden?
		if ( ! display_header_text() ) :
	?>
		.site-title,
		.site-description {
			position: absolute;
			clip: rect(1px 1px 1px 1px); /* IE6, IE7 */
			clip: rect(1px, 1px, 1px, 1px);
		}
		.header-image {
			margin-bottom: 0;
		}
	<?php
		// If the user has set a custom color for the text use that
		else :
	?>
		.site-title a,
		.site-description {
			color: #<?php echo $header_text_color; ?>;
		}
	<?php endif; ?>
	</style>
	<?php
}
endif; // writr_header_style

if ( ! function_exists( 'writr_admin_header_style' ) ) :
/**
 * Styles the header image displayed on the Appearance > Header admin panel.
 *
 * @see writr_custom_header_setup().
 */
function writr_admin_header_style() {
?>
	<style type="text/css">
	.appearance_page_custom-header #headimg {
		border: none;
		text-align: center;
	}
	<?php if ( ! display_header_text() ) : ?>
	#headimg h1,
	#desc {
		display: none;
	}
	<?php endif; ?>
	#headimg h1 {
		padding: 0 0 5px;
		margin: 0;
		font: bold 20px/1.2 Montserrat, sans-serif;
		text-transform: uppercase;
	}
	#headimg h1 a {
		text-decoration: none;
	}
	#desc {
		font: 12px/1.3 Montserrat, sans-serif;
		text-transform: uppercase;
	}
	#headimg img {
		margin-bottom: 20px;
		max-width: 300px;
		height: auto;
	}
	#headimg img[src*="gravatar"] {
		-webkit-border-radius: 50%;
		-moz-border-radius:    50%;
		border-radius:         50%;
	}
	</style>
<?php
}
endif; // writr_admin_header_style

if ( ! function_exists( 'writr_admin_header_image' ) ) :
/**
 * Custom header image markup displayed on the Appearance > Header admin panel.
 *
 * @see writr_custom_header_setup().
 */
function writr_admin_header_image() {
	$style        = sprintf( ' style="color:#%s;"', get_header_textcolor() );
	$header_image = get_header_image();
?>
	<div id="headimg">
		<?php if ( ! empty( $header_image ) ) : ?>
		<img src="<?php echo esc_url( $header_image ); ?>" class="no-grav" alt="">
		<?php endif; ?>
		<h1 class="displaying-header-text"><a id="name"<?php echo $style; ?> onclick="return false;" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></h1>
		<div class="displaying-header-text" id="desc"<?php echo $style; ?>><?php bloginfo( 'description' ); ?></div>
	</div>
<?php
}
endif; // writr_admin_header_image
