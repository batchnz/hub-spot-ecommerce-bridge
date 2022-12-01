# HubSpot Commerce plugin for Craft CMS 4.x

Sync data between Craft Commerce and HubSpot using the HubSpot Ecommerce Bridge

## Requirements

This plugin requires Craft CMS 4.0.0 or later as well as Craft Commerce 4.0.0 or later.

It is also required to have a Hubspot account and the ability to create private apps for it.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require batchnz/hub-spot-ecommerce-bridge

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for HubSpot Commerce.

## HubSpot Commerce Overview

Hubspot Commerce automatically syncs all commerce related data from Craft to Hubspot.
The models that are synced are:

| Craft Model | Hubspot Model |
|-------------|---------------|
| Order       | Deal          |
| User        | Contact       |
| Product     | Product       |

## Configuring HubSpot Commerce

### Private App
1. Create a private app within Hubspot and make sure to give it the following Scopes:
   1. e-commerce
   2. crm.objects.contacts.read
   3. crm.objects.contacts.write
   4. crm.schemas.custom.read
   5. crm.schemas.contacts.read
   6. crm.objects.deals.read
   7. crm.objects.deals.write
   8. crm.schemas.contacts.write
   9. crm.schemas.deals.read
   10. crm.schemas.deals.write
   11. crm.objects.line_items.read
   12. crm.objects.line_items.write
   13. crm.schemas.line_items.read
2. Navigate to ```Hubspot Commerce -> Settings``` within the cp and enter the Access token for the private app you created
and click Save. **Note:** 
You need to have admin permission to access this setting section. It is also recommended to store this as
an ENV variable.

### Mappings
You can configure which Hubspot properties the Craft Commerce fields will map to. Head to ```Hubspot Commerce -> Mappings```
within the cp and configure which Hubspot properties each of the Craft Commerce fields will map to.

**IMPORTANT:** The properties you configure for mappings MUST EXIST IN HUBSPOT for the given object type (Order, Product etc).

### Addresses
Craft 4 changes the way addresses work for Users.

If you would like customer address information to be synced to Hubspot, make sure you follow the
[Commerce Upgrade Guide](https://craftcms.com/docs/commerce/4.x/upgrading.html#performing-the-upgrade)
and the [Craft Setup Guide](https://craftcms.com/docs/4.x/addresses.html#setup-pro) and have all
address related configuration configured on your site.

## Using HubSpot Commerce
The sync will happen automatically.

Synced item can be monitored from the Queue in Craft.

You are also able to manually sync data of a given type within a given date range. You can do this by
heading to ```Hubspot Commerce -> Manual Sync``` from within the control panel and run any of the available syncs.
