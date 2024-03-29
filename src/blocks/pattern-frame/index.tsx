import { registerBlockType } from '@wordpress/blocks';
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { fullscreen } from '@wordpress/icons';
import { PanelBody, PanelRow, TextControl } from '@wordpress/components';

import blockJson from './block.json';

const Edit = ( { attributes, setAttributes }: blockProps ) => {
	const blockProps = useBlockProps();

	return <>
		<div { ...blockProps }>
			{ attributes?.title &&
				<span className={ 'blockify-pattern-editor-frame-title' }>
					{ attributes.title }
				</span>
			}
			<InnerBlocks
				templateLock={ false }
			/>
			<InspectorControls>
				<PanelBody
					title={ 'Settings' }
					initialOpen={ true }
				>
					<PanelRow>
						<TextControl
							label={ 'Title' }
							value={ attributes?.title ?? '' }
							onChange={ ( value: string ) => setAttributes( {
								title: value,
							} ) }
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
		</div>
	</>;
};

const Save = ( props: blockProps ) => {
	const blockProps = useBlockProps.save();

	return <div { ...blockProps }>
		<InnerBlocks.Content />
	</div>;
};

registerBlockType(
	blockJson,
	{
		icon: fullscreen,
		edit: Edit,
		save: Save,
	}
);
