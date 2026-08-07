import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';
import IconPicker from '../../utils/icon-picker';

export default function Edit( { attributes, setAttributes } ) {
	const { rows } = attributes;
	const blockProps = useBlockProps();

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
