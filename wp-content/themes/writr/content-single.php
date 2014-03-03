<?php
/**
 * @package Writr
 */
$format = get_post_format();
$formats = get_theme_support( 'post-formats' );
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php
			if ( 'link' == $format ) :
				the_title( '<h1 class="entry-title"><a href="' . esc_url( writr_get_link_url() ) . '" rel="bookmark">', '</a></h1>' );
			else :
				the_title( '<h1 class="entry-title">', '</h1>' );
			endif;
		?>

		<?php if ( '' != get_the_post_thumbnail() && '' == $format ) : ?>
		<div class="entry-thumbnail">
			<?php the_post_thumbnail( 'featured-image' ); ?>
		</div><!-- .post-thumbnail -->
		<?php endif; ?>

		<?php if ( $format && in_array( $format, $formats[0] ) ) : ?>
			<a class="entry-format-badge genericon genericon-<?php echo $format; ?>" href="<?php echo esc_url( get_post_format_link( $format ) ); ?>" title="<?php echo esc_attr( sprintf( __( 'All %s posts', 'writr' ), get_post_format_string( $format ) ) ); ?>"><span class="screen-reader-text"><?php echo get_post_format_string( $format ); ?></span></a>
		<?php else : ?>
			<span class="entry-format-badge genericon genericon-standard"><span class="screen-reader-text"><?php _e( 'Standard', 'writr' ); ?></span></span>
		<?php endif; ?>
	</header><!-- .entry-header -->

	<div class="entry-content">
		<?php the_content(); ?>
		<?php
			wp_link_pages( array(
				'before' => '<div class="page-links">' . __( 'Pages:', 'writr' ),
				'after'  => '</div>',
			) );
		?>
	</div><!-- .entry-content -->

	<footer class="entry-meta">

		<ul class="clear">
			<?php writr_meta(); ?>
		</ul>

	</footer><!-- .entry-meta -->
</article><!-- #post-## -->
