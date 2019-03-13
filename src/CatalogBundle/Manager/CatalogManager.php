<?php
/**
 */

namespace Commercetools\Symfony\CatalogBundle\Manager;

use Commercetools\Core\Model\Product\Product;
use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CatalogBundle\Model\ProductUpdateBuilder;
use Commercetools\Symfony\CatalogBundle\Event\ProductPostUpdateEvent;
use Commercetools\Symfony\CatalogBundle\Event\ProductUpdateEvent;
use Commercetools\Symfony\CatalogBundle\Model\Repository\CatalogRepository;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
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
     * @param $locale
     * @param $itemsPerPage
     * @param $currentPage
     * @param $sort
     * @param $currency
     * @param $country
     * @param UriInterface $uri
     * @param null $search
     * @param null $filters
     * @return array
     */
    public function searchProducts(
        $locale,
        $itemsPerPage,
        $currentPage,
        $sort,
        $currency,
        $country,
        UriInterface $uri,
        $search = null,
        $filters = null
    ) {
        $searchRequest = $this->repository->baseSearchProductsRequest($itemsPerPage, $currentPage, $sort);
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
     * @param $locale
     * @param $term
     * @param $limit
     * @param $currency
     * @param $country
     * @return mixed
     */
    public function suggestProducts($locale, $term, $limit, $currency, $country)
    {
        return $this->repository->suggestProducts($locale, $term, $limit, $currency, $country);
    }

    /**
     * @param $locale
     * @param $id
     * @return ProductProjection
     */
    public function getProductById($locale, $id)
    {
        return $this->repository->getProductById($locale, $id);
    }

    /**
     * @param $locale
     * @param QueryParams $params
     * @return mixed
     */
    public function getProductTypes($locale, QueryParams $params = null)
    {
        return $this->repository->getProductTypes($locale, $params);
    }

    /**
     * @param $locale
     * @param QueryParams $params
     * @return mixed
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
     * @param null $eventName
     * @return AbstractAction[]
     */
    public function dispatch(Product $product, AbstractAction $action, $eventName = null)
    {
        $eventName = is_null($eventName) ? get_class($action) : $eventName;

        $event = new ProductUpdateEvent($product, $action);
        $event = $this->dispatcher->dispatch($eventName, $event);

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
        $event = $this->dispatcher->dispatch(ProductPostUpdateEvent::class, $event);

        return $event->getActions();
    }
}
