( function( blocks, blockEditor, element ) {
	var el = element.createElement;
	var InnerBlocks = blockEditor.InnerBlocks;

	var TEMPLATE = [
		[ 'core/paragraph', { content: 'LOOP je online fitness platforma na kojoj treniraju žene svjesne važnosti tjelovježbe za emocionalno i fizičko zdravlje. Za fit tijelo ne trebaš trenirati puno nego pametno i u skladu s menstrualnim ciklusom.' } ]
	];

	blocks.registerBlockType( 'zaher/landing-intro', {
		edit: function() {
			return el(
				'section',
				{ className: 'loop-intro' },
				el(
					'div',
					{ className: 'grid-container' },
					el(
						'div',
						{ className: 'loop-intro__text' },
						el( InnerBlocks, {
							template: TEMPLATE,
							templateLock: 'all'
						} )
					)
				)
			);
		},
		save: function() {
			return el(
				'section',
				{ className: 'loop-intro' },
				el(
					'div',
					{ className: 'grid-container' },
					el(
						'div',
						{ className: 'loop-intro__text' },
						el( InnerBlocks.Content )
					)
				)
			);
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.element );
