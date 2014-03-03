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
				the_title( '<h1 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h1>' );
			endif;
		?>

		<?php if ( '' != get_the_post_thumbnail() && '' == $format ) : ?>
		<div class="entry-thumbnail">
			<a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Permalink to %s', 'writr' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="<?php the_ID(); ?>">
				<?php the_post_thumbnail( 'featured-image' ); ?>
			</a>
		</div><!-- .post-thumbnail -->
		<?php endif; ?>

		<?php if ( is_sticky() && is_front_page() && ! is_paged() ) : ?>
			<span class="entry-format-badge genericon genericon-star"><span class="screen-reader-text"><?php _e( 'Sticky', 'writr' ); ?></span></span>
		<?php elseif ( $format && in_array( $format, $formats[0] ) ) : ?>
			<a class="entry-format-badge genericon genericon-<?php echo $format; ?>" href="<?php echo esc_url( get_post_format_link( $format ) ); ?>" title="<?php echo esc_attr( sprintf( __( 'All %s posts', 'writr' ), get_post_format_string( $format ) ) ); ?>"><span class="screen-reader-text"><?php echo get_post_format_string( $format ); ?></span></a>
		<?php else : ?>
			<span class="entry-format-badge genericon genericon-standard"><span class="screen-reader-text"><?php _e( 'Standard', 'writr' ); ?></span></span>
		<?php endif; ?>
	</header><!-- .entry-header -->

	<?php if ( is_search() ) : // Only display Excerpts for Search ?>
	<div class="entry-summary">
		<?php the_excerpt(); ?>
	</div><!-- .entry-summary -->
	<?php else : ?>
	<div class="entry-content">
		<?php the_content( __( 'Continue reading', 'writr' ) ); ?>
		<?php
			wp_link_pages( array(
				'before' => '<div class="page-links">' . __( 'Pages:', 'writr' ),
				'after'  => '</div>',
			) );
		?>
	</div><!-- .entry-content -->
	<?php endif; ?>

	<?php if ( 'post' == get_post_type() ) : // Hide meta for pages on Search ?>
	<footer class="entry-meta">

		<ul class="clear">
			<?php writr_meta(); ?>
		</ul>

	</footer><!-- .entry-meta -->
	<?php endif; ?>
</article><!-- #post-## -->
