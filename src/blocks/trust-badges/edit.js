import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	ToggleControl,
	RangeControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';
import IconPicker from '../../utils/icon-picker';

export default function Edit( { attributes, setAttributes } ) {
	const { items, boxed, boxedWidth, iconSize } = attributes;
	const blockProps = useBlockProps( {
		// Exposed as a CSS variable the icon consumes directly (see
		// style.scss) rather than a plain inherited size, which would need
		// a dedicated attribute-to-inline-style path anyway since icon
		// dimensions aren't part of any native block support.
		style: iconSize
			? { '--noorifa-trust-badges-icon-size': `${ iconSize }rem` }
			: undefined,
	} );

	const updateItem = ( index, field, value ) => {
		const next = items.slice();
		next[ index ] = { ...next[ index ], [ field ]: value };
		setAttributes( { items: next } );
	};

	const addItem = () =>
		setAttributes( {
			items: [
				...items,
				{
					icon: 'nt-shieldCheck',
					label: __( 'New badge', 'noorifa-core' ),
				},
			],
		} );

	const removeItem = ( index ) =>
		setAttributes( { items: items.filter( ( _item, i ) => i !== index ) } );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					title={ __( 'Layout', 'noorifa-core' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __( 'Boxed width', 'noorifa-core' ) }
						checked={ boxed }
						onChange={ ( value ) =>
							setAttributes( { boxed: value } )
						}
						help={
							boxed
								? __(
										'Constrained to a max width and centered.',
										'noorifa-core'
								  )
								: __(
										'Stretches the full width of its container.',
										'noorifa-core'
								  )
						}
					/>
					{ boxed && (
						<RangeControl
							label={ __( 'Max width (px)', 'noorifa-core' ) }
							value={ boxedWidth }
							onChange={ ( value ) =>
								setAttributes( { boxedWidth: value } )
							}
							min={ 480 }
							max={ 1800 }
							step={ 10 }
						/>
					) }
					<NumberControl
						__next40pxDefaultSize
						label={ __( 'Icon size (rem)', 'noorifa-core' ) }
						help={ __( 'Default is 1.75rem.', 'noorifa-core' ) }
						value={ iconSize }
						min={ 0.5 }
						max={ 6 }
						step={ 0.25 }
						onChange={ ( value ) =>
							setAttributes( { iconSize: value || '' } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<ul
				className={
					'noorifa-core-trust-badges__list' +
					( boxed ? ' is-boxed' : '' )
				}
				style={ boxed ? { maxWidth: boxedWidth } : undefined }
			>
				{ items.map( ( item, index ) => (
					<li
						className="noorifa-core-trust-badges__item"
						key={ index }
					>
						<Button
							className="noorifa-core-trust-badges__remove"
							icon={ closeSmall }
							label={ __( 'Remove badge', 'noorifa-core' ) }
							onClick={ () => removeItem( index ) }
						/>
						<IconPicker
							value={ item.icon }
							allowNone
							onChange={ ( value ) =>
								updateItem( index, 'icon', value )
							}
						/>
						<RichText
							tagName="span"
							className="noorifa-core-trust-badges__label"
							value={ item.label }
							onChange={ ( value ) =>
								updateItem( index, 'label', value )
							}
							placeholder={ __( 'Label', 'noorifa-core' ) }
						/>
					</li>
				) ) }
			</ul>
			<Button variant="secondary" onClick={ addItem }>
				{ __( 'Add badge', 'noorifa-core' ) }
			</Button>
		</div>
	);
}
