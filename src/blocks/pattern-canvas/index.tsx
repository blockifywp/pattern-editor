import { registerBlockType } from '@wordpress/blocks';
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { grid } from '@wordpress/icons';
import { PanZoom } from 'react-easy-panzoom';
import { PanelBody, PanelRow, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import './index.scss';
import blockJson from './block.json';

const Edit = ( { attributes, setAttributes, clientId }: blockProps ) => {
	const blockProps = useBlockProps();
	const style = blockProps.style ?? {};

	// get number of inner blocks.
	const innerBlocks = useSelect( ( select ) => {
		const { getBlock } = select( 'core/block-editor' );
		return getBlock( clientId )?.innerBlocks ?? [];
	}, [ clientId ] );

	if ( innerBlocks ) {
		style[ '--inner-blocks' ] = innerBlocks.length;
	}
	return <>
		<div { ...blockProps }>
			<PanZoom
				disableKeyInteraction={ true }
				disableDoubleClickZoom={ true }
				disabled={ ! attributes?.enabled }
			>
				<span className={ 'screen-reader-text' }>
					{ 'Pan and zoom canvas' }
				</span>
				<InnerBlocks
					allowedBlocks={ [
						'blockify/pattern-frame',
					] }
					template={ [
						[
							'blockify/pattern-frame',
						],
					] }
					templateLock={ false }
				/>
			</PanZoom>
			<InspectorControls>
				<PanelBody
					title={ 'Settings' }
					initialOpen={ true }
				>
					<PanelRow>
						<ToggleControl
							label={ 'Enable pan and zoom' }
							checked={ attributes?.enabled ?? false }
							onChange={ ( value: boolean ) => setAttributes( {
								enabled: value,
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
		icon: grid,
		edit: Edit,
		save: Save,
	}
);
