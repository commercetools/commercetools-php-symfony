# Quick-start

  * [Pre-requisites](#pre-requisites)
  * [Create a basic page](#create-a-basic-page)
  * [Install ExampleBundle](#examplebundle)
  * [Import sample data](#sample-data)
  * [Preview in browser](#preview-in-browser)

### Pre-requisites

* You have successfully completed the Installation step and verified that everything works fine.
* Optionally, you may want to install the Symfony's built-in Web Server if you don't want
to bother configuring a full-featured web server such as Nginx or Apache, by running
`composer require symfony/web-server-bundle --dev`. Note, that this is only recommended
for local (development) setups.
* You can also add the symfony debug bar that displays useful debuging and profiling information:
`composer require symfony/web-profiler-bundle --dev`



## Create a basic page

By following the next steps you will be able to create a simple page that shows a products list.
The products are being fetched from the commercetools platform. This is not intended to be
a Symfony Framework guide. It just intends to show how to use the SymfonyBundle together
with Symfony and it assumes you start on a new project.

* open file `config/routes.yaml` and uncomment the commented lines. The result should be
```yaml
index:
    path: /
    controller: App\Controller\DefaultController::indexAction
```

* enable CatalogBundle in file `config/bundles.php`
```php
    Commercetools\Symfony\CatalogBundle\CatalogBundle::class => ['all' => true],
```

* create class `DefaultController` in directory `src/Controller/` like

```php
<?php

namespace App\Controller;

use Commercetools\Symfony\CatalogBundle\Manager\CatalogManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    public function indexAction(CatalogManager $catalogManager)
    {
        list($products, $facets, $offset) = $catalogManager->searchProducts('en');

        return $this->render('index.html.twig', [
            'products' => $products
        ]);
    }
}
```
* create file `templates/index.html.twig`:

```twig
{% extends "base.html.twig" %}

{% block body %}
    <div class="container">
        <ul class="products">
            {% for product in products %}
                <li class="product">
                    <h3>{{ product.name }}</h3>
                    <img src="{{ product.masterVariant.images.current.url | default('') }}" width="100">
                    <p>{{ product.description }}</p>
                    <p>{{ product.masterVariant.prices.0.value | default('0') }}</p>
                </li>
            {% endfor %}
        </ul>
    </div>
{% endblock %}
```

## ExampleBundle

You may enable ExampleBundle to have a running instance of the Sunrise sample e-shop. It
resembles an e-shop demonstrating sample data, templates and functionalities.

* run `composer require symfony/asset symfony/translation`
* enable all commercetools bundles in `config/bundles.php`
```php
return [
...
    Commercetools\Symfony\CtpBundle\CtpBundle::class => ['all' => true],
    Commercetools\Symfony\ShoppingListBundle\ShoppingListBundle::class => ['all' => true],
    Commercetools\Symfony\CartBundle\CartBundle::class => ['all' => true],
    Commercetools\Symfony\CustomerBundle\CustomerBundle::class => ['all' => true],
    Commercetools\Symfony\ReviewBundle\ReviewBundle::class => ['all' => true],
    Commercetools\Symfony\CatalogBundle\CatalogBundle::class => ['all' => true],
    Commercetools\Symfony\ExampleBundle\ExampleBundle::class => ['all' => true],
    Commercetools\Symfony\SetupBundle\SetupBundle::class => ['all' => true],
    Commercetools\Symfony\StateBundle\StateBundle::class => ['all' => true],
...
];
```
* import the ExampleBundle routes in `config/routes.yaml`
```yaml
_example:
    resource: "@ExampleBundle/Resources/config/routing.yml"
```

* enable user/customer management and authentication to be managed by commercetools
by editing `congig/packages/security.yaml` as:
```yaml
security:
  providers:
    ctp:
      id: Commercetools\Symfony\CustomerBundle\Security\User\UserProvider
  access_control:
  - { path: /user/, roles: ROLE_USER }
  encoders:
    Symfony\Component\Security\Core\User\User: plaintext
    Commercetools\Symfony\CustomerBundle\Security\User\User: plaintext
  firewalls:
    main:
      anonymous: ~
      commercetools-login:
        login_path: login
        check_path: login_check
        default_target_path: _ctp_example_index
      logout:
        path:   logout
        target: _ctp_example_index
    dev:
      pattern: ^/(_(profiler|wdt)|css|images|js)/
      security: false
    default:
      anonymous: ~
```
* note: to disable errors on undefined twig variables change in `config/packages/twig.yaml`. This is
because right now there are undefined variables in the Sunrise templates. This setting should and it is
recommended to be back to it's original value for any real world scenario.
```yaml
twig:
    strict_variables: false
```

* recreate the translation messages by running:
`bin/console translation:update --dump-messages --force en ExampleBundle`

## Sample Data

* At this point no data is inserted in the shop which will result to your sample e-shop be
more or less empty and no sample products/categories/etc
will be demonstrated. To import the sample Sunrise data please follow the procedure
[here](https://github.com/commercetools/commercetools-sunrise-data/). You may run this importer
in a repository other than the one you currently work at, since you will not need any of it
after running it once on your project.



## Preview in browser

* run `php bin/console server:run`. This will start a local webserver listening at port 8000.
(If you are already running an instance in port `8000` it will automatically start in the
next available port and it will print the final decision in the screen)
* open a browser and visit `localhost:8000`. You should be able to view a list with the products
available in your commercetools project. Note, that if you didn't select to create some sample data when you
created your project in commercetools, you will have to manually add some products, otherwise
the list will be empty. If you, manually added some products and your locale was not `en`,
you should change the parameter `en` used in the `DefaultController` above, to match the
locale that you used in your products.
