( function( blocks, blockEditor, element ) {
	var el = element.createElement;
	var InnerBlocks = blockEditor.InnerBlocks;

	var LIST_VALUES = '<li>Treninzi od 10 do 45 minuta koji prate hormonalne promjene.</li>' +
		'<li>Stručna podrška i zajednica koja razumije izazove modernog života.</li>' +
		'<li>Programi i planovi koji balansiraju snagu, mobilnost i oporavak.</li>';

	var TEMPLATE = [
		[ 'core/group', { className: 'section__header section__header--center' }, [
			[ 'core/paragraph', { className: 'section__subtitle', content: 'Pametan pristup' } ],
			[ 'core/heading', { className: 'section__title', content: 'Treniraj u skladu sa svojim tijelom, ne protiv njega' } ],
			[ 'core/group', { className: 'section__desc' }, [
				[ 'core/paragraph', { content: 'LOOP te vodi kroz faze ciklusa kako bi svaka žena mogla trenirati u skladu s energijom koju taj tjedan ima. Ulog je mali, a rezultat je kontinuitet i osjećaj lakoće u vlastitom tijelu.' } ]
			] ]
		] ],
		[ 'core/list', { className: 'bf-story__list', values: LIST_VALUES } ],
		[ 'core/group', { className: 'bf-story__image' }, [
			[ 'core/image', { url: '/wp-content/themes/zaherpilates/dist/assets/images/loop-training.jpg', alt: 'LOOP trening', linkDestination: 'none' } ]
		] ]
	];

	blocks.registerBlockType( 'zaher/landing-story', {
		edit: function() {
			return el(
				'section',
				{ className: 'section section--bg bf-story' },
				el(
					'div',
					{ className: 'grid-container' },
					el( InnerBlocks, {
						template: TEMPLATE,
						templateLock: 'all'
					} )
				)
			);
		},
		save: function() {
			return el(
				'section',
				{ className: 'section section--bg bf-story' },
				el(
					'div',
					{ className: 'grid-container' },
					el( InnerBlocks.Content )
				)
			);
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.element );
