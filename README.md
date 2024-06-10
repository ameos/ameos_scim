# SCIM Extension for TYPO3

## Overview

This TYPO3 extension provides an implementation of the System for Cross-domain Identity Management (SCIM) standard. SCIM is designed to simplify user management in cloud-based applications and services by providing a consistent way to manage user identities across various platforms.

## Features

 * User Provisioning: Automate user account creation, updates, and deletion.
 * Group Management: Manage user groups and memberships.
 * Schema Compliance: Fully compliant with SCIM 2.0 specifications.
 * Extensible: Easily extend and customize to fit specific needs.
 * Secure: Implements robust security measures to protect user data.

## Requirements

TYPO3 v12 or higher
PHP 8.0 or higher

## Installation

### Using Composer

Add the extension to your TYPO3 project:

```
composer require ameos/scim
```

### Manual Installation

Download the extension.

Activate the extension in the TYPO3 Extension Manager.

## Configuration

### Add access

Create a scim access record on the root page and copy the secret.
This secret can be used as Bearer in your provider configuration.

### TypoScript Setup

Navigate to the TYPO3 Backend and go to the Template module.

Select your root page and click Edit the whole template record.

Add the static template SCIM Configuration.

Edit the plugin.tx_scim.pid constant with the ID of the storage folder containing frontend users.

### Extension Settings

You can activate or deactivate SCIM for backend or frontend and configure the api path in the extension settings. 

### Mapping configuration

The extension comes with a preconfigured mapping

You can modify this by indicating a path to a Yaml file in your sitepackage ext_localconf.php files with :

```
Configuration::registerConfiguration('EXT:sitepackage/Configuration/Scim/Configuration.yaml');
```

See default mapping in Configuration/Mapping/Configuration.yaml

## Usage

### API Endpoints

User Endpoints:

```
GET /Users: Retrieve all users.
GET /Users/{id}: Retrieve a specific user by ID.
POST /Users: Create a new user.
PUT /Users/{id}: Replace an existing user.
PATCH /Users/{id}: Update an existing user.
DELETE /Users/{id}: Delete a user.
```

Group Endpoints:
```
GET /Groups: Retrieve all groups.
GET /Groups/{id}: Retrieve a specific group by ID.
POST /Groups: Create a new group.
PUT /Groups/{id}: Replace an existing group.
PATCH /Groups/{id}: Update an existing group.
DELETE /Groups/{id}: Delete a group.
```

Other Endpoints:
```
POST /Bulk: Bulk operations
```

### Example Requests

Create a User

```
curl -X POST https://example.com/scim/v2/Users \
-H "Authorization: Bearer your_api_key_here" \
-H "Content-Type: application/json" \
-d '{
  "userName": "jdoe",
  "name": {
    "givenName": "John",
    "familyName": "Doe"
  },
  "emails": [
    {
      "value": "jdoe@example.com",
      "primary": true
    }
  ]
}'
```

Retrieve a User

```
curl -X GET https://example.com/scim/v2/Users/{id} \
-H "Authorization: Bearer your_api_key_here"
```
## Logging

The logging is connected to the scim channel

See the TYPO3 configuration for configuration: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Logging/Index.html

## Extending the Extension

To extend the functionality of this extension, you can:

 * Use event.
 * Extend the mapping configuration.
 * Xclass the extension classes.

For detailed information, refer to the TYPO3 Extension Development Documentation.

## Support

For support, please open an issue on the GitHub repository or contact us at typo3dev@ameos.com.

## Contributing

We welcome contributions to this project. Please follow these steps to contribute:

 * Fork the repository on GitHub.
 * Create a feature branch (git checkout -b feature * your-feature).
 * Commit your changes (git commit -am 'Add your feature').
 * Push to the branch (git push origin feature/your-feature).
 * Create a new Pull Request.

## License

This project is licensed under the MIT License. See the LICENSE file for more details.

Thank you for using our SCIM extension for TYPO3. We hope it simplifies your user management and improves your productivity.
