<?php
/**
 * The template for displaying image attachments.
 *
 * @package Writr
 */

get_header();
?>

	<div id="primary" class="content-area image-attachment">
		<main id="main" class="site-main" role="main">

		<?php while ( have_posts() ) : the_post(); ?>

			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<header class="entry-header">
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

					<span class="entry-format-badge genericon genericon-image"></span>
				</header><!-- .entry-header -->

				<div class="entry-content">
					<div class="entry-attachment">
						<div class="attachment">
							<?php writr_the_attached_image(); ?>
						</div><!-- .attachment -->

						<?php if ( has_excerpt() ) : ?>
						<div class="entry-caption">
							<?php the_excerpt(); ?>
						</div><!-- .entry-caption -->
						<?php endif; ?>
					</div><!-- .entry-attachment -->

					<?php
						the_content();
						wp_link_pages( array(
							'before' => '<div class="page-links">' . __( 'Pages:', 'writr' ),
							'after'  => '</div>',
						) );
					?>
				</div><!-- .entry-content -->

				<footer class="entry-meta">
					<ul class="clear">
						<li class="date-meta">
							<div class="genericon genericon-month"></div>
							<span class="screen-reader-text"><?php _e( 'Date', 'writr' ); ?></span>
							<?php the_time( get_option( 'date_format' ) ); ?>
						</li>
						<li class="size-meta">
							<div class="genericon genericon-cog"></div>
							<span class="screen-reader-text"><?php _e( 'Size', 'writr' ); ?></span>
							<a href="<?php echo esc_url( wp_get_attachment_url() ); ?>" title="Link to full-size image">
								<?php
									$metadata = wp_get_attachment_metadata();
									echo $metadata['width'] . ' &times; ' . $metadata['height'];
								?>
							</a>
						</li>
						<li class="comment-meta">
							<div class="genericon genericon-comment"></div>
							<?php
								if ( comments_open() && pings_open() ) : // Comments and trackbacks open
									printf( __( '<a class="comment-link" href="#respond" title="Post a comment">Post a comment</a> or leave a trackback: <a class="trackback-link" href="%s" title="Trackback URL for your post" rel="trackback">Trackback URL</a>.', 'writr' ), esc_url( get_trackback_url() ) );
								elseif ( ! comments_open() && pings_open() ) : // Only trackbacks open
									printf( __( 'Comments are closed, but you can leave a trackback: <a class="trackback-link" href="%s" title="Trackback URL for your post" rel="trackback">Trackback URL</a>.', 'writr' ), esc_url( get_trackback_url() ) );
								elseif ( comments_open() && ! pings_open() ) : // Only comments open
									 _e( 'Trackbacks are closed, but you can <a class="comment-link" href="#respond" title="Post a comment">post a comment</a>.', 'writr' );
								elseif ( ! comments_open() && ! pings_open() ) : // Comments and trackbacks closed
									_e( 'Both comments and trackbacks are currently closed.', 'writr' );
								endif;
							?>
						</li>
						<?php edit_post_link( __( 'Edit', 'writr' ), '<li class="edit-link"><div class="genericon genericon-edit"></div> ', '</li>' );	?>
					</ul>
				</footer><!-- .entry-meta -->
			</article><!-- #post-## -->

			<nav role="navigation" id="image-navigation" class="image-navigation">
				<div class="nav-previous"><?php previous_image_link( false, __( '<span class="genericon genericon-leftarrow"></span> Previous', 'writr' ) ); ?></div>
				<div class="nav-next"><?php next_image_link( false, __( 'Next <span class="genericon genericon-rightarrow"></span>', 'writr' ) ); ?></div>
			</nav><!-- #image-navigation -->

			<?php
				// If comments are open or we have at least one comment, load up the comment template
				if ( comments_open() || '0' != get_comments_number() )
					comments_template();
			?>

		<?php endwhile; // end of the loop. ?>

		</main><!-- #main -->
	</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>