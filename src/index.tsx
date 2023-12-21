import { render, useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { check, external } from '@wordpress/icons';
import { select } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { registerPlugin } from '@wordpress/plugins';

const PatternEditor = () => {
	const postHeaderSettings = document.getElementsByClassName( 'edit-post-header__settings' )[ 0 ] as HTMLDivElement;

	const [ exporting, setExporting ] = useState( false );
	const [ exported, setExported ] = useState( false );

	if ( ! postHeaderSettings ) {
		return <></>;
	}

	const placeholderId = 'blockify-pattern-editor';
	const existing = document.getElementById( placeholderId );
	let placeholder = null;

	if ( ! existing ) {
		placeholder = document.createElement( 'div' );
		placeholder.id = placeholderId;

		postHeaderSettings.insertBefore( placeholder, postHeaderSettings.firstChild );
	} else {
		placeholder = existing;
	}

	const editorData = select( 'core/editor' );

	const slug = editorData.getEditedPostSlug() ?? '';

	const publishButton = postHeaderSettings.getElementsByClassName( 'editor-post-publish-button' )[ 0 ] as HTMLButtonElement;

	const exportPlaceholderId = 'blockify-pattern-export';
	const exportExisting = document.getElementById( exportPlaceholderId );
	let exportPlaceholder = null;

	if ( ! exportExisting ) {
		exportPlaceholder = document.createElement( 'div' );
		exportPlaceholder.id = exportPlaceholderId;

		postHeaderSettings.insertBefore( exportPlaceholder, publishButton );
	} else {
		exportPlaceholder = exportExisting;
	}

	render(
		<>
			<Button
				href={ '/?page_id=9999&pattern_name=' + slug }
				target={ '_blank' }
				icon={ external }
				label={ __( 'Preview pattern', 'pattern-editor' ) }
			/>
		</>,
		placeholder
	);

	const exportPattern = () => {
		apiFetch( {
			path: '/blockify/v1/export-pattern',
			method: 'POST',
			data: {
				slug,
				id: editorData.getCurrentPostId() ?? 0,
				content: editorData.getEditedPostContent() ?? '',
				title: editorData.getEditedPostAttribute( 'title' ) ?? '',
			},
		} ).then( ( response ) => {
			setExporting( false );

			if ( response?.success === true ) {
				setExported( true );

				setTimeout( () => {
					setExported( false );
				}, 2000 );
			}
		} ).catch( ( error ) => {
			setExporting( false );

			console.error( error );
		} ).finally( () => {
			setExporting( false );
		} );
	};

	return <></>;

	if ( publishButton ) {
		render(
			<>
				<Button
					variant={ 'secondary' }
					isBusy={ exporting }
					onClick={ () => {
						setExporting( true );
						exportPattern();
					} }
					label={ __( 'Export pattern to theme', 'pattern-editor' ) }
					aria-label={ __( 'Export pattern to theme', 'pattern-editor' ) }
					title={ __( 'Export pattern to theme', 'pattern-editor' ) }
				>
					{ ( ! exporting && ! exported ) &&
						__( 'Export', 'pattern-editor' )
					}
					{ ( exporting && ! exported ) &&
						__( 'Exporting…', 'pattern-editor' )
					}
					{ ( ! exporting && exported ) &&
						<>
							{ __( 'Exported!', 'pattern-editor' ) }
							{ check }
						</>
					}
				</Button>
			</>,
			exportPlaceholder
		);
	}

	return <></>;
};

registerPlugin( 'blockify-pattern-editor', {
	render: () => <PatternEditor />,
} );
