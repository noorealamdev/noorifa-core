import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button, Dropdown, Icon, TextControl } from '@wordpress/components';
import * as wpIcons from '@wordpress/icons';

/*
 * A handful of hand-drawn additions @wordpress/icons doesn't have (no
 * health/wellness icons in that library) — prefixed "nt-" so they can
 * never collide with a future @wordpress/icons export name. Mirrored
 * server-side in includes/Blocks/WP_Icon_Registry.php (both generated
 * by scripts/generate-icon-registry.js, which embeds the same markup).
 */
function ShieldCheckIcon() {
	return (
		<svg
			xmlns="http://www.w3.org/2000/svg"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
			strokeLinejoin="round"
		>
			<path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3z" />
			<path d="M9 12l2 2 4-4" />
		</svg>
	);
}

function LeafIcon() {
	return (
		<svg
			xmlns="http://www.w3.org/2000/svg"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
			strokeLinejoin="round"
		>
			<path d="M5 19c0-8 5-15 14-15 0 9-6 15-14 15z" />
			<path d="M5 19c3-3 6-6 9-11" />
		</svg>
	);
}

function CapsuleIcon() {
	return (
		<svg
			xmlns="http://www.w3.org/2000/svg"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
			strokeLinejoin="round"
		>
			<path d="M7 17a5 5 0 010-7l3-3a5 5 0 017 7l-3 3a5 5 0 01-7 0z" />
			<path d="M9.5 9.5l5 5" />
		</svg>
	);
}

function MedalIcon() {
	return (
		<svg
			xmlns="http://www.w3.org/2000/svg"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
			strokeLinejoin="round"
		>
			<circle cx="12" cy="8" r="4.5" />
			<path d="M9 12l-2 8 5-3 5 3-2-8" />
		</svg>
	);
}

const EXTRA_ICONS = {
	'nt-shieldCheck': { label: __( 'Shield check', 'noorifa-core' ), icon: ShieldCheckIcon },
	'nt-leaf': { label: __( 'Leaf', 'noorifa-core' ), icon: LeafIcon },
	'nt-capsule': { label: __( 'Capsule', 'noorifa-core' ), icon: CapsuleIcon },
	'nt-medal': { label: __( 'Medal', 'noorifa-core' ), icon: MedalIcon },
};

function humanizeKey( key ) {
	return key
		.replace( /([a-z0-9])([A-Z])/g, '$1 $2' )
		.replace( /^./, ( first ) => first.toUpperCase() );
}

/**
 * Every icon available to pick from — the full @wordpress/icons library
 * (the same set used throughout the block editor's own UI) plus a few
 * health/wellness icons it doesn't have.
 */
export const ALL_ICONS = [
	...Object.entries( EXTRA_ICONS ).map( ( [ key, { label, icon } ] ) => ( {
		key,
		label,
		icon,
	} ) ),
	...Object.keys( wpIcons )
		.filter( ( key ) => 'Icon' !== key )
		.sort()
		.map( ( key ) => ( {
			key,
			label: humanizeKey( key ),
			icon: wpIcons[ key ],
		} ) ),
];

const DEFAULT_ICON = ALL_ICONS[ 0 ];

const NONE_ICON = {
	key: '',
	label: __( 'No icon', 'noorifa-core' ),
	icon: wpIcons.close,
};

export function getIconByKey( key ) {
	if ( ! key ) {
		return NONE_ICON;
	}

	return ALL_ICONS.find( ( item ) => item.key === key ) || DEFAULT_ICON;
}

const MAX_VISIBLE = 200;

/**
 * A searchable icon picker button + popover, backed by the full icon set
 * above. Shared across every block that lets the user pick an icon per
 * row/item, so there's exactly one picker UI to maintain.
 *
 * @param {Object}   props           Component props.
 * @param {string}   props.value     Selected icon key (empty string = no icon).
 * @param {Function} props.onChange  Called with the new icon key.
 * @param {boolean}  [props.allowNone] Whether "No icon" is a selectable option.
 */
export default function IconPicker( { value, onChange, allowNone } ) {
	const [ search, setSearch ] = useState( '' );
	const current = getIconByKey( value );

	const filtered = search
		? ALL_ICONS.filter( ( item ) =>
				item.label.toLowerCase().includes( search.toLowerCase() )
		  )
		: ALL_ICONS;

	return (
		<Dropdown
			className="noorifa-core-icon-picker"
			contentClassName="noorifa-core-icon-picker__popover"
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Button
					className={
						'noorifa-core-icon-picker__toggle' +
						( value ? '' : ' is-empty' )
					}
					onClick={ onToggle }
					aria-expanded={ isOpen }
					label={ __( 'Choose icon', 'noorifa-core' ) }
					showTooltip
				>
					<Icon icon={ current.icon } />
				</Button>
			) }
			renderContent={ ( { onClose } ) => (
				<div className="noorifa-core-icon-picker__content">
					<TextControl
						__nextHasNoMarginBottom
						className="noorifa-core-icon-picker__search"
						placeholder={ __( 'Search icons…', 'noorifa-core' ) }
						value={ search }
						onChange={ setSearch }
					/>
					<div className="noorifa-core-icon-picker__grid">
						{ allowNone && (
							<Button
								className="noorifa-core-icon-picker__option noorifa-core-icon-picker__option--none"
								isPressed={ ! value }
								label={ __( 'No icon', 'noorifa-core' ) }
								showTooltip
								onClick={ () => {
									onChange( '' );
									onClose();
								} }
							>
								<Icon icon={ wpIcons.close } />
							</Button>
						) }
						{ filtered.slice( 0, MAX_VISIBLE ).map( ( item ) => (
							<Button
								key={ item.key }
								className="noorifa-core-icon-picker__option"
								isPressed={ item.key === value }
								label={ item.label }
								showTooltip
								onClick={ () => {
									onChange( item.key );
									onClose();
								} }
							>
								<Icon icon={ item.icon } />
							</Button>
						) ) }
						{ ! filtered.length && (
							<p className="noorifa-core-icon-picker__empty">
								{ __( 'No icons found.', 'noorifa-core' ) }
							</p>
						) }
					</div>
					{ filtered.length > MAX_VISIBLE && (
						<p className="noorifa-core-icon-picker__hint">
							{ __(
								'Keep typing to narrow the results…',
								'noorifa-core'
							) }
						</p>
					) }
				</div>
			) }
		/>
	);
}
