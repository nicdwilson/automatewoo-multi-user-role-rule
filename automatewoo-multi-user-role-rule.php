<?php
/**
 * Plugin Name: AutomateWoo Multi User Role Rule
 * Plugin URI: https://github.com/your-repo/automatewoo-multi-user-role-rule
 * Description: Adds a custom AutomateWoo rule "Customer - Roles Contains" that checks all user roles, not just the first one. This is useful for users with multiple roles assigned via third-party plugins.
 * Version: 1.0.0
 * Author: WooCommerce Growth Team
 * Author URI: https://woocommerce.com
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: automatewoo-multi-user-role-rule
 *
 * @package AutomateWoo_Multi_User_Role_Rule
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Declare HPOS (High-Performance Order Storage / Custom Order Tables) compatibility.
 *
 * @since 1.0.0
 */
function automatewoo_multi_user_role_rule_declare_hpos_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'automatewoo_multi_user_role_rule_declare_hpos_compatibility' );

/**
 * Register the custom AutomateWoo rule
 *
 * @param array $rules Existing rules array.
 * @return array Modified rules array with our custom rule.
 */
function automatewoo_multi_user_role_rule_register( $rules ) {
	// Only register if AutomateWoo is active.
	if ( ! class_exists( 'AutomateWoo\Rules\Preloaded_Select_Rule_Abstract' ) ) {
		return $rules;
	}

	// Include the rule class file.
	require_once plugin_dir_path( __FILE__ ) . 'includes/rule-customer-roles-contains.php';

	// Register the rule with a unique ID.
	$rules['customer_roles_contains'] = 'AutomateWoo_Multi_User_Role_Rule\Rule_Customer_Roles_Contains';

	return $rules;
}
add_filter( 'automatewoo/rules/includes', 'automatewoo_multi_user_role_rule_register' );

