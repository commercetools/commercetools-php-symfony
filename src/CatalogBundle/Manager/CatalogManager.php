<?php
/**
 */

namespace Commercetools\Symfony\CatalogBundle\Manager;

use Commercetools\Core\Model\Category\CategoryCollection;
use Commercetools\Core\Model\Product\Product;
use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Model\Product\ProductProjectionCollection;
use Commercetools\Core\Model\ProductType\ProductTypeCollection;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CatalogBundle\Model\ProductUpdateBuilder;
use Commercetools\Symfony\CatalogBundle\Event\ProductPostUpdateEvent;
use Commercetools\Symfony\CatalogBundle\Event\ProductUpdateEvent;
use Commercetools\Symfony\CatalogBundle\Model\Repository\CatalogRepository;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\ExampleBundle\Controller\CatalogController;
use Psr\Http\Message\UriInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CatalogManager
{
    /**
     * @var CatalogRepository
     */
    private $repository;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * CatalogManager constructor.
     * @param CatalogRepository $repository
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(CatalogRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string $locale
     * @param int $itemsPerPage
     * @param int $offset
     * @param string $sort
     * @param string|null $currency
     * @param string|null $country
     * @param UriInterface|null $uri
     * @param string|null $search
     * @param array|null $filters
     * @return array
     */
    public function searchProducts(
        $locale,
        $itemsPerPage = CatalogController::ITEMS_PER_PAGE,
        $offset = 1,
        $sort = 'id asc',
        $currency = null,
        $country = null,
        UriInterface $uri = null,
        $search = null,
        $filters = null
    ) {
        $searchRequest = $this->repository->baseSearchProductsRequest($itemsPerPage, $offset, $sort);
        $searchRequest = $this->repository->searchRequestAddCountryAndCurrency($searchRequest, $country, $currency);
        $searchRequest = $this->repository->searchRequestAddSearchParameters($searchRequest, $locale, $uri, $search);
        $searchRequest = $this->repository->searchRequestAddSearchFilters($searchRequest, $filters);

        return $this->repository->executeSearchRequest($searchRequest, $locale);
    }

    /**
     * @param $locale
     * @param $slug
     * @param $currency
     * @param $country
     * @return \Commercetools\Core\Model\Product\ProductProjection|null
     */
    public function getProductBySlug($locale, $slug, $currency, $country)
    {
        return $this->repository->getProductBySlug($locale, $slug, $currency, $country);
    }

    /**
     * @param string $locale
     * @param string $term
     * @param int $limit
     * @param string $currency
     * @param string $country
     * @return ProductProjectionCollection
     */
    public function suggestProducts($locale, $term, $limit, $currency, $country)
    {
        return $this->repository->suggestProducts($locale, $term, $limit, $currency, $country);
    }

    /**
     * @param string $locale
     * @param string $id
     * @return ProductProjection
     */
    public function getProductById($locale, $id)
    {
        return $this->repository->getProductById($locale, $id);
    }

    /**
     * @param string $locale
     * @param string $id
     * @param QueryParams|null $params
     * @return ProductProjection
     */
    public function getProductTypeById($locale, $id, QueryParams $params = null)
    {
        return $this->repository->getProductTypeById($locale, $id, $params);
    }

    /**
     * @param string $locale
     * @param QueryParams $params
     * @return ProductTypeCollection
     */
    public function getProductTypes($locale, QueryParams $params = null)
    {
        return $this->repository->getProductTypes($locale, $params);
    }

    /**
     * @param string $locale
     * @param QueryParams $params
     * @return CategoryCollection
     */
    public function getCategories($locale, QueryParams $params = null)
    {
        return $this->repository->getCategories($locale, $params);
    }

    /**
     * @param Product $product
     * @return ProductUpdateBuilder
     */
    public function update(Product $product)
    {
        return new ProductUpdateBuilder($product, $this);
    }

    /**
     * @param Product $product
     * @param AbstractAction $action
     * @param string|null $eventName
     * @return AbstractAction[]
     */
    public function dispatch(Product $product, AbstractAction $action, $eventName = null)
    {
        $eventName = is_null($eventName) ? get_class($action) : $eventName;

        $event = new ProductUpdateEvent($product, $action);
        $event = $this->dispatcher->dispatch($event, $eventName);

        return $event->getActions();
    }

    /**
     * @param Product $product
     * @param array $actions
     * @return Product
     */
    public function apply(Product $product, array $actions)
    {
        $product = $this->repository->update($product, $actions);

        $this->dispatchPostUpdate($product, $actions);

        return $product;
    }

    /**
     * @param Product $product
     * @param array $actions
     * @return AbstractAction[]
     */
    public function dispatchPostUpdate(Product $product, array $actions)
    {
        $event = new ProductPostUpdateEvent($product, $actions);
        $event = $this->dispatcher->dispatch($event);

        return $event->getActions();
    }
}
