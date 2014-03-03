( function( $ ) {

	var video = $( '.video-wrapper embed, .video-wrapper iframe, .video-wrapper object' );

	video.each( function() {

		$( this )
			// jQuery .data does not work on object/embed elements
			.attr( 'data-ratio', this.height / this.width )
			.attr( 'data-width', this.width )
			.attr( 'data-height', this.height );

	} );

	function responsive_videos() {

		video.each( function() {

			var video_element   = $( this ),
			    video_width     = video_element.attr( 'data-width' ),
			    video_height    = video_element.attr( 'data-height' ),
			    video_ratio     = video_element.attr( 'data-ratio' ),
			    video_wrapper   = video_element.closest( '.video-wrapper' ),
			    container_width = video_wrapper.parent().width();

			video_element
				.removeAttr( 'height' )
				.removeAttr( 'width' );

			if ( video_width > container_width ) {

				video_element
					.width( container_width )
					.height( container_width * video_ratio );

			} else {

				video_element
					.width( video_width )
					.height( video_height );

			}

		} );

	}

	responsive_videos();

	$( window ).load( responsive_videos ).resize( _.debounce( responsive_videos, 100 ) );

} )( jQuery );
