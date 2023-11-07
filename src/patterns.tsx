import { __, sprintf } from '@wordpress/i18n';
import { toKebabCase } from '../../../themes/blockify/src/utility';

interface blockifyPatterns {
	nonce: string;
	restUrl: string;
	adminUrl: string;
	currentUser: number;
	stylesheet: string;
	stylesheetDir: string;
	isChildTheme: boolean;
}

const patternEditor: blockifyPatterns = window?.blockifyPatterns ?? {
	restUrl: '',
	nonce: '',
	currentUser: 0,
	adminUrl: '',
	stylesheet: '',
	patternDir: '',
	imgDir: '',
	isChildTheme: false,
};

const exportPatterns = () => {
	const exportButton = document.createElement( 'a' );
	const addNewButton = document.getElementsByClassName( 'page-title-action' ).item( 0 );

	const adminPostUrl = patternEditor?.adminUrl + 'admin-post.php?action=blockify_export_patterns';

	exportButton.classList.add( 'blockify-patterns-import', 'page-title-action' );
	exportButton.innerHTML = __( 'Export Patterns', 'blockify' );

	if ( addNewButton ) {
		addNewButton.after( exportButton );
	}

	exportButton.addEventListener( 'click', () => {
		const confirmed = confirm(
			sprintf(
				// translators: %s: theme name.
				__(
					'WARNING: This will overwrite all block pattern HTML files in the following theme: %s. Would you like to continue?',
					'blockify'
				),
				patternEditor?.stylesheet
			)
		);

		if ( confirmed ) {
			window.location.href = adminPostUrl;
		}
	} );
};

document.addEventListener( 'DOMContentLoaded', exportPatterns );

const importPatterns = () => {
	const importButton = document.createElement( 'a' );
	const addNewButton = document.getElementsByClassName( 'page-title-action' ).item( 0 );
	const link = patternEditor?.adminUrl + 'admin-post.php?action=blockify_import_patterns';

	importButton.classList.add( 'blockify-patterns-import', 'page-title-action' );
	importButton.innerHTML = __( 'Import Patterns', 'blockify' );
	importButton.onclick = () => {
		const themeName = patternEditor?.stylesheet?.charAt( 0 )?.toUpperCase() + patternEditor?.stylesheet?.slice( 1 );

		const confirmed: boolean = confirm(
			sprintf(
				// translators: %s: theme name.
				__( 'Import all block patterns? (Active theme: %s)',
					'blockify'
				),
				themeName
			)
		);

		return confirmed ? window.location.href = link : false;
	};

	if ( addNewButton ) {
		addNewButton.after( importButton );
	}
};

document.addEventListener( 'DOMContentLoaded', importPatterns );

const deletePatterns = () => {
	const deleteButton = document.createElement( 'a' );
	const addNewButton = document.getElementsByClassName( 'page-title-action' ).item( 0 );
	const link = patternEditor?.adminUrl + 'admin-post.php?action=blockify_delete_patterns';

	deleteButton.classList.add( 'blockify-patterns-delete', 'page-title-action' );
	deleteButton.innerHTML = __( 'Delete Patterns', 'blockify' );
	deleteButton.onclick = () => {
		const confirmed: boolean = confirm(
			__( 'Delete all block patterns?',
				'blockify'
			),
		);

		return confirmed ? window.location.href = link : false;
	};

	if ( addNewButton ) {
		addNewButton.after( deleteButton );
	}
};

document.addEventListener( 'DOMContentLoaded', deletePatterns );

const addPreviewRowAction = () => {
	const rowActions = document.getElementsByClassName( 'row-actions' ) as HTMLCollectionOf<HTMLDivElement>;

	for ( let i = 0; i < rowActions.length; i++ ) {
		const rowAction = rowActions.item( i );

		if ( ! rowAction ) {
			continue;
		}

		const title = rowAction.closest( 'tr' )?.getElementsByClassName( 'row-title' )?.[ 0 ]?.textContent?.trim() ?? '';

		if ( ! title ) {
			continue;
		}

		const slug = toKebabCase( title );
		const span = document.createElement( 'span' );
		const link = document.createElement( 'a' );
		const isPage = slug?.includes( 'page-' );

		let url = '/?page_id=9999&pattern_name=' + slug;

		if ( isPage ) {
			url += '&show_main=false';
		} else {
			url += '&template=blank';
		}

		span.setAttribute( 'class', 'preview-link' );
		link.setAttribute( 'href', url );
		link.setAttribute( 'target', '_blank' );
		link.setAttribute( 'aria-label', __( 'Preview pattern', 'blockify' ) );
		link.innerHTML = __( 'Preview', 'blockify' );

		span.appendChild( document.createTextNode( ' | ' ) );
		span.appendChild( link );
		rowAction.appendChild( span );
	}
};

document.addEventListener( 'DOMContentLoaded', addPreviewRowAction );
