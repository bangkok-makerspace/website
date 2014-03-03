<?php
/**
 * The Sidebar containing the main widget areas.
 *
 * @package Writr
 */
?>

<?php if ( is_active_sidebar( 'sidebar-1' ) ) : ?>

	<div id="secondary" class="widget-area" role="complementary">
		<?php do_action( 'before_sidebar' ); ?>
		<?php dynamic_sidebar( 'sidebar-1' ) ?>
		<?php do_action( 'after_sidebar' ); ?>
	</div><!-- #secondary -->

<?php endif; // end sidebar widget area ?>
