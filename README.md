# Symfony bundle installation guide

## Requirements
Composer :
To install the composer go to https://getcomposer.org/doc/00-intro.md

## Installation
Create a new symfony project 
```sh
composer create-project symfony/skeleton
```
or use an existing one (symfony >= 3.4)

Navigate to the project's directory and run
```sh
composer config extra.symfony.allow-contrib true
```
This will allow symfony to use the recipes (via Symfony Flex) that will automate most of the 
configuration.

To install the commercetools symfony-bundle open composer.json and add to the attribute `require` 
the package `"commercetools/symfony-bundle"`
and run `composer install` or directly run the following on the command line
```sh
composer require commercetools/symfony-bundle
```

Open `.env.local` file from root directory and edit the following lines to add your credentials. 
The credentials can be retrieved through 
`Commercetools Merchant Center > Settings > Developer Settings`, while you create a new API Client. 
Note that for security reasons you cannot retrieve the `CTP_CLIENT_SECRET` for clients created in
the past.

```dotenv
CTP_CLIENT_ID=<your client id>
CTP_CLIENT_SECRET=<your client secret>
CTP_PROJECT_KEY=<your project id>
CTP_AUTH_URL=https://auth.commercetools.com or https://auth.commercetools.co
CTP_API_URL=https://api.commercetools.com or https://api.commercetools.co
CTP_SCOPES=<your desired scopes>
```

## Usage

commercetools symfony bundle consists of 8 smaller bundles

- CartBundle
- CatalogBundle
- CtpBundle
- CustomerBundle
- ReviewBundle
- SetupBundle
- ShoppingListBundle
- StateBundle

### Usage of 

### Usage of available services 

In every bundle there are services that can be autowired directly in your application. For example
to autowire `CatalogManager` in a controller action: 

```php
<?php
namespace App\Controller;

use Commercetools\Symfony\CatalogBundle\Manager\CatalogManager;
use Symfony\Component\HttpFoundation\Response;

class MyController
{
    public function index(CatalogManager $manager)
    {
        $categories = $manager->getCategories('en');

        return new Response(
            '<html><body>categories: '.json_encode($categories).'</body></html>'
        );
    }
}
```

#### Available services

- CartBundle
    - CartManager
    - OrderManager
    - PaymentManager
    - ShippingMethodManager
- CatalogBundle
    - CatalogManager
- CtpBundle
    - ClientFactory
- CustomerBundle
    - CustomerManager
    - AuthenticationProvider
    - UserProvider
- ReviewBundle
    - ReviewManager
- SetupBundle
    - ...
- ShoppingListBundle
    - ShoppingListManager
- StateBundle
    - ...

### Usage of SDK Models in templates

Get variables on templates using only variable name:
```
{{ product.masterVariant.attributes.test.value }}
{{ attribute(product.masterVariant.attributes, 'custom-attribute').value }}
```

## Testing

Clone the project and navigate on the project's directory. On the command line, run
```sh
composer install
./vendor/bin/phpunit
```
