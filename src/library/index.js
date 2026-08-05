import { registerPlugin } from '@wordpress/plugins';

import Library from './library';
import './editor.scss';

registerPlugin( 'noorifa-core-library', {
	render: Library,
} );
