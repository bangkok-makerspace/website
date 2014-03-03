( function( $ ) {

	/*
	 * A function to adjust the min-height of #primary
	 * to make sure #colophon will go underneath #secondary.
	 */
	function grid() {

		$( '#primary' ).removeAttr( 'style' );

		if( $( window ).width() > 959 ) {

			var $right = $( '#primary' ).height(),
			    $left = $( '#masthead' ).height() + $( '#secondary' ).height();

		} else {

			var $right = $( '#primary' ).height(),
			    $left = $( '#secondary' ).height() - $( '#masthead' ).height();

		}

		if( $left >= $right ) {

			$( '#primary' ).css( 'min-height', $left );

		} else {

			$( '#content' ).removeAttr( 'class' );

		}

	}

	/*
	 * A function to load grid() or not,
	 * enable/disable a dropdown submenu and
	 * add calc() support depending on the window width.
	 */
	function responsive() {

		$( '.dropdown-icon' ).remove();

		if( $( window ).width() > 959 ) {

			$( '#secondary, #colophon' ).show();

			if ( $( '#secondary' ).length > 0 ) {

				$( this ).css( 'margin-top', 0 );

			} else {

				$( '#colophon' ).css( 'margin-top', 0 );

			}

	    	grid();
	    	$( '.dropdown-icon' ).remove();

		} else {

			$( '#primary, #secondary, #colophon' ).removeAttr( 'style' );
			$( '#content' ).removeAttr( 'class' );

			if ( $( '#secondary' ).length > 0 ) {

				$( this ).css( 'margin-top', - $( '#masthead' ).height() );

			} else {

				$( '#colophon' ).css( 'margin-top', - $( '#masthead' ).height() );

			}

			$( '.main-navigation .dropdown > a' ).append( '<span class="dropdown-icon" />' );

			$( '.dropdown-icon' ).click( function( e ) {

				e.preventDefault();

				$( this ).toggleClass( 'open' );

				if( $( this ).hasClass( 'open' ) ) {

					$( this ).parent().next( '.dropdown-menu' ).show();

				} else {

					$( this ).parent().next( '.dropdown-menu' ).hide();

				}

			} );

	    }

	    var $calc_support = $( '.entry-thumbnail, .entry-attachment .attachment' );

	    if( $( window ).width() < 768 ) {

	    	$calc_support.css( 'width', '100%' ).css( 'width', '+=80px' );

	    } else {

		    $calc_support.removeAttr( 'style' );

	    }

	}

	/*
	 * A function to open/close the sidebar and
	 * add calc() support depending on the window width.
	 */
	function sidebar() {

		$( '#sidebar-toggle' ).click( function( e ) {

			e.preventDefault();

			$( 'body, html' ).animate( {
				scrollTop: 0
			}, 250 );

			$( this ).toggleClass( 'open' );
			$( 'body' ).toggleClass( 'sidebar-closed' );

			$( '#secondary, #colophon' ).toggleClass( 'block' );

			$( '#secondary' ).css( 'margin-top', - $( '#masthead' ).height() );

			if( $( this ).hasClass( 'open' ) ) {

				grid();

			} else {

				$( '#primary' ).removeAttr( 'style' );
				$( '#content' ).removeAttr( 'class' );

			}

			var $calc_support = $( '.main-navigation' );

			if( $( window ).width() < 768 && $( 'body' ).hasClass( 'sidebar-closed' ) ) {

		    	$calc_support.css( 'width', '100%' ).css( 'width', '+=80px' );

		    } else {

			    $calc_support.removeAttr( 'style' );

		    }

		} );

	}

	// Call grid() after IS loads posts only if window width > 959px.
	$( document ).on( 'post-load', function() {

		if( $( window ).width() > 959 ) {

			grid();

		}

	} );

	// Call all functions after a page load completely.
	$( window ).load( function() {

		responsive();
		sidebar();

	    // If resize window
	    $( window ).resize( function() {

	    	responsive();
	    	sidebar();

	    } );

	} );

} )( jQuery );