import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import ServerSideRender from '@wordpress/server-side-render';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { symbol } from '@wordpress/icons';
import blockJson from './block.json';
import {
	__experimentalUnitControl as UnitControl,
	Button,
	PanelBody,
	PanelRow,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { ucWords } from '../../../../../../../resources/js/utility';
import './index.scss';
import Select, { SingleValue } from 'react-select';
import { useMemo } from '@wordpress/element';

interface BlockPattern {
	name: string;
	title: string;
	categories: string[];
}

interface PatternOption {
	label: string;
	value: string;
}

interface BlockAttributes {
	slug?: string;
	iframe?: boolean;
	height?: string;
	preview?: boolean;
}

interface EditProps {
	attributes: BlockAttributes;
	setAttributes: ( newAttributes: BlockAttributes ) => void;
}

const Edit = ( { attributes, setAttributes }: EditProps ) => {
	const blockProps = useBlockProps();

	const patterns: BlockPattern[] = useSelect( ( select ) => {
		return select( 'core' )?.getBlockPatterns();
	} );

	const patternKeysValues: { [name: string]: string } = {};

	for ( const pattern of patterns ) {
		const category = pattern?.categories?.[ 0 ] ?? 'uncategorized';
		const acronymCategories = [ 'cta', 'faq' ];
		let categoryLabel = ucWords( category.replace( '-', ' ' ) );

		const patternLabel = pattern.title.replace( categoryLabel, '' ).trim();

		if ( acronymCategories.includes( category ) ) {
			categoryLabel = category.toUpperCase();
		}

		patternKeysValues[ pattern.name ] = categoryLabel + ' ' + patternLabel;
	}

	const ordered = Object.keys( patternKeysValues ).sort().reduce( ( obj: {
		[name: string]: string;
	}, key: string ) => {
		obj[ key ] = patternKeysValues[ key ];
		return obj;
	}, {} );

	const patternOptions: PatternOption[] = [];

	for ( const [ key, value ] of Object.entries( ordered ) ) {
		patternOptions.push( {
			label: value,
			value: key,
		} );
	}

	const placeholder = patternOptions?.length > 0 ? __( 'Select pattern', 'pattern-editor' ) : __( 'Loading patterns', 'pattern-editor' );

	const PatternSelect = () => <Select
		isMulti={ false }
		isSearchable
		placeholder={ placeholder }
		value={ patternOptions.find( ( option: PatternOption ) => option.value === attributes?.slug ) }
		options={ patternOptions }
		onChange={ ( value: SingleValue<PatternOption> ) => {
			setAttributes( {
				slug: value?.value ?? '',
			} );
		} }
		isClearable={ true }
	/>;

	const memoizedServerSideRender = useMemo( () => {
		return <ServerSideRender
			block={ 'blockify/pattern' }
			attributes={ attributes }
			LoadingResponsePlaceholder={ () => <div>
				<p>{ __( 'Loading Pattern', 'pattern-editor' ) }</p>
			</div> }
			EmptyResponsePlaceholder={ () => <div>
				<p>{ __( 'Select Pattern', 'pattern-editor' ) }</p>
				<PatternSelect />
			</div> }
		/>;
	}, [ attributes ] );

	return <>
		<div { ...blockProps }>
			{ attributes?.preview && memoizedServerSideRender }
			{ ! attributes?.preview &&
				<div
					style={ {
						padding: '1em',
						background: '#fff',
						fontSize: '14px',
					} }
				>
					<PatternSelect />
				</div>
			}
		</div>
		<InspectorControls>
			<PanelBody
				title={ __( 'Select Pattern', 'pattern-editor' ) }
				initialOpen={ true }
				className={ 'blockify-pattern-settings' }
			>
				<PanelRow>
					<PatternSelect />
				</PanelRow>
				<PanelRow>
					<TextControl
						label={ __( 'Slug', 'pattern-editor' ) }
						value={ attributes?.slug ?? '' }
						onChange={ ( value: string ) => {
							setAttributes( {
								slug: value,
							} );
						} }
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label={ __( 'Preview', 'pattern-editor' ) }
						checked={ attributes?.preview }
						onChange={ ( value: boolean ) => {
							setAttributes( {
								preview: value,
							} );
						} }
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label={ __( 'Iframe', 'pattern-editor' ) }
						checked={ attributes?.iframe }
						onChange={ ( value: boolean ) => {
							setAttributes( {
								iframe: value,
							} );
						} }
					/>
				</PanelRow>
				{ attributes?.iframe &&
					<>
						<PanelRow>
							<UnitControl
								label={ __( 'Height', 'pattern-editor' ) }
								value={ attributes?.height }
								onChange={ ( value: string ) => {
									setAttributes( {
										height: value,
									} );
								} }
							/>
						</PanelRow>
					</>
				}
				<PanelRow>
					<Button
						variant={ 'link' }
						href={ window.blockify.adminUrl + '?edit_pattern=' + attributes.slug }
						target={ '_blank' }
					>
						{ __( 'Edit Pattern ↗', 'pattern-editor' ) }
					</Button>
				</PanelRow>
			</PanelBody>
		</InspectorControls>
	</>;
};

registerBlockType( blockJson, {
	icon: symbol,
	edit: Edit,
} );
