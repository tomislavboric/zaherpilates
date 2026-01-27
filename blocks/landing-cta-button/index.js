( function( blocks, blockEditor, element, components ) {
	var el = element.createElement;
	var Fragment = element.Fragment;
	var RichText = blockEditor.RichText;
	var InspectorControls = blockEditor.InspectorControls;
	var URLInputButton = blockEditor.URLInputButton;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var SelectControl = components.SelectControl;

	blocks.registerBlockType( 'zaher/landing-cta-button', {
		edit: function( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var className = 'button' + ( attributes.size ? ' ' + attributes.size : '' );

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Button Settings', initialOpen: true },
						URLInputButton ? el( URLInputButton, {
							url: attributes.url,
							onChange: function( value ) {
								setAttributes( { url: value } );
							}
						} ) : el( TextControl, {
							label: 'URL',
							value: attributes.url,
							onChange: function( value ) {
								setAttributes( { url: value } );
							}
						} ),
						el( SelectControl, {
							label: 'Size',
							value: attributes.size,
							options: [
								{ label: 'Default', value: '' },
								{ label: 'Large', value: 'button--large' }
							],
							onChange: function( value ) {
								setAttributes( { size: value } );
							}
						} )
					)
				),
				el( RichText, {
					tagName: 'a',
					className: className,
					href: attributes.url || '#',
					value: attributes.text,
					allowedFormats: [],
					placeholder: 'Button text',
					onChange: function( value ) {
						setAttributes( { text: value } );
					}
				} )
			);
		},
		save: function( props ) {
			var attributes = props.attributes;
			var className = 'button' + ( attributes.size ? ' ' + attributes.size : '' );

			return el( RichText.Content, {
				tagName: 'a',
				className: className,
				href: attributes.url || '#',
				value: attributes.text
			} );
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.element, window.wp.components );
