import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { layout } from '@wordpress/icons';
import blockJson from './block.json';

const Edit = ( props: blockProps ) => {
	const blockProps = useBlockProps();

	return <>
		<div { ...blockProps }>
			<InnerBlocks
				allowedBlocks={ [
					'blockify/pattern-canvas',
				] }
				template={ [
					[
						'blockify/pattern-canvas',
					],
				] }
				templateLock={ 'all' }
			/>
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
		icon: layout,
		edit: Edit,
		save: Save,
	}
);
