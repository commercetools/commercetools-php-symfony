# commercetools Symfony bundle

The commercetools Symfony Bundle is a collection of Symfony bundles that ease the use of the
[commercetools PHP-SDK](https://github.com/commercetools/commercetools-php-sdk) when implementing
a Symfony project.

##### Table of contents

  * [Requirements](#requirements)
  * [Installation](#installation)
    + [Verify everything works](#verify-everything-works)
  * [Usage](#usage)
    + [Usage of available services](#usage-of-available-services)
      - [Available services](#available-services)
    + [Console Commands](#console-commands)
      - [Available console commands](#available-console-commands)
    + [Usage of SDK Models in templates](#usage-of-sdk-models-in-templates)
  * [Quick-start](#quick-start)
  * [Disclaimer for ExampleBundle (dev)](#disclaimer-for-examplebundle--dev-)
  * [Testing](#testing)
  * [Issues](#issues)
  * [Contribute](#contribute)
  * [License](#license)


## Pre-requisites
Composer :
To install the composer go to https://getcomposer.org/doc/00-intro.md

## Installation
You can either create a new Symfony project or use an existing Symfony project (ver. 3.4 and above)

Create a new Symfony project using the following command: 

```sh
composer create-project symfony/skeleton <project-name>
```

Next, navigate to the project's directory and run the following command: 

```sh
composer config extra.symfony.allow-contrib true
```
This automates most of the configuration using the `recipes-contrib` bundle from Symfony Flex.

Next, install the commercetools Symfony bundle. To do this, open the **composer.json** file and add `"commercetools/symfony-bundle"` to the `require` attribute.
`require` the package `"commercetools/symfony-bundle"`
and run `composer install` or directly run the following on the command line
```sh
composer require commercetools/symfony-bundle
```

Open the`.env` file or create a `.env.local` file on root directory and edit the following 
lines to add your credentials. You can retrieve your API client credentials from the Merchant Center under
`Commercetools Merchant Center > Settings > Developer Settings`, when you create a new 
API Client. Note that for security reasons you cannot retrieve the `CTP_CLIENT_SECRET` 
for clients created in the past.

```dotenv
CTP_CLIENT_ID=<your client id>
CTP_CLIENT_SECRET=<your client secret>
CTP_PROJECT_KEY=<your project id>
CTP_AUTH_URL=https://auth.commercetools.com or https://auth.commercetools.co
CTP_API_URL=https://api.commercetools.com or https://api.commercetools.co
CTP_SCOPES=<your desired scopes>
```

For more information about using `.env` and `.env.local`, see
[here](https://symfony.com/doc/current/configuration/dot-env-changes.html) and
[here](https://symfony.com/blog/new-in-symfony-4-2-define-env-vars-per-environment)

### Verify configuration

To verify that your configuration works, after adding your client credentials on the `.env` file
as explained above, you may run on the command line:
If everything is set up correctly, this should return
the details of your project. For example:

```
Project's key: super-cool-project-4
Project's name: my eshop at commercetools
Countries: DE, US
Currencies: EUR
Languages: en
Created at: 2019-02-04T11:28:49+00:00
Messages: disabled
```


## Usage

The commercetools Symfony bundle consists of 8 smaller bundles (The dependencies mentioned are
only the additional ones, required for bundle-specific functionalities)

- CartBundle
- CatalogBundle
- CtpBundle
- CustomerBundle
    - Dependency: `symfony/security-bundle`
- ReviewBundle
- SetupBundle
    - Dependency: `symfony/console`
- ShoppingListBundle
- StateBundle
    - Dependency: `symfony/console`, `symfony/workflow`, `twig/extensions`
    

By default, `CtpBundle` & `CustomerBundle` are enabled for all environments.
additionally `SetupBundle` & `StateBundle` only for dev environment. To overview, 
enable or disable them, edit the `config/bundles.php` file.


### Available services

In every bundle there are services that can be autowired directly in your application. For example
to autowire the `CatalogManager` service in a controller action, do the following: 

```php
<?php
namespace App\Controller;

use Commercetools\Symfony\CatalogBundle\Manager\CatalogManager;
use Symfony\Component\HttpFoundation\Response;

class MyController
{
    public function index(CatalogManager $manager)
    {
        /** @var CategoryCollection $categories */
        $categories = $manager->getCategories('en');

        return new Response(
            '<html><body>categories: '
            // handle CategoryCollection $categories
            .'</body></html>'
        );
    }
}
```

The main idea behind is that in each Bundle there are some reusable services that you
may inject directly in your app. As a generic pattern there are a 
couple of `*Manager` services, that provide related actions. So, for example in `CartManager`
you will find helpers like `getCart`, `createCartForUser` and `update` which returns a 
`CartUpdateBuilder` where you can dynamically set your update actions.

#### Available services

- CartBundle
    - CartManager
    - OrderManager
    - PaymentManager
    - ShippingMethodManager
- CatalogBundle
    - CatalogManager
    - Search
- CtpBundle
    - ClientFactory
    - ContextFactory
    - MapperFactory
    - LocaleConverter
    - CustomTypeProvider
- CustomerBundle
    - CustomerManager
    - AuthenticationProvider
    - UserProvider
- ReviewBundle
    - ReviewManager
- ShoppingListBundle
    - ShoppingListManager
- StateBundle
    - CtpMarkingStoreLineItemState
    - CtpMarkingStoreOrderState
    - CtpMarkingStorePaymentState
    - CtpMarkingStoreProductState
    - CtpMarkingStoreReviewState
    
### Console Commands

To use the console commands navigate to your project's directory and run

```sh
bin/console commercetools:<command-name>
```

#### Available console commands

- SetupBundle
    - `project-info` // Fetches and displays information from commercetoools platform
    about the configured project
    - `project-apply-configuration` // Save the local project configuration that resides under
    `commercetools` key on the commercetools platform
    - `create-custom-type` // Interactive CLI to create CustomTypes on your project
    - `sync-custom-types-from-server` // Saves the CustomTypes currently present on the project
    in `<PROJECT_DIR>/config/packages/<ENV>/custom_types.yaml`
    - `sync-custom-types-from-local` // Saves or updates the CustomTypes present in 
    `<PROJECT_DIR>/config/packages/<ENV>/custom_types.yaml` on your commercetools project
    
- StateBundle
    - `set-state-machine-config` // Fetches States from commercetools platform and creates a 
    Symfony `state_machine` type configuration file at 
    `<PROJECT_DIR>/config/packages/<ENV>/workflow.yaml`
    - `set-workflow-config` // Fetches States from commercetools platform and creates a Symfony
    `workflow` type configuration file at `<PROJECT_DIR>/config/packages/<ENV>/workflow.yaml`
    
      More info on working with Symfony Workflows can be found in Symfony's [documentation](https://symfony.com/doc/current/workflow/usage.html):

### Usage of SDK Models in templates

Get variables on templates using only variable name:
```
{{ product.masterVariant.attributes.test.value }}
{{ attribute(product.masterVariant.attributes, 'custom-attribute').value }}
```

## Quick-start

For a more detailed quick start guide you can continue [here](quickstart.md)

## Disclaimer for ExampleBundle (dev)

There is an ExampleBundle provided in the repository which tries to demonstrate a sample
eshop implementation making use of the other Bundles provided. This ExampleBundle is currently 
under development. It may includes outdated code, is not properly tested and right now
is not encouraged to be used in any production environment. For now, we don't guarantee any
non breaking changes. We intend to fix the problems, improve it and create a more helpful 
generic example.

## Testing

Clone the project and navigate on the project's directory. On the command line, run
```sh
composer install --dev
./vendor/bin/phpunit
```

## Issues

Check current [issues](https://github.com/commercetools/commercetools-php-symfony/issues/) 
or [open](https://github.com/commercetools/commercetools-php-symfony/issues/new)
a new one

## Contribute

[Contribute](CONTRIBUTING.md)

## License
This bundle is under the MIT license. See the complete license in the bundle:
[MIT License](LICENSE)
