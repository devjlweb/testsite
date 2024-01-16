/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button, Icon } from '@wordpress/components';
import { check, close } from '@wordpress/icons';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import checkboxList from './icons/checkbox-list';
import { EidWrapper } from '../common/components/eid-wrapper';
import { store as publishGuideStore } from './store';

const PublishGuideButton = ( {
	isCompleted,
	setPublishGuideMenuOpened,
	publishGuideInteracted,
} ) => {
	const {
		isPublishGuideOpened,
	} = useSelect( ( select ) => ( {
		isPublishGuideOpened: select( publishGuideStore ).isPublishGuideOpened(),
	} ), [] );

	const {
		togglePublishGuide,
	} = useDispatch( publishGuideStore );

	const fabAction = () => {
		/*
			The system plugin script that captures eid clicks is running after we update the entity object
			Therefore we should wait for that script to run before updating the publishGuideOpen state
			otherwise the SP script will run using the next value of the entity object
		*/
		setTimeout( () => {
			togglePublishGuide();

			if ( ! publishGuideInteracted ) {
				setPublishGuideMenuOpened();
			}
		}, 0 );
	};
	let fabCta = (
		<span className="publish-guide-trigger__button__icon-check">
			<Icon icon={ checkboxList } />
		</span>
	);

	const [ buttonIsHovered, setButtonIsHovered ] = useState( false );

	if ( isPublishGuideOpened && ! isCompleted ) {
		fabCta = (
			<span className="publish-guide-trigger__button__icon-close">
				<Icon icon={ close } />
			</span>
		);
	}

	const completedFab = buttonIsHovered ? (
		<div className="publish-guide-trigger__button__launch">
			{ __( 'Launch Site', 'godaddy-launch' ) }
		</div>
	) : <Icon icon={ check } />;

	return (
		<EidWrapper
			action="click"
			section="guide"
			target={ isPublishGuideOpened ? 'close' : 'open' }
		>
			<Button
				aria-label={ __( 'Publish Guide', 'godaddy-launch' ) }
				className={ classnames(
					'publish-guide-trigger__button',
					{ 'is-opened': isPublishGuideOpened }
				) }
				id="gdl-publish-guide-trigger-button"
				isPrimary
				onClick={ fabAction }
				onMouseEnter={ () => setButtonIsHovered( true ) }
				onMouseLeave={ () => setButtonIsHovered( false ) }
			>
				{ isCompleted ? completedFab : fabCta }
			</Button>
		</EidWrapper>
	);
};

export default PublishGuideButton;
