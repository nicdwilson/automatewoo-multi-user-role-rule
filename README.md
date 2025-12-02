# AutomateWoo Multi User Role Rule

A WordPress plugin that adds a custom AutomateWoo rule to check if a customer's roles contain a specific role. Unlike the default AutomateWoo "Customer - User Role" rule which only checks the first role, this rule checks **all** roles assigned to a user.

## Description

This plugin extends AutomateWoo by adding a new rule called **"Customer - Roles Contains"** that properly handles users with multiple roles. This is particularly useful when users have multiple roles assigned via third-party plugins (such as membership plugins, role management plugins, etc.).

### The Problem

The default AutomateWoo "Customer - User Role" rule uses `$customer->get_role()`, which only returns the first role in a user's roles array. If a user has multiple roles (e.g., `['customer', 'premium_member', 'vip']`), the default rule will only check against `'customer'` and ignore the other roles.

### The Solution

This plugin adds a new rule that checks **all** roles in a user's roles array, ensuring that workflows can properly trigger for users with multiple roles.

## Installation

1. Upload the `automatewoo-multi-user-role-rule` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The new rule will automatically appear in AutomateWoo workflows

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- WooCommerce 3.0 or higher
- AutomateWoo plugin (active)

## Compatibility

- ✅ Compatible with High-Performance Order Storage (HPOS)
- ✅ Compatible with all AutomateWoo workflow types
- ✅ Works with all WordPress user roles
- ✅ Supports guest users

## Usage

After activation, the new rule **"Customer - Roles Contains"** will appear in the AutomateWoo workflow rules list under the **Customer** group.

### Adding the Rule to a Workflow

1. Go to **AutomateWoo > Workflows**
2. Create a new workflow or edit an existing one
3. In the **Rules** section, click **Add Rule**
4. Select **Customer - Roles Contains** from the dropdown
5. Choose the comparison type:
   - **is** - Customer has the selected role
   - **is not** - Customer does not have the selected role
6. Select the role to check for from the dropdown
7. Save the workflow

### Example Use Cases

- **VIP Member Workflows**: Trigger workflows for users who have the "VIP" role, even if they also have other roles
- **Multi-Membership Support**: Check for specific membership roles when users have multiple memberships
- **Role-Based Segmentation**: Create segments based on roles that may be secondary to a user's primary role

## How It Works

The rule checks all roles in a user's `$user->roles` array, not just the first one. This means:

- If a user has roles: `['customer', 'premium_member']`
- And you check for `'premium_member'`
- The rule will return `true` (whereas the default rule would return `false`)

### Technical Implementation

1. **Value Normalization**: AutomateWoo may pass the role value as an array even for single-select fields. The rule automatically normalizes this to handle both string and array values.

2. **Customer Retrieval**: The rule includes fallback logic to retrieve the customer object:
   - First, uses the customer passed directly to the validate method
   - If not available, retrieves from the workflow's data layer
   - If still not found, attempts to get the customer from the order in the data layer

3. **Debug Logging**: All validation steps are logged to WooCommerce logs for troubleshooting. Check `WooCommerce > Status > Logs` for files starting with `automatewoo-multi-user-role-rule`.

## Differences from Default Rule

| Feature | Default "Customer - User Role" | This Plugin "Customer - Roles Contains" |
|---------|-------------------------------|------------------------------------------|
| Checks first role only | ✅ | ❌ |
| Checks all roles | ❌ | ✅ |
| Works with multiple roles | ❌ | ✅ |
| Guest user support | ✅ | ✅ |

## Technical Details

- **Rule ID**: `customer_roles_contains`
- **Rule Type**: Select (Preloaded)
- **Data Item**: Customer
- **Comparison Types**: `is`, `is_not`
- **Namespace**: `AutomateWoo_Multi_User_Role_Rule`
- **Debugging**: Comprehensive logging via WooCommerce logger (check `wp-content/uploads/wc-logs/automatewoo-multi-user-role-rule-*.log`)

## Features

- ✅ Checks **all** user roles, not just the first one
- ✅ Handles array values from AutomateWoo (normalizes automatically)
- ✅ Fallback logic to retrieve customer from order when needed
- ✅ Comprehensive debug logging for troubleshooting
- ✅ Works with manually triggered workflows
- ✅ Supports guest users

## Changelog

### 1.0.0
- Initial release
- Added "Customer - Roles Contains" rule
- HPOS compatibility declaration
- Support for all WordPress user roles
- Guest user support
- Array value normalization
- Customer retrieval fallback from data layer and orders
- Comprehensive debug logging via WooCommerce logger

## Support

For issues, feature requests, or contributions, please visit the [GitHub repository](https://github.com/your-repo/automatewoo-multi-user-role-rule).

## License

GPL v2 or later

## Credits

Developed by WooCommerce Growth Team

## Related Documentation

- [AutomateWoo Custom Rules Documentation](https://woocommerce.com/document/automatewoo/rules/custom-rules/)
- [AutomateWoo Documentation](https://woocommerce.com/document/automatewoo/)

