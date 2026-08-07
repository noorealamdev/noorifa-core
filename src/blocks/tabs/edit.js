import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	ToggleControl,
	RangeControl,
} from '@wordpress/components';
import { createBlock } from '@wordpress/blocks';
import { useSelect, useDispatch } from '@wordpress/data';
import { plus } from '@wordpress/icons';

const TEMPLATE = [
	[ 'noorifa-core/tab', { title: 'Tab 1' } ],
	[ 'noorifa-core/tab', { title: 'Tab 2' } ],
];

export default function Edit( { clientId, attributes, setAttributes } ) {
	const { boxed, boxedWidth } = attributes;
	const tabs = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks( clientId ),
		[ clientId ]
	);
	const activeTabId = useSelect(
		( select ) => {
			const { isBlockSelected, hasSelectedInnerBlock } =
				select( 'core/block-editor' );
			const selected = select( 'core/block-editor' )
				.getBlocks( clientId )
				.find(
					( tab ) =>
						isBlockSelected( tab.clientId ) ||
						hasSelectedInnerBlock( tab.clientId, true )
				);

			return selected ? selected.clientId : null;
		},
		[ clientId ]
	);
	const { selectBlock, updateBlockAttributes, insertBlock } =
		useDispatch( 'core/block-editor' );

	const blockProps = useBlockProps( {
		className: boxed ? 'is-boxed' : undefined,
		style: boxed ? { maxWidth: boxedWidth } : undefined,
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'noorifa-core-tabs__panels' },
		{
			allowedBlocks: [ 'noorifa-core/tab' ],
			template: TEMPLATE,
			renderAppender: false,
		}
	);

	const addTab = () => {
		const tab = createBlock( 'noorifa-core/tab', {
			title: `Tab ${ tabs.length + 1 }`,
		} );
		insertBlock( tab, tabs.length, clientId );
		selectBlock( tab.clientId );
	};

	// With no selection inside the block, the first tab acts as active.
	const currentId =
		activeTabId || ( tabs.length ? tabs[ 0 ].clientId : null );

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
				</PanelBody>
			</InspectorControls>
			<div className="noorifa-core-tabs__list">
				{ tabs.map( ( tab ) => (
					/* eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions */
					<div
						key={ tab.clientId }
						className={
							'noorifa-core-tabs__tab' +
							( tab.clientId === currentId ? ' is-active' : '' )
						}
						onClick={ () => selectBlock( tab.clientId ) }
					>
						<RichText
							tagName="span"
							value={ tab.attributes.title }
							onChange={ ( value ) =>
								updateBlockAttributes( tab.clientId, {
									title: value,
								} )
							}
							placeholder={ __( 'Tab', 'noorifa-core' ) }
							allowedFormats={ [] }
							withoutInteractiveFormatting
						/>
					</div>
				) ) }
				<Button
					className="noorifa-core-tabs__add"
					icon={ plus }
					label={ __( 'Add tab', 'noorifa-core' ) }
					onClick={ addTab }
				/>
			</div>
			<div { ...innerBlocksProps } />
		</div>
	);
}
