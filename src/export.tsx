import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import { subscribe } from '@wordpress/data';

const addDarkMode = () => {
	const editorStylesWrapper = ( document.getElementsByClassName( 'editor-styles-wrapper' )[ 0 ] ?? null ) as HTMLElement;

	if ( ! editorStylesWrapper ) {
		return;
	}

	const postHeaderSettings = document.getElementsByClassName( 'edit-post-header__settings' )[ 0 ] as HTMLDivElement;

	const onClick = () => {

		// do ajax call.

	};

	if ( postHeaderSettings ) {
		const id = 'blockify-export-pattern';

		const existing = document.getElementById( id );

		if ( ! existing ) {
			const button = document.createElement( 'button' );

			const label = __( 'Export', 'blockify-pro' );

			button.id = id;
			button.classList.add( 'components-button' );
			button.setAttribute( 'aria-label', label );
			button.setAttribute( 'title', label );
			button.innerHTML = __( 'Export', 'blockify-pro' );

			button.addEventListener( 'click', onClick );

			postHeaderSettings.insertBefore( button, postHeaderSettings.firstChild );
		}
	}
};

domReady( () => {
	subscribe( addDarkMode );
} );
