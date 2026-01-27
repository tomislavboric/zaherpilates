( function( blocks, blockEditor, element, components ) {
	var el = element.createElement;
	var Fragment = element.Fragment;
	var RichText = blockEditor.RichText;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;

	blocks.registerBlockType( 'zaher/landing-countdown', {
		edit: function( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Countdown', initialOpen: true },
						el( TextControl, {
							label: 'Deadline (ISO format)',
							value: attributes.deadline,
							onChange: function( value ) {
								setAttributes( { deadline: value } );
							}
						} )
					)
				),
				el(
					'section',
					{ className: 'bf-countdown', 'data-deadline': attributes.deadline },
					el(
						'div',
						{ className: 'grid-container' },
						el(
							'div',
							{ className: 'bf-countdown__inner' },
							el( RichText, {
								tagName: 'div',
								className: 'bf-countdown__label',
								value: attributes.label,
								onChange: function( value ) {
									setAttributes( { label: value } );
								}
							} ),
							el(
								'div',
								{ className: 'bf-countdown__time' },
								el( 'span', { 'data-unit': 'days' }, '00', el( 'small', null, 'dana' ) ),
								el( 'span', { 'data-unit': 'hours' }, '00', el( 'small', null, 'sati' ) ),
								el( 'span', { 'data-unit': 'minutes' }, '00', el( 'small', null, 'minuta' ) ),
								el( 'span', { 'data-unit': 'seconds' }, '00', el( 'small', null, 'sekundi' ) )
							),
							attributes.hint ? el( RichText, {
								tagName: 'div',
								className: 'bf-countdown__hint',
								value: attributes.hint,
								onChange: function( value ) {
									setAttributes( { hint: value } );
								}
							} ) : el( RichText, {
								tagName: 'div',
								className: 'bf-countdown__hint',
								placeholder: 'Dodaj napomenu (opcionalno)',
								value: attributes.hint,
								onChange: function( value ) {
									setAttributes( { hint: value } );
								}
							} )
						)
					)
				)
			);
		},
		save: function( props ) {
			var attributes = props.attributes;

			return el(
				'section',
				{ className: 'bf-countdown', 'data-deadline': attributes.deadline },
				el(
					'div',
					{ className: 'grid-container' },
					el(
						'div',
						{ className: 'bf-countdown__inner' },
						el( RichText.Content, {
							tagName: 'div',
							className: 'bf-countdown__label',
							value: attributes.label
						} ),
						el(
							'div',
							{ className: 'bf-countdown__time' },
							el( 'span', { 'data-unit': 'days' }, '00', el( 'small', null, 'dana' ) ),
							el( 'span', { 'data-unit': 'hours' }, '00', el( 'small', null, 'sati' ) ),
							el( 'span', { 'data-unit': 'minutes' }, '00', el( 'small', null, 'minuta' ) ),
							el( 'span', { 'data-unit': 'seconds' }, '00', el( 'small', null, 'sekundi' ) )
						),
						attributes.hint ? el( RichText.Content, {
							tagName: 'div',
							className: 'bf-countdown__hint',
							value: attributes.hint
						} ) : null
					)
				)
			);
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.element, window.wp.components );
