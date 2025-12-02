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
	 * @param \AutomateWoo\Customer|false $customer The customer object, or false if not available.
	 * @param string                     $compare  The comparison type (is/is_not).
	 * @param string                     $value    The role to check for.
	 * @return bool True if the rule matches, false otherwise.
	 */
	public function validate( $customer, $compare, $value ) {
		$logger = wc_get_logger();
		$context = [ 'source' => 'automatewoo-multi-user-role-rule' ];

		// Normalize value - AutomateWoo may pass it as an array even for single-select fields.
		$roles_to_check = [];
		if ( is_array( $value ) ) {
			$roles_to_check = array_filter( $value ); // Remove empty values
			$logger->debug(
				sprintf(
					'[Customer Roles Contains] Value is array with %d items: %s',
					count( $roles_to_check ),
					implode( ', ', $roles_to_check )
				),
				$context
			);
		} else {
			$roles_to_check = [ $value ];
			$logger->debug(
				sprintf(
					'[Customer Roles Contains] Value is string: %s',
					$value
				),
				$context
			);
		}

		// Log initial parameters.
		$logger->debug(
			sprintf(
				'[Customer Roles Contains] Validate called - Compare: %s, Roles to check: %s, Initial Customer: %s',
				$compare,
				implode( ', ', $roles_to_check ),
				$customer ? ( is_a( $customer, 'AutomateWoo\Customer' ) ? 'Valid Customer Object' : gettype( $customer ) ) : 'false/null'
			),
			$context
		);

		// If customer is not directly available, try to get it from the data layer.
		if ( ! $customer || ! is_a( $customer, 'AutomateWoo\Customer' ) ) {
			$logger->debug( '[Customer Roles Contains] Initial customer invalid, attempting to get from data layer', $context );

			$data_layer = $this->data_layer();
			if ( $data_layer ) {
				$logger->debug( '[Customer Roles Contains] Data layer retrieved successfully', $context );

				$customer = $data_layer->get_customer();
				$logger->debug(
					sprintf(
						'[Customer Roles Contains] Customer from data layer: %s',
						$customer ? ( is_a( $customer, 'AutomateWoo\Customer' ) ? 'Valid Customer Object' : gettype( $customer ) ) : 'false/null'
					),
					$context
				);

				// If still no customer, try to get from order.
				if ( ! $customer || ! is_a( $customer, 'AutomateWoo\Customer' ) ) {
					$logger->debug( '[Customer Roles Contains] Customer not found in data layer, attempting to get from order', $context );

					$order = $data_layer->get_order();
					$logger->debug(
						sprintf(
							'[Customer Roles Contains] Order from data layer: %s',
							$order ? ( is_a( $order, 'WC_Order' ) ? 'Order #' . $order->get_id() : gettype( $order ) ) : 'false/null'
						),
						$context
					);
					
					if ( $order && is_a( $order, 'WC_Order' ) ) {
						$customer = \AutomateWoo\Customer_Factory::get_by_order( $order );
						$logger->debug(
							sprintf(
								'[Customer Roles Contains] Customer from order factory: %s',
								$customer ? ( is_a( $customer, 'AutomateWoo\Customer' ) ? 'Valid Customer Object' : gettype( $customer ) ) : 'false/null'
							),
							$context
						);
					}
				}
			} else {
				$logger->debug( '[Customer Roles Contains] Data layer is null/false', $context );
			}
		}

		// If we still don't have a valid customer, return false.
		if ( ! $customer || ! is_a( $customer, 'AutomateWoo\Customer' ) ) {
			$logger->debug( '[Customer Roles Contains] No valid customer found after all attempts', $context );

			// For guest checks, if there's no customer, they are a guest.
			if ( in_array( 'guest', $roles_to_check, true ) ) {
				$result = 'is' === $compare;
				$logger->debug(
					sprintf(
						'[Customer Roles Contains] Guest check - Returning: %s',
						$result ? 'true' : 'false'
					),
					$context
				);
				return $result;
			}

			$logger->debug( '[Customer Roles Contains] No customer and not guest check - Returning: false', $context );
			return false;
		}

		// Log customer details.
		$customer_id = $customer->get_id();
		$customer_email = $customer->get_email();
		$is_registered = $customer->is_registered();
		$logger->debug(
			sprintf(
				'[Customer Roles Contains] Valid customer found - ID: %s, Email: %s, Registered: %s',
				$customer_id ? $customer_id : 'N/A',
				$customer_email ? $customer_email : 'N/A',
				$is_registered ? 'yes' : 'no'
			),
			$context
		);

		$customer_roles = [];

		if ( $customer->is_registered() ) {
			$user = $customer->get_user();
			$logger->debug(
				sprintf(
					'[Customer Roles Contains] User object: %s',
					$user ? ( is_a( $user, 'WP_User' ) ? 'User ID: ' . $user->ID : gettype( $user ) ) : 'false/null'
				),
				$context
			);

			if ( $user && isset( $user->roles ) && is_array( $user->roles ) ) {
				// Get ALL roles, not just the first one.
				$customer_roles = $user->roles;
				$logger->debug(
					sprintf(
						'[Customer Roles Contains] User roles found: %s',
						implode( ', ', $customer_roles )
					),
					$context
				);
			} else {
				$logger->debug(
					sprintf(
						'[Customer Roles Contains] User roles not found or invalid - User exists: %s, Has roles property: %s, Roles is array: %s',
						$user ? 'yes' : 'no',
						$user && isset( $user->roles ) ? 'yes' : 'no',
						$user && isset( $user->roles ) && is_array( $user->roles ) ? 'yes' : 'no'
					),
					$context
				);
			}
		} else {
			// Guest users have no roles, but we'll handle 'guest' as a special case.
			$customer_roles = [];
			$logger->debug( '[Customer Roles Contains] Customer is not registered (guest)', $context );
		}

		// Check if any of the selected roles are in the customer's roles array.
		$has_role = false;
		foreach ( $roles_to_check as $role_to_check ) {
			if ( in_array( $role_to_check, $customer_roles, true ) ) {
				$has_role = true;
				$logger->debug(
					sprintf(
						'[Customer Roles Contains] Role match found - Role: %s',
						$role_to_check
					),
					$context
				);
				break; // Found a match, no need to check further
			}
		}

		// Handle guest check separately.
		if ( ! $has_role && in_array( 'guest', $roles_to_check, true ) && ! $customer->is_registered() ) {
			$has_role = true;
			$logger->debug( '[Customer Roles Contains] Guest role match - Setting has_role to true', $context );
		}

		$logger->debug(
			sprintf(
				'[Customer Roles Contains] Role check - Looking for: %s, Customer roles: %s, Has role: %s',
				implode( ', ', $roles_to_check ),
				implode( ', ', $customer_roles ),
				$has_role ? 'yes' : 'no'
			),
			$context
		);

		// Perform the comparison.
		$result = false;
		switch ( $compare ) {
			case 'is':
				$result = $has_role;
				break;

			case 'is_not':
				$result = ! $has_role;
				break;

			default:
				$logger->debug(
					sprintf(
						'[Customer Roles Contains] Unknown compare type: %s - Returning: false',
						$compare
					),
					$context
				);
				return false;
		}

		$logger->debug(
			sprintf(
				'[Customer Roles Contains] Final result - Compare: %s, Has role: %s, Result: %s',
				$compare,
				$has_role ? 'yes' : 'no',
				$result ? 'true' : 'false'
			),
			$context
		);

		return $result;
	}
}

