import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	RangeControl,
	ToggleControl,
	TextControl,
	// No stable/public equivalent — the same segmented control WP core's
	// own inspector uses (here for the scroll direction).
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';

export default function Edit( { attributes, setAttributes } ) {
	const { items, speed, direction, pauseOnHover, gap, separator } =
		attributes;
	const blockProps = useBlockProps();

	const updateItem = ( index, value ) => {
		const next = items.slice();
		next[ index ] = { ...next[ index ], text: value };
		setAttributes( { items: next } );
	};

	const addItem = () =>
		setAttributes( {
			items: [ ...items, { text: __( 'New item', 'noorifa-core' ) } ],
		} );

	const removeItem = ( index ) =>
		setAttributes( { items: items.filter( ( _item, i ) => i !== index ) } );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Marquee Settings', 'noorifa-core' ) }>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __(
							'Scroll speed (seconds per loop)',
							'noorifa-core'
						) }
						help={ __( 'Higher is slower.', 'noorifa-core' ) }
						value={ speed }
						onChange={ ( value ) =>
							setAttributes( { speed: value || 20 } )
						}
						min={ 5 }
						max={ 60 }
					/>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Direction', 'noorifa-core' ) }
						value={ direction }
						onChange={ ( value ) =>
							setAttributes( { direction: value || 'left' } )
						}
					>
						<ToggleGroupControlOption
							value="left"
							label={ __( 'Left', 'noorifa-core' ) }
						/>
						<ToggleGroupControlOption
							value="right"
							label={ __( 'Right', 'noorifa-core' ) }
						/>
					</ToggleGroupControl>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Pause on hover', 'noorifa-core' ) }
						checked={ pauseOnHover }
						onChange={ ( value ) =>
							setAttributes( { pauseOnHover: value } )
						}
					/>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Gap between items (px)', 'noorifa-core' ) }
						value={ gap }
						onChange={ ( value ) =>
							setAttributes( { gap: value || 0 } )
						}
						min={ 8 }
						max={ 120 }
					/>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Separator', 'noorifa-core' ) }
						help={ __(
							'Shown between items. Leave empty for none.',
							'noorifa-core'
						) }
						value={ separator }
						onChange={ ( value ) =>
							setAttributes( { separator: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			{ /* The items are shown as a plain wrapping row in the editor so
			     each stays clickable/editable — the infinite scroll is a
			     front-end-only effect (see render.php). */ }
			<div className="noorifa-core-marquee__editor">
				{ items.map( ( item, index ) => (
					<div
						className="noorifa-core-marquee__editor-item"
						key={ index }
					>
						<RichText
							tagName="span"
							className="noorifa-core-marquee__item"
							value={ item.text }
							onChange={ ( value ) => updateItem( index, value ) }
							placeholder={ __( 'Add text…', 'noorifa-core' ) }
							allowedFormats={ [] }
						/>
						<Button
							className="noorifa-core-marquee__remove"
							icon={ closeSmall }
							label={ __( 'Remove item', 'noorifa-core' ) }
							onClick={ () => removeItem( index ) }
						/>
					</div>
				) ) }
				<Button variant="secondary" onClick={ addItem }>
					{ __( 'Add item', 'noorifa-core' ) }
				</Button>
			</div>
		</div>
	);
}
