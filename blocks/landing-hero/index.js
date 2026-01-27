( function( blocks, blockEditor, element ) {
	var el = element.createElement;
	var InnerBlocks = blockEditor.InnerBlocks;

	var TEMPLATE = [
		[ 'core/paragraph', { className: 'hero__kicker', content: 'Black Week na LOOPu (24. – 28.11.)' } ],
		[ 'core/group', { className: 'hero__header' }, [
			[ 'core/heading', { level: 1, className: 'hero__title', content: 'Ostvari ekskluzivan popust i dodatne pogodnosti – samo ovaj tjedan' } ],
			[ 'core/group', { className: 'hero__desc' }, [
				[ 'core/paragraph', { content: 'LOOP je online fitness platforma na kojoj treniraju žene svjesne važnosti tjelovježbe za emocionalno i fizičko zdravlje. Za fit tijelo ne trebaš trenirati puno nego pametno i u skladu s menstrualnim ciklusom.' } ]
			] ]
		] ],
		[ 'core/group', { className: 'hero__cta' }, [
			[ 'zaher/landing-cta-button', { text: 'Pogledaj ponudu', url: '#bf-options', size: 'button--large' } ],
			[ 'core/paragraph', { className: 'hero__note', content: 'Ponuda traje cijeli tjedan, a završava 28.11. u 23:59.' } ]
		] ]
	];

	blocks.registerBlockType( 'zaher/landing-hero', {
		edit: function() {
			return el(
				'section',
				{ className: 'hero hero--black-friday' },
				el(
					'div',
					{ className: 'grid-container' },
					el(
						'div',
						{ className: 'hero__content' },
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
				{ className: 'hero hero--black-friday' },
				el(
					'div',
					{ className: 'grid-container' },
					el(
						'div',
						{ className: 'hero__content' },
						el( InnerBlocks.Content )
					)
				)
			);
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.element );
