import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	SelectControl,
	RangeControl,
} from '@wordpress/components';
import { Fragment } from '@wordpress/element';

import { VISIBILITY_DEFAULT, ANIMATION_DEFAULT, isExtendable } from './shared';

/**
 * Adds the visibility/animation attributes to every Noorifa Core block.
 *
 * @param {Object} settings Block settings.
 * @param {string} name     Block name.
 * @return {Object} Filtered settings.
 */
function addExtensionAttributes( settings, name ) {
	if ( ! isExtendable( name ) ) {
		return settings;
	}

	return {
		...settings,
		attributes: {
			...settings.attributes,
			noorifaCoreVisibility: {
				type: 'object',
				default: VISIBILITY_DEFAULT,
			},
			noorifaCoreAnimation: {
				type: 'object',
				default: ANIMATION_DEFAULT,
			},
		},
	};
}

addFilter(
	'blocks.registerBlockType',
	'noorifa-core/extensions/attributes',
	addExtensionAttributes
);

/**
 * Appends a "Visibility" and "Animation" Inspector panel to every
 * Noorifa Core block, after its own controls.
 */
const withExtensionControls = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		if ( ! isExtendable( props.name ) ) {
			return <BlockEdit { ...props } />;
		}

		const { attributes, setAttributes } = props;
		const visibility = {
			...VISIBILITY_DEFAULT,
			...attributes.noorifaCoreVisibility,
		};
		const animation = {
			...ANIMATION_DEFAULT,
			...attributes.noorifaCoreAnimation,
		};

		const updateVisibility = ( key, value ) =>
			setAttributes( {
				noorifaCoreVisibility: { ...visibility, [ key ]: value },
			} );

		const updateAnimation = ( key, value ) =>
			setAttributes( {
				noorifaCoreAnimation: { ...animation, [ key ]: value },
			} );

		return (
			<Fragment>
				<BlockEdit { ...props } />
				<InspectorControls>
					<PanelBody
						title={ __( 'Visibility', 'noorifa-core' ) }
						initialOpen={ false }
					>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Hide on desktop', 'noorifa-core' ) }
							checked={ visibility.hideOnDesktop }
							onChange={ ( value ) =>
								updateVisibility( 'hideOnDesktop', value )
							}
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Hide on tablet', 'noorifa-core' ) }
							checked={ visibility.hideOnTablet }
							onChange={ ( value ) =>
								updateVisibility( 'hideOnTablet', value )
							}
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Hide on mobile', 'noorifa-core' ) }
							checked={ visibility.hideOnMobile }
							onChange={ ( value ) =>
								updateVisibility( 'hideOnMobile', value )
							}
						/>
					</PanelBody>
					<PanelBody
						title={ __( 'Animation', 'noorifa-core' ) }
						initialOpen={ false }
					>
						<SelectControl
							__nextHasNoMarginBottom
							label={ __( 'Entrance animation', 'noorifa-core' ) }
							value={ animation.type }
							options={ [
								{
									label: __( 'None', 'noorifa-core' ),
									value: 'none',
								},
								{
									label: __( 'Fade in', 'noorifa-core' ),
									value: 'fade-in',
								},
								{
									label: __( 'Slide up', 'noorifa-core' ),
									value: 'slide-up',
								},
								{
									label: __( 'Zoom in', 'noorifa-core' ),
									value: 'zoom-in',
								},
							] }
							onChange={ ( value ) =>
								updateAnimation( 'type', value )
							}
						/>
						{ animation.type !== 'none' && (
							<>
								<RangeControl
									__nextHasNoMarginBottom
									label={ __(
										'Duration (ms)',
										'noorifa-core'
									) }
									min={ 100 }
									max={ 3000 }
									step={ 50 }
									value={ animation.duration }
									onChange={ ( value ) =>
										updateAnimation( 'duration', value )
									}
								/>
								<RangeControl
									__nextHasNoMarginBottom
									label={ __( 'Delay (ms)', 'noorifa-core' ) }
									min={ 0 }
									max={ 3000 }
									step={ 50 }
									value={ animation.delay }
									onChange={ ( value ) =>
										updateAnimation( 'delay', value )
									}
								/>
							</>
						) }
					</PanelBody>
				</InspectorControls>
			</Fragment>
		);
	},
	'withNoorifaCoreExtensionControls'
);

addFilter(
	'editor.BlockEdit',
	'noorifa-core/extensions/inspector-controls',
	withExtensionControls
);

/**
 * Writes the resulting visibility classes and animation data attributes
 * onto the saved markup of static (save.js) Noorifa Core blocks.
 *
 * @param {Object} extraProps Block save element props.
 * @param {Object} blockType  Block type object.
 * @param {Object} attributes Block attributes.
 * @return {Object} Filtered props.
 */
function addExtensionSaveProps( extraProps, blockType, attributes ) {
	if ( ! isExtendable( blockType.name ) ) {
		return extraProps;
	}

	const visibility = {
		...VISIBILITY_DEFAULT,
		...attributes.noorifaCoreVisibility,
	};
	const animation = {
		...ANIMATION_DEFAULT,
		...attributes.noorifaCoreAnimation,
	};

	const classNames = [ extraProps.className ];

	if ( visibility.hideOnMobile ) {
		classNames.push( 'noorifa-core-hide-mobile' );
	}

	if ( visibility.hideOnTablet ) {
		classNames.push( 'noorifa-core-hide-tablet' );
	}

	if ( visibility.hideOnDesktop ) {
		classNames.push( 'noorifa-core-hide-desktop' );
	}

	const nextProps = {
		...extraProps,
		className: classNames.filter( Boolean ).join( ' ' ) || undefined,
	};

	if ( animation.type && 'none' !== animation.type ) {
		nextProps[ 'data-noorifa-core-animation' ] = animation.type;
		nextProps[ 'data-noorifa-core-duration' ] = animation.duration;
		nextProps[ 'data-noorifa-core-delay' ] = animation.delay;
	}

	return nextProps;
}

addFilter(
	'blocks.getSaveContent.extraProps',
	'noorifa-core/extensions/save-props',
	addExtensionSaveProps
);
