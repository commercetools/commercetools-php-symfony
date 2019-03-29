## Unreleased

### Changed

* **CatalogBundle**: Uri is optional in product search
* **ExampleBundle**: jQuery is fetched from CDN

### Fixed

* **Logger**: remove return void value
* **CustomerBundle**: change password returns CustomerSignInResult
* **CartBundle**: getShippingMethodsByCart returns Collection


<a name="0.4.7"></a>
## [0.4.7] - (2019-03-22)

### Changed

* **CtpBundle**: Add default values in Configuration class
* **CtpBundle**: Move custom types configuration to CtpBundle

### Fixed

* **ExampleBundle**: use namespace for Twig Environment
* **StateBundle**: add exception handling for State cache warmer


<a name="0.4.0"></a>
## [0.4.0] - (2019-03-22)

### Changed

We rewrote the project into different structure. Backward campatibility is broken at this point.

### Features

* CartBundle
* CatalogBundle
* CtpBundle
* CustomerBundle
* ReviewBundle
* SetupBundle
* ShoppingListBundle
* StateBundle
* ExampleBundle


<a name="0.2.1"></a>
## [0.2.1](https://github.com/commercetools/commercetools-php-symfony/compare/0.2.0...v0.2.1) (2016-12-06)


### Bug Fixes

* **Search:** fix uninitialized facet configuration ([8ba352e](https://github.com/commercetools/commercetools-php-symfony/commit/8ba352e))


<a name="0.2.0"></a>
# [0.2.0](https://github.com/commercetools/commercetools-php-symfony/compare/0.1.0...v0.2.0) (2016-12-06)


### Bug Fixes

* **Example:** fix example catalog controller ([e9eab18](https://github.com/commercetools/commercetools-php-symfony/commit/e9eab18))
* **Example:** fix product details with missing images ([aafb491](https://github.com/commercetools/commercetools-php-symfony/commit/aafb491))
* **Repository:** fix mapping ([40eb4e1](https://github.com/commercetools/commercetools-php-symfony/commit/40eb4e1))
* **Repository:** fix mapping ([92e8ebe](https://github.com/commercetools/commercetools-php-symfony/commit/92e8ebe))

### Features

* **Client:** add profiler middleware ([9b58ec2](https://github.com/commercetools/commercetools-php-symfony/commit/9b58ec2))
* **Profiler:** add commercetools stats to profiler ([aa4db35](https://github.com/commercetools/commercetools-php-symfony/commit/aa4db35))
* **Search:** add range facets to search model ([d18588d](https://github.com/commercetools/commercetools-php-symfony/commit/d18588d)), closes [#24](https://github.com/commercetools/commercetools-php-symfony/issues/24)
* **Search:** add search model ([5915226](https://github.com/commercetools/commercetools-php-symfony/commit/5915226))
* **Templating:** remove assetic parts ([b46319d](https://github.com/commercetools/commercetools-php-symfony/commit/b46319d))

### Breaking Change

The Service ```commercetools.client``` has been changed. It now returns a client instead of a client factory.

  Before:

  ```
  $client = $this->get('commercetools.client')->build($locale);
  ```

  After:

  ```
  $client = $this->get('commercetools.client.factory')->build($locale);
  ```
