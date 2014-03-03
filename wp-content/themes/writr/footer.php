<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of <div id="content"> and all content after
 *
 * @package Writr
 */
$twitter     = get_theme_mod( 'jetpack-twitter' );
$facebook    = get_theme_mod( 'jetpack-facebook' );
$google_plus = get_theme_mod( 'jetpack-google_plus' );
$linkedin    = get_theme_mod( 'jetpack-linkedin' );
$tumblr      = get_theme_mod( 'jetpack-tumblr' );

$social_links = ( '' != $twitter
	|| '' != $facebook
	|| '' != $google_plus
	|| '' != $linkedin
	|| '' != $tumblr
) ? true : false;
?>

	</div><!-- #content -->

	<footer id="colophon" class="site-footer" role="contentinfo">
		<?php if ( $social_links ) : ?>
		<div id="social-links" class="clear">
			<ul class="social-links clear">
				<?php if ( '' != $twitter ) : ?>
				<li>
					<a href="<?php echo esc_url( $twitter ); ?>" class="genericon genericon-twitter" title="<?php esc_attr_e( 'Twitter', 'writr' ); ?>" target="_blank">
						<span class="screen-reader-text"><?php _e( 'Twitter', 'writr' ); ?></span>
					</a>
				</li>
				<?php endif; ?>

				<?php if ( '' != $facebook ) : ?>
				<li>
					<a href="<?php echo esc_url( $facebook ); ?>" class="genericon genericon-facebook" title="<?php esc_attr_e( 'Facebook', 'writr' ); ?>" target="_blank">
						<span class="screen-reader-text"><?php _e( 'Facebook', 'writr' ); ?></span>
					</a>
				</li>
				<?php endif; ?>

				<?php if ( '' != $google_plus ) : ?>
				<li>
					<a href="<?php echo esc_url( $google_plus ); ?>" class="genericon genericon-googleplus" title="<?php esc_attr_e( 'Google+', 'writr' ); ?>" target="_blank">
						<span class="screen-reader-text"><?php _e( 'Google+', 'writr' ); ?></span>
					</a>
				</li>
				<?php endif; ?>

				<?php if ( '' != $linkedin ) : ?>
				<li>
					<a href="<?php echo esc_url( $linkedin ); ?>" class="genericon genericon-linkedin-alt" title="<?php esc_attr_e( 'LinkedIn', 'writr' ); ?>" target="_blank">
						<span class="screen-reader-text"><?php _e( 'LinkedIn', 'writr' ); ?></span>
					</a>
				</li>
				<?php endif; ?>

				<?php if ( '' != $tumblr ) : ?>
				<li>
					<a href="<?php echo esc_url( $tumblr ); ?>" class="genericon genericon-tumblr" title="<?php esc_attr_e( 'Tumblr', 'writr' ); ?>" target="_blank">
						<span class="screen-reader-text"><?php _e( 'Tumblr', 'writr' ); ?></span>
					</a>
				</li>
				<?php endif; ?>
			</ul>
		</div><!-- #social-links -->
		<?php endif; ?>

		<div class="site-info">
			<?php do_action( 'writr_credits' ); ?>
			<div><a href="http://wordpress.org/" title="<?php esc_attr_e( 'A Semantic Personal Publishing Platform', 'writr' ); ?>" rel="generator"><?php printf( __( 'Proudly powered by %s', 'writr' ), 'WordPress' ); ?></a></div>
			<div><?php printf( __( 'Theme: %1$s by %2$s.', 'writr' ), 'Writr', '<a href="http://theme.wordpress.com/" rel="designer">Automattic</a>' ); ?></div>
		</div><!-- .site-info -->
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>