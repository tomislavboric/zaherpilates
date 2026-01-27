( function( blocks, blockEditor, element ) {
	var el = element.createElement;
	var InnerBlocks = blockEditor.InnerBlocks;

	var TEMPLATE = [
		[ 'core/group', { className: 'section__header section__header--center no-padding' }, [
			[ 'core/heading', { className: 'section__title', content: 'Savršeni trenutak je sada' } ],
			[ 'core/group', { className: 'section__desc' }, [
				[ 'core/paragraph', { content: 'Shvati ovo kao znak i iskoristi ponudu.<br> Nadogradi članstvo, pokloni sebi vrijeme i podršku te uđi u kraj godine osjećajući se snažno i samopouzdano.' } ]
			] ]
		] ],
		[ 'core/buttons', {}, [
			[ 'core/button', { className: 'button--large', text: 'Iskoristi ponudu!', url: '#bf-options' } ]
		] ],
		[ 'core/paragraph', { className: 'hero__note', content: 'Postojeće članice mogu nadograditi pretplatu unutar svog korisničkog računa.' } ]
	];

	blocks.registerBlockType( 'zaher/landing-final-cta', {
		edit: function() {
			return el(
				'section',
				{ className: 'bf-final-cta' },
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
				{ className: 'bf-final-cta' },
				el(
					'div',
					{ className: 'grid-container' },
					el( InnerBlocks.Content )
				)
			);
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.element );
