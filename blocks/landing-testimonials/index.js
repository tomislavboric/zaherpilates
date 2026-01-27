( function( blocks, blockEditor, element ) {
	var el = element.createElement;
	var InnerBlocks = blockEditor.InnerBlocks;

	var TEMPLATE = [
		[ 'core/group', { className: 'section__header section__header--center' }, [
			[ 'core/paragraph', { className: 'section__subtitle', content: 'Evo zašto stotinu žena bira LOOP' } ],
			[ 'core/heading', { className: 'section__title', content: 'Iskustva iz prve ruke' } ]
		] ],
		[ 'core/group', { className: 'testimonials__main' }, [
			[ 'core/group', { className: 'testimonials__grid' }, [
				[ 'core/group', { className: 'testimonials__item' }, [
					[ 'core/group', { className: 'testimonials__blockquote' }, [
						[ 'core/paragraph', { content: 'Željela sam ti reći... tako se <strong>osjećam nježno i ženstveno, a opet snažno</strong> kad vježbam s tobom, jer ti imaš takvu energiju koju prosljeđuješ!' } ]
					] ],
					[ 'core/group', { className: 'testimonials__person' }, [
						[ 'core/group', { className: 'testimonials__person-wrap' }, [
							[ 'core/paragraph', { className: 'testimonials__person-name', content: 'Maja' } ]
						] ]
					] ]
				] ],
				[ 'core/group', { className: 'testimonials__item' }, [
					[ 'core/group', { className: 'testimonials__blockquote' }, [
						[ 'core/paragraph', { content: 'Vježbe su odlične, baš su mi sjele, posebno videa s vježbama koje ti vodiš, paše mi ta fluidnost i prisutnost. <strong>Uz posao, dvoje male djece, brigu o kućanskim obvezama, ja stignem i po 4-5 puta tjedno odvježbati</strong>, to je dio dana kad djeca zaspu, za mene i volim to što brinem o svom tijelu' } ]
					] ],
					[ 'core/group', { className: 'testimonials__person' }, [
						[ 'core/group', { className: 'testimonials__person-wrap' }, [
							[ 'core/paragraph', { className: 'testimonials__person-name', content: 'Manuela' } ]
						] ]
					] ]
				] ],
				[ 'core/group', { className: 'testimonials__item' }, [
					[ 'core/group', { className: 'testimonials__blockquote' }, [
						[ 'core/paragraph', { content: 'Ajme Ivana, ove tvoje vježbe su predivne!! Toliko opuštajuće, ovaj prvi trening mi nije bio zahtjevan, ali sam osjetila svaki djelić tijela i kako ono radi! Hvala ti na tolikoj predanosti i detaljnim opisima dok izvodiš vježbe.<br> Prebrzo je prošlo, nisam ni skužila da je gotovo! Čista uživancija!🥰🩷🌸' } ]
					] ],
					[ 'core/group', { className: 'testimonials__person' }, [
						[ 'core/group', { className: 'testimonials__person-wrap' }, [
							[ 'core/paragraph', { className: 'testimonials__person-name', content: 'Vida' } ]
						] ]
					] ]
				] ],
				[ 'core/group', { className: 'testimonials__item' }, [
					[ 'core/group', { className: 'testimonials__blockquote' }, [
						[ 'core/paragraph', { content: 'Draga Ivana, samo ću ti reći da si nikada nisam priuštila bolje treninge...Na Loop-u je toliko izbora da stvarno nema izgovora da se ne odradi bilo kakav trening u danu. Kada imam više vremena uzmem si neke zahtjevnije treninge, kada uopće nemam vremena uzmem one najkraće i <strong>osjećaj je odličan jer ipak i u takvom danu napravim nešto za svoje zdravlje</strong>.<br> Vježbe su mi odlične, nije dosadno.' } ]
					] ],
					[ 'core/group', { className: 'testimonials__person' }, [
						[ 'core/group', { className: 'testimonials__person-wrap' }, [
							[ 'core/paragraph', { className: 'testimonials__person-name', content: 'Zrinka' } ]
						] ]
					] ]
				] ],
				[ 'core/group', { className: 'testimonials__item' }, [
					[ 'core/group', { className: 'testimonials__blockquote' }, [
						[ 'core/paragraph', { content: 'Draga Ivana, htjela sam samo reći kako sam nastavila trenirati u Loopu. I dalje sam presretna koliko <strong>stvarno s guštom treniram i osjećam se snažno tijekom i nakon treninga</strong>. 💪❤️<br> Obožavam kako vodiš trening!' } ]
					] ],
					[ 'core/group', { className: 'testimonials__person' }, [
						[ 'core/group', { className: 'testimonials__person-wrap' }, [
							[ 'core/paragraph', { className: 'testimonials__person-name', content: 'Iva' } ]
						] ]
					] ]
				] ]
			] ]
		] ]
	];

	blocks.registerBlockType( 'zaher/landing-testimonials', {
		edit: function() {
			return el(
				'section',
				{ className: 'testimonials section bf-testimonials' },
				el(
					'div',
					{ className: 'grid-container full' },
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
				{ className: 'testimonials section bf-testimonials' },
				el(
					'div',
					{ className: 'grid-container full' },
					el( InnerBlocks.Content )
				)
			);
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.element );
