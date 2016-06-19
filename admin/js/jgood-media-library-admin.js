(function( $ ) {
	'use strict';

	 $( document ).ready( function() {
		var $mediaGridWrap = $( '#jgood-media-grid' );

		// Open up our custom media view
		if ( $mediaGridWrap.length && window.wp && window.wp.media ) {
			jgoodMedia.displayLibraryView({
				frame: 'library',
				container: $mediaGridWrap
			}).open();
		}
		
		// when our custom media button is clicked open up our custom media view
		$(document).on( 'click', '.jgood-insert-media', function( event ) {
			var modal = jgoodMedia.displayLibraryView({
				frame: 'modal',
				modal: true,
				multiple: true,
				container: $(document.body)
			}).open();

			// when a item in our custom media view is inserted add item to post/page
			modal.on( 'insert', function( selection ) {
				var state = modal.state();

				selection = selection || state.get('selection');

				if ( ! selection )
					return;

				$.when.apply( $, selection.map( function( attachment ) {
					var display = state.display( attachment ).toJSON();
					/**
					 * @this wp.media.editor
					 */
					return wp.media.editor.send.attachment( display, attachment.toJSON() );
				}, this ) ).done( function() {
					wp.media.editor.insert( _.toArray( arguments ).join('\n\n') );
				});
			}, this );

			// when a item in our custom media view is inserted via url add it to item/page
			modal.state('embed').on( 'select', function() {
				/**
				 * @this wp.media.editor
				 */
				var state = modal.state(),
					type = state.get('type'),
					embed = state.props.toJSON();

				embed.url = embed.url || '';

				if ( 'link' === type ) {
					_.defaults( embed, {
						title:   embed.url,
						linkUrl: embed.url
					});

					wp.media.editor.send.link( embed ).done( function( resp ) {
						wp.media.editor.insert( resp );
					});

				} else if ( 'image' === type ) {
					_.defaults( embed, {
						title:   embed.url,
						linkUrl: '',
						align:   'none',
						link:    'none'
					});

					if ( 'none' === embed.link ) {
						embed.linkUrl = '';
					} else if ( 'file' === embed.link ) {
						embed.linkUrl = embed.url;
					}

					wp.media.editor.insert( wp.media.string.image( embed ) );
				}
			}, this );
		});

	});

	

})( jQuery );
