( function( blocks, blockEditor, element ) {
	var el = element.createElement;
	var InnerBlocks = blockEditor.InnerBlocks;

	var LIST_OPTION_ONE = '<li>Pristup cijeloj LOOP platformi na 3 mjeseca.</li>' +
		'<li>Mogućnost otkazivanja pretplate u bilo kojem trenutku unutar korisničkog računa.</li>' +
		'<li>Bonus kategorije samo za članove viših pretplata</li>';

	var LIST_OPTION_TWO = '<li>6 mjeseci pristupa LOOP platformi – jedan mjesec dobivaš gratis.</li>' +
		'<li>Individualni coaching call (60 min) na temu po tvom izboru.</li>' +
		'<li>Ulaziš u izbor za coaching paket od 12 susreta potpuno besplatno.</li>';

	var TEMPLATE = [
		[ 'core/group', { className: 'section__header section__header--center' }, [
			[ 'core/paragraph', { className: 'section__subtitle', content: 'Iznenađenja za crni tjedan' } ],
			[ 'core/heading', { className: 'section__title', content: 'Biraj između dvije ekskluzivne opcije' } ],
			[ 'core/group', { className: 'section__desc' }, [
				[ 'core/paragraph', { content: 'Kako te jednom godišnje za Crni petak volim iznenaditi, ove godine pripremila sam sljedeće ponude.' } ]
			] ]
		] ],
		[ 'core/group', { className: 'bf-offer__grid' }, [
			[ 'core/group', { className: 'bf-offer__card' }, [
				[ 'core/paragraph', { className: 'bf-offer__badge', content: 'Opcija 1' } ],
				[ 'core/paragraph', { className: 'bf-offer__price', content: '<del>€79.99</del><span>€54.99</span>' } ],
				[ 'core/paragraph', { className: 'bf-offer__savings', content: '25 € OFF na tromjesečnu pretplatu.' } ],
				[ 'core/list', { className: 'bf-offer__list', values: LIST_OPTION_ONE } ],
				[ 'core/group', { className: 'bf-offer__cta' }, [
					[ 'zaher/landing-cta-button', { text: 'Ugrabi ponudu', url: '/registracija/tromjesecna-pretplata/?coupon=BF25', size: '' } ]
				] ]
			] ],
			[ 'core/group', { className: 'bf-offer__card' }, [
				[ 'core/paragraph', { className: 'bf-offer__badge', content: 'Opcija 2' } ],
				[ 'core/paragraph', { className: 'bf-offer__price', content: '<span>€149.99</span>' } ],
				[ 'core/paragraph', { className: 'bf-offer__savings', content: 'Polugodišnja pretplata + 1:1 coaching call sa Ivanom<br> (ograničeno na 7 mjesta!)' } ],
				[ 'core/list', { className: 'bf-offer__list', values: LIST_OPTION_TWO } ],
				[ 'core/group', { className: 'bf-offer__cta' }, [
					[ 'zaher/landing-cta-button', { text: 'Ugrabi ponudu', url: '/registracija/polugodisnja-pretplata/', size: '' } ]
				] ]
			] ]
		] ],
		[ 'core/paragraph', { className: 'bf-note', content: 'Sve što trebaš je kliknuti na odabranu opciju i pratiti daljnje korake.<br> <strong>Obje opcije</strong> vrijede za postojeće članice LOOP-a prilikom nadogradnje na višu pretplatu. Ako zapneš, tipkaj nam preko <a href="mailto:info@zaherpilates.com">info@zaherpilates.com</a>.' } ]
	];

	blocks.registerBlockType( 'zaher/landing-offer', {
		edit: function() {
			return el(
				'section',
				{ className: 'pricing-plans section bf-offer', id: 'bf-options' },
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
				{ className: 'pricing-plans section bf-offer', id: 'bf-options' },
				el(
					'div',
					{ className: 'grid-container' },
					el( InnerBlocks.Content )
				)
			);
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.element );
