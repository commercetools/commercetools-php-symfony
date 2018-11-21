# Symfony Commercetools CtpBundle

## Requirements
Symfony 3:
To install symfony go to http://symfony.com/doc/current/book/installation.html

Composer :
To install the composer go to https://getcomposer.org/doc/00-intro.md

## Installation
Create a new or open a symfony3 project and open it in any editor of choice

Open composer.json and add to the attribute require this line

```sh
composer require commercetools/symfony-bundle
```

Add the Commercetools Bundle to your application kernel

```php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...
            new Commercetools\Symfony\CtpBundle\CtpBundle(),
            // ...
        ];
        // ...
    }
    // ...
```

Open App/config/parameters.yml.dist and add these lines
```yaml
    commercetools.client_id: ~
    commercetools.client_secret: ~
    commercetools.project: ~
```

Open App/config/config.yml and these lines on the bottom of the document
```yaml
commercetools:
  credentials:
    client_id: "%commercetools.client_id%"
    client_secret: "%commercetools.client_secret%"
    project: "%commercetools.project%"
```


### Usage of SDK Models in templates

Get variables on templates using only variable name:
```
{{ product.masterVariant.attributes.test.value }}
{{ attribute(product.masterVariant.attributes, 'custom-attribute').value }}
```
