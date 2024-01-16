/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { GlobeIcon } from '@godaddy-wordpress/coblocks-icons';
import { useCopyToClipboard } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { Button, Modal } from '@wordpress/components';
import { Icon, pages } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Confetti from '../common/components/confetti';

const LiveSiteLaunchSuccessModal = ( { closeModal } ) => {
	const [ copyText, setCopyText ] = useState( __( 'Copy the URL', 'godaddy-launch' ) );

	const forceHttps = ( url ) => {
		if ( url.includes( '//localhost' ) || url.includes( '//gdl.test' ) ) {
			return url;
		}

		return url.replace( 'http:/', 'https:/' );
	};

	const { url } = useSelect( ( select ) => {
		return {
			url: forceHttps( select( 'core' ).getSite()?.url ),
		};
	} );

	const ref = useCopyToClipboard( url, () => setCopyText( __( 'Copied!', 'godaddy-launch' ) ) );

	return (
		<>
			<Modal
				className="gdl-launch-site-success-modal godaddy-styles"
				onRequestClose={ closeModal }
				title={ __( 'Good work! Your site is live.', 'godaddy-launch' ) }
			>
				<p className="gdl-launch-site-success-modal__description">{ __( 'Show it off to all of your family, friends, and customers.', 'godaddy-launch' ) }</p>
				<div className="gdl-launch-site-success-modal__content">
					<div className="gdl-launch-site-success-modal__site-description-container">
						<div className="gdl-launch-site-success-modal__site-description-container__icon-container border-right">
							<Icon icon={ GlobeIcon } size={ 18 } />
						</div>
						<p className="gdl-launch-site-success-modal__site-description-container__site-name">{ url }</p>
					</div>
					<Button
						icon={ <Icon icon={ pages } /> }
						isSecondary
						ref={ ref }
					>
						{ copyText }
					</Button>
				</div>
				<div className="gdl-launch-site-success-modal__cta-container">
					<Button
						href={ url }
						isPrimary
						target="_blank"
					>
						{ __( 'View Site', 'godaddy-launch' ) }
					</Button>
				</div>
			</Modal>
			{ Confetti( true, true ) }
		</>
	);
};

export default LiveSiteLaunchSuccessModal;
