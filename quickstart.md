# Quick-start

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


