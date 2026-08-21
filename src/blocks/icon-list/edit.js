import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	// No stable/public equivalent — the native line-height control only
	// surfaces when the theme opts in via theme.json, which this classic
	// theme intentionally doesn't use, so it's hand-rolled here.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';
import IconPicker from '../../utils/icon-picker';

export default function Edit( { attributes, setAttributes } ) {
	const { rows, lineHeight, iconSize } = attributes;
	const blockProps = useBlockProps( {
		// Exposed as CSS variables the row text/icon consume directly (see
		// style.scss) rather than plain inherited values, which the
		// theme's own span/li rules would otherwise override.
		style: {
			...( lineHeight ? { '--noorifa-icon-list-lh': lineHeight } : {} ),
			...( iconSize
				? { '--noorifa-icon-list-icon-size': `${ iconSize }em` }
				: {} ),
		},
	} );

	const updateRow = ( index, field, value ) => {
		const next = rows.slice();
		next[ index ] = { ...next[ index ], [ field ]: value };
		setAttributes( { rows: next } );
	};

	const addRow = () =>
		setAttributes( {
			rows: [
				...rows,
				{ icon: 'shield', text: __( 'New line', 'noorifa-core' ) },
			],
		} );

	const removeRow = ( index ) =>
		setAttributes( { rows: rows.filter( ( _row, i ) => i !== index ) } );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Typography', 'noorifa-core' ) }>
					<NumberControl
						__next40pxDefaultSize
						label={ __( 'Line height', 'noorifa-core' ) }
						value={ lineHeight }
						min={ 0 }
						step={ 0.1 }
						onChange={ ( value ) =>
							setAttributes( { lineHeight: value || '' } )
						}
					/>
					<NumberControl
						__next40pxDefaultSize
						label={ __( 'Icon size (em)', 'noorifa-core' ) }
						help={ __(
							'Relative to the text size — 1.25 (the default) makes the icon 1.25× the row text.',
							'noorifa-core'
						) }
						value={ iconSize }
						min={ 0.25 }
						max={ 5 }
						step={ 0.05 }
						onChange={ ( value ) =>
							setAttributes( { iconSize: value || '' } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<ul className="noorifa-core-icon-list__list">
				{ rows.map( ( row, index ) => (
					<li className="noorifa-core-icon-list__row" key={ index }>
						<IconPicker
							value={ row.icon }
							onChange={ ( value ) =>
								updateRow( index, 'icon', value )
							}
						/>
						<RichText
							tagName="span"
							className="noorifa-core-icon-list__text"
							value={ row.text }
							onChange={ ( value ) =>
								updateRow( index, 'text', value )
							}
							placeholder={ __( 'Add a line…', 'noorifa-core' ) }
						/>
						<Button
							className="noorifa-core-icon-list__remove"
							icon={ closeSmall }
							label={ __( 'Remove row', 'noorifa-core' ) }
							onClick={ () => removeRow( index ) }
						/>
					</li>
				) ) }
			</ul>
			<Button variant="secondary" onClick={ addRow }>
				{ __( 'Add row', 'noorifa-core' ) }
			</Button>
		</div>
	);
}
