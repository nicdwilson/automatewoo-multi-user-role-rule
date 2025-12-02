<?php
/**
 * Custom AutomateWoo Rule: Customer - Roles Contains
 *
 * This rule checks if a customer's roles array contains a specific role.
 * Unlike the default Customer_Role rule which only checks the first role,
 * this rule checks ALL roles assigned to the user, making it useful for
 * users with multiple roles assigned via third-party plugins.
 *
 * @package AutomateWoo_Multi_User_Role_Rule
 */

namespace AutomateWoo_Multi_User_Role_Rule;

use AutomateWoo\Rules\Preloaded_Select_Rule_Abstract;
use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Rule_Customer_Roles_Contains
 *
 * @extends Preloaded_Select_Rule_Abstract
 */
class Rule_Customer_Roles_Contains extends Preloaded_Select_Rule_Abstract {

	/**
	 * Specifies the data type used by this rule.
	 *
	 * @var string
	 */
	public $data_item = DataTypes::CUSTOMER;

	/**
	 * Initialize the rule.
	 */
	public function init() {
		parent::init();

		$this->title = __( 'Customer - Roles Contains', 'automatewoo-multi-user-role-rule' );
		$this->group = __( 'Customer', 'automatewoo' );
	}

	/**
	 * Load select choices for the rule.
	 * Returns all available WordPress user roles plus a "Guest" option.
	 *
	 * @return array Array of role slugs => role names.
	 */
	public function load_select_choices() {
		global $wp_roles;
		$choices = [];

		if ( isset( $wp_roles->roles ) && is_array( $wp_roles->roles ) ) {
			foreach ( $wp_roles->roles as $key => $role ) {
				$choices[ $key ] = $role['name'];
			}
		}

		$choices['guest'] = __( 'Guest', 'automatewoo' );

		return $choices;
	}

	/**
	 * Validate the rule based on options set by a workflow.
	 *
	 * This method checks if the customer's roles array contains the selected role.
	 * Unlike the default Customer_Role rule which only checks the first role,
	 * this checks ALL roles assigned to the user.
	 *
	 * @param \AutomateWoo\Customer $customer The customer object.
	 * @param string                $compare  The comparison type (is/is_not).
	 * @param string                $value    The role to check for.
	 * @return bool True if the rule matches, false otherwise.
	 */
	public function validate( $customer, $compare, $value ) {
		$customer_roles = [];

		if ( $customer->is_registered() ) {
			$user = $customer->get_user();
			if ( $user && isset( $user->roles ) && is_array( $user->roles ) ) {
				// Get ALL roles, not just the first one.
				$customer_roles = $user->roles;
			}
		} else {
			// Guest users have no roles, but we'll handle 'guest' as a special case.
			$customer_roles = [];
		}

		// Check if the selected role is in the customer's roles array.
		$has_role = in_array( $value, $customer_roles, true );

		// Handle guest check separately.
		if ( 'guest' === $value && ! $customer->is_registered() ) {
			$has_role = true;
		}

		// Perform the comparison.
		switch ( $compare ) {
			case 'is':
				return $has_role;

			case 'is_not':
				return ! $has_role;

			default:
				return false;
		}
	}
}

