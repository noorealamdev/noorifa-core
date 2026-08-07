import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	RangeControl,
	TextControl,
	ToggleControl,
	BaseControl,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import WooPlaceholder from '../../utils/woo-placeholder';

export default function Edit( { attributes, setAttributes } ) {
	const deadlineControlId = useInstanceId(
		Edit,
		'noorifa-core-urgency-deadline'
	);
	const {
		type,
		stockThreshold,
		stockMessage,
		showIcon,
		countdownSource,
		customDate,
		countdownLabel,
		hideWhenExpired,
		expiredMessage,
		boxed,
		boxedWidth,
	} = attributes;

	const instructions =
		type === 'stock'
			? __(
					'Shows a low-stock warning based on the live stock level once it drops to or below the threshold. Preview appears on the product page.',
					'noorifa-core'
			  )
			: __(
					'Counts down to the sale end date or a custom deadline. Preview appears on the product page.',
					'noorifa-core'
			  );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Urgency type', 'noorifa-core' ) }>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Type', 'noorifa-core' ) }
						value={ type }
						options={ [
							{
								label: __(
									'Low stock warning',
									'noorifa-core'
								),
								value: 'stock',
							},
							{
								label: __( 'Countdown timer', 'noorifa-core' ),
								value: 'countdown',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { type: value } )
						}
					/>
				</PanelBody>

				{ type === 'stock' && (
					<PanelBody
						title={ __( 'Low stock settings', 'noorifa-core' ) }
					>
						<RangeControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __(
								'Show when stock is at or below',
								'noorifa-core'
							) }
							value={ stockThreshold }
							onChange={ ( value ) =>
								setAttributes( { stockThreshold: value || 1 } )
							}
							min={ 1 }
							max={ 50 }
						/>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Message', 'noorifa-core' ) }
							help={ __(
								'Use {stock} where the remaining quantity should appear.',
								'noorifa-core'
							) }
							value={ stockMessage }
							onChange={ ( value ) =>
								setAttributes( { stockMessage: value } )
							}
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Show icon', 'noorifa-core' ) }
							checked={ showIcon }
							onChange={ ( value ) =>
								setAttributes( { showIcon: value } )
							}
						/>
					</PanelBody>
				) }

				{ type === 'countdown' && (
					<PanelBody
						title={ __( 'Countdown settings', 'noorifa-core' ) }
					>
						<SelectControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Count down to', 'noorifa-core' ) }
							value={ countdownSource }
							options={ [
								{
									label: __(
										"Product's scheduled sale end date",
										'noorifa-core'
									),
									value: 'sale',
								},
								{
									label: __(
										'Custom date & time',
										'noorifa-core'
									),
									value: 'custom',
								},
							] }
							onChange={ ( value ) =>
								setAttributes( { countdownSource: value } )
							}
						/>
						{ countdownSource === 'sale' && (
							<p className="components-base-control__help">
								{ __(
									'Only shows while the product has an active sale with a scheduled end date set in Product data → General.',
									'noorifa-core'
								) }
							</p>
						) }
						{ countdownSource === 'custom' && (
							<BaseControl
								__nextHasNoMarginBottom
								id={ deadlineControlId }
								label={ __( 'Deadline', 'noorifa-core' ) }
								help={ __(
									"Uses your site's timezone.",
									'noorifa-core'
								) }
							>
								<input
									type="datetime-local"
									className="components-text-control__input"
									value={ customDate }
									onChange={ ( event ) =>
										setAttributes( {
											customDate: event.target.value,
										} )
									}
								/>
							</BaseControl>
						) }
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Label', 'noorifa-core' ) }
							value={ countdownLabel }
							onChange={ ( value ) =>
								setAttributes( { countdownLabel: value } )
							}
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Hide once expired', 'noorifa-core' ) }
							checked={ hideWhenExpired }
							onChange={ ( value ) =>
								setAttributes( { hideWhenExpired: value } )
							}
							help={
								hideWhenExpired
									? __(
											'The block disappears once the deadline passes.',
											'noorifa-core'
									  )
									: __(
											'Shows the message below once the deadline passes.',
											'noorifa-core'
									  )
							}
						/>
						{ ! hideWhenExpired && (
							<TextControl
								__nextHasNoMarginBottom
								label={ __(
									'Expired message',
									'noorifa-core'
								) }
								value={ expiredMessage }
								onChange={ ( value ) =>
									setAttributes( { expiredMessage: value } )
								}
							/>
						) }
					</PanelBody>
				) }

				<PanelBody
					title={ __( 'Layout', 'noorifa-core' ) }
					initialOpen={ false }
				>
					<ToggleControl
						__nextHasNoMarginBottom
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
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Max width (px)', 'noorifa-core' ) }
							value={ boxedWidth }
							onChange={ ( value ) =>
								setAttributes( { boxedWidth: value } )
							}
							min={ 320 }
							max={ 1800 }
							step={ 10 }
						/>
					) }
				</PanelBody>
			</InspectorControls>

			<div
				{ ...useBlockProps( {
					className: boxed ? 'is-boxed' : undefined,
					style: boxed ? { maxWidth: boxedWidth } : undefined,
				} ) }
			>
				<WooPlaceholder
					icon="warning"
					label={ __( 'Urgency & Countdown', 'noorifa-core' ) }
					instructions={ instructions }
				/>
			</div>
		</>
	);
}
