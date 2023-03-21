const patternEditor = window?.blockifyPatternEditor ?? {
	restUrl: '',
	nonce: '',
	currentUser: '',
	adminUrl: '',
	stylesheet: '',
	patternDir: '',
	imgDir: '',
	isChildTheme: false,
};

const __ = window.wp.i18n.__;

const togglePatternPreview = () => {
	const toggleButton    = document.createElement( 'span' );
	const postQuerySubmit = document.getElementById( 'post-query-submit' );

	toggleButton.classList.add( 'button', 'blockify-patterns-grid-button' );
	toggleButton.innerHTML = __( 'Toggle Preview', 'blockify-pro' );

	if ( postQuerySubmit ) {
		postQuerySubmit.after( toggleButton );
	}

	toggleButton.addEventListener( 'click', () => {
		document.body.classList.toggle( 'show-patterns' );

		const path = patternEditor?.restUrl + 'wp/v2/users/' + patternEditor?.currentUser;

		fetch( path, {
			method: 'POST',
			credentials: 'same-origin',
			body: JSON.stringify( {
				meta: {
					blockify_show_patterns: document.body.classList.contains( 'show-patterns' ) ? '1' : '0'
				}
			} ),
			headers: {
				'X-WP-Nonce': patternEditor?.nonce ?? '',
				'Content-Type': 'application/json'
			}
		} ).then( response => {
			return response.json();
		} ).then( data => {
			console.log( data );
		} );
	} );
};

const exportPatterns = () => {
	const exportButton = document.createElement( 'a' );
	const addNewButton = document.getElementsByClassName( 'page-title-action' ).item( 0 );

	const adminPostUrl = patternEditor?.adminUrl + 'admin-post.php?action=blockify_export_patterns';

	exportButton.classList.add( 'blockify-patterns-import', 'page-title-action' );
	exportButton.innerHTML = __( 'Export Patterns', 'blockify-pro' );

	addNewButton.after( exportButton );

	exportButton.addEventListener( 'click', () => {
		const confirmValue = confirm( __( 'WARNING: This will overwrite all block pattern HTML files in the following theme:' + patternEditor?.stylesheet + '. We recommend creating your own child theme. Would you like to continue?', 'blockify-pro' ) );

		if ( confirmValue ) {
			window.location.href = adminPostUrl;
		}
	} );
}

const importPatterns = () => {
	const importButton = document.createElement( 'a' );
	const addNewButton = document.getElementsByClassName( 'page-title-action' ).item( 0 );
	const link         = patternEditor?.adminUrl + 'admin-post.php?action=blockify_import_patterns';

	importButton.classList.add( 'blockify-patterns-import', 'page-title-action' );
	importButton.innerHTML = __( 'Import Patterns', 'blockify-pro' );
	importButton.onclick   = () => confirm( __( 'Import all registered block patterns? (Active theme: ' + patternEditor?.stylesheet?.charAt( 0 )?.toUpperCase() + patternEditor?.stylesheet?.slice( 1 ) + ')', 'blockify-pro' ) ) ? window.location.href = link : false;

	addNewButton.after( importButton );
}

const exportPath = () => {
	const postsFilter       = document.getElementById( 'adv-settings' );
	const container         = document.createElement( 'fieldset' );
	const legend            = document.createElement( 'legend' );
	const separator         = document.createElement( 'br' );
	const patternLabel      = document.createElement( 'label' );
	const imgLabel          = document.createElement( 'label' );
	const patternExportPath = document.createElement( 'input' );
	const imgExportPath     = document.createElement( 'input' );

	legend.innerHTML = __( 'Export Paths', 'blockify-pro' );

	patternLabel.innerHTML = __( 'Pattern Export Path', 'blockify-pro' );
	patternLabel.htmlFor   = 'blockify-pattern-export-path';
	patternExportPath.classList.add( 'blockify-patterns-export-path' );
	patternExportPath.type             = 'text';
	patternExportPath.placeholder      = __( 'themes/child-theme/patterns', 'blockify-pro' );
	patternExportPath.value            = patternEditor?.patternDir ?? '';
	patternExportPath.style.marginLeft = '5px';

	imgLabel.innerHTML = __( 'Image Export Path', 'blockify-pro' );
	imgLabel.htmlFor   = 'blockify-pattern-image-export-path';
	imgExportPath.classList.add( 'blockify-patterns-export-path' );
	imgExportPath.type             = 'text';
	imgExportPath.placeholder      = __( 'themes/child-theme/images', 'blockify-pro' );
	imgExportPath.value            = patternEditor?.imgDir ?? '';
	imgExportPath.style.marginLeft = '5px';

	separator.style.marginBottom = '10px';

	container.appendChild( legend );
	container.appendChild( patternLabel );
	container.appendChild( patternExportPath );
	container.appendChild( separator );
	container.appendChild( imgLabel );
	container.appendChild( imgExportPath );
	postsFilter.before( container );
}

document.addEventListener( 'DOMContentLoaded', exportPath );
document.addEventListener( 'DOMContentLoaded', togglePatternPreview );
document.addEventListener( 'DOMContentLoaded', exportPatterns );
document.addEventListener( 'DOMContentLoaded', importPatterns );
