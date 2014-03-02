				<div id="sidebar1" class="col-sm-4" role="complementary">

					<?php if ( is_active_sidebar( 'sidebar1' ) ) : ?>

						<?php dynamic_sidebar( 'sidebar1' ); ?>

					<?php else : ?>

						<!-- This content shows up if there are no widgets defined in the backend. -->

						<div class="alert alert-message">

							<p><?php _e("Please activate some Widgets","wpbootstrap"); ?>.</p>

						</div>

					<?php endif; ?>

					<h4 class="widgettitle">Meta</h4>
					<ul>
						<?php wp_register(); ?>
						<li><a href="https://www.facebook.com/BangkokMakerSpace" target="_blank">Facebook</a></li>
						<li><a href="https://twitter.com/BKKMakerSpace" target="_blank">Twitter</a></li>
						<li><a href="https://foursquare.com/v/bangkok-makerspace/52d67a5f11d2d527eed4c194" target="_blank">Foursquare</a></li>
						<li><?php wp_loginout(); ?></li>
					</ul>

				</div>