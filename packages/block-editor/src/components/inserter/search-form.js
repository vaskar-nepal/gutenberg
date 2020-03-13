/**
 * WordPress dependencies
 */
import { useInstanceId } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

function InserterSearchForm( { onChange } ) {
	const instanceId = useInstanceId( InserterSearchForm );

	// Disable reason (no-autofocus): The inserter menu is a modal display, not one which
	// is always visible, and one which already incurs this behavior of autoFocus via
	// Popover's focusOnMount.
	/* eslint-disable jsx-a11y/no-autofocus */
	return (
		<>
			<label
				htmlFor={ `block-editor-inserter__search-${ instanceId }` }
				className="screen-reader-text"
			>
				{ __( 'Search for a block' ) }
			</label>
			<input
				id={ `block-editor-inserter__search-${ instanceId }` }
				type="search"
				placeholder={ __( 'Search for a block' ) }
				className="block-editor-inserter__search"
				autoFocus
				onChange={ ( event ) => onChange( event.target.value ) }
			/>
		</>
	);
	/* eslint-enable jsx-a11y/no-autofocus */
}

export default InserterSearchForm;
