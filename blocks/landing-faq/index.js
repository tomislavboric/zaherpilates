( function( blocks, blockEditor, element ) {
	var el = element.createElement;
	var InnerBlocks = blockEditor.InnerBlocks;

	var TEMPLATE = [
		[ 'core/group', { className: 'section__header section__header--center' }, [
			[ 'core/paragraph', { className: 'section__subtitle', content: 'Česta pitanja' } ],
			[ 'core/heading', { className: 'section__title', content: 'Sve što trebaš znati prije nego uskoro istekne ponuda' } ]
		] ],
		[ 'core/group', { className: 'bf-faq__list' }, [
			[ 'core/group', { className: 'bf-faq__item' }, [
				[ 'core/paragraph', { className: 'bf-faq__question', content: 'Kada počinje moje članstvo?' } ],
				[ 'core/paragraph', { className: 'bf-faq__answer', content: 'Pristup dobivaš odmah nakon kupnje i vrijedi punih 12 mjeseci. Sve nadogradnje i novi programi automatski se dodaju u tvoj račun.' } ]
			] ],
			[ 'core/group', { className: 'bf-faq__item' }, [
				[ 'core/paragraph', { className: 'bf-faq__question', content: 'Što ako tek počinjem s tjelovježbom?' } ],
				[ 'core/paragraph', { className: 'bf-faq__answer', content: 'U LOOP biblioteci postoje početnički, srednji i napredni treninzi. Uz Black Friday paket dobivaš i vodič kako odabrati pravi program za svoju razinu.' } ]
			] ],
			[ 'core/group', { className: 'bf-faq__item' }, [
				[ 'core/paragraph', { className: 'bf-faq__question', content: 'Mogu li pokloniti pristup nekome?' } ],
				[ 'core/paragraph', { className: 'bf-faq__answer', content: 'Da! Nakon kupnje nam se javi na support i prebacit ćemo članstvo na osobu koju želiš razveseliti.' } ]
			] ]
		] ]
	];

	blocks.registerBlockType( 'zaher/landing-faq', {
		edit: function() {
			return el(
				'section',
				{ className: 'bf-faq section' },
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
				{ className: 'bf-faq section' },
				el(
					'div',
					{ className: 'grid-container' },
					el( InnerBlocks.Content )
				)
			);
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.element );
