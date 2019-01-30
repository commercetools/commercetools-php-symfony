# Symfony bundle installation guide

## Requirements
Composer :
To install the composer go to https://getcomposer.org/doc/00-intro.md

## Installation
Create a new or navigate to a symfony (>= 3.4) project directory

Navigate to the project's directory and run
```sh
composer config extra.symfony.allow-contrib true
```
This will allow symfony to use the recipes (via Symfony Flex) that will automate most of the 
configuration.

To install the base CTP package open composer.json and add to the attribute `require` this line
```json
"commercetools/symfony-ctpbundle"
```
or run the following on the command line
```sh
composer require commercetools/symfony-ctpbundle
```


Open .env and edit these lines to add your credentials. The credentials can be retrieved through 
Commercetools Merchant Center > Settings > Developer Settings

```dotenv
CTP_CLIENT_ID=<your client id>
CTP_CLIENT_SECRET=<your client secret>
CTP_PROJECT_KEY=<your project id>
CTP_AUTH_URL=https://auth.commercetools.com or https://auth.commercetools.co
CTP_API_URL=https://api.commercetools.com or https://api.commercetools.co
CTP_SCOPES=<your desired scopes>
```


### Usage of SDK Models in templates

Get variables on templates using only variable name:
```
{{ product.masterVariant.attributes.test.value }}
{{ attribute(product.masterVariant.attributes, 'custom-attribute').value }}
```
