/** global wp */
/**
 * WordPress dependencies
 */
import { cloneElement, isValidElement } from '@wordpress/element';

const isInEditor = window.location.pathname.includes( 'post-new' ) || window.location.pathname.includes( 'post.php' );

export const EID_PREFIX = 'wp.' + ( isInEditor ? 'editor' : 'wpadmin' );

/**
 * Return an element wrapped with Data-EID properties.
 *
 * @param    {Object} props    Should use all referenced properties in constructing the wrapper.
 * @property {Object} children `children` React component children
 * @property {string} section  `section` Reflects section within the interface
 *                             e.g., `guide/item/add_domain`, `launch/modal/finish/choices`.
 * @property {string} target   `target` Reflects interacted element and its role description.
 *                             e.g., `yes`, `no`, `panel`, `launch_later`, `launch_now`, `edit`, `skip`.
 * @property {string} action   `action` Reflects user action
 *                             e.g., `click`.
 */
export const EidWrapper = ( props ) => {
	const { children, section, target, action } = props;
	if ( ! isValidElement( children ) ) {
		return children;
	}

	return cloneElement( children, {
		...children.props,
		'data-eid': `${ EID_PREFIX }.${ section }.${ target }.${ action }`,
	} );
};
