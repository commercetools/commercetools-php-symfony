<?php
/**
 */

namespace Commercetools\Symfony\CatalogBundle\Manager;


use Commercetools\Core\Model\Product\Product;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CartBundle\Model\ProductUpdateBuilder;
use Commercetools\Symfony\CatalogBundle\Event\ProductPostUpdateEvent;
use Commercetools\Symfony\CatalogBundle\Event\ProductUpdateEvent;
use Commercetools\Symfony\CatalogBundle\Model\Repository\CatalogRepository;
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

    public function getProducts(
        $locale,
        $itemsPerPage,
        $currentPage,
        $sort,
        $currency,
        $country,
        UriInterface $uri,
        $search = null,
        $filters = null
    ){
        return $this->repository->getProducts($locale, $itemsPerPage, $currentPage, $sort, $currency, $country, $uri, $search, $filters);
    }

    public function getProductBySlug($slug, $locale, $currency, $country)
    {
        return $this->repository->getProductBySlug($slug, $locale, $currency, $country);
    }

    public function suggestProducts($locale, $term, $limit, $currency, $country)
    {
        return $this->repository->suggestProducts($locale, $term, $limit, $currency, $country);
    }

    public function getProductById($id, $locale)
    {
        return $this->repository->getProductById($id, $locale);
    }

    public function getProductTypes($locale, $sort)
    {
        return $this->repository->getProductTypes($locale, $sort);
    }

    public function getCategories($locale, $sort)
    {
        return $this->repository->getCategories($locale, $sort);
    }

    /**
     * @param Product $product
     * @return ProductUpdateBuilder
     */
    public function update(Product $product)
    {
        return new ProductUpdateBuilder($product, $this);
    }

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

    public function dispatchPostUpdate(Product $product, array $actions)
    {
        $event = new ProductPostUpdateEvent($product, $actions);
        $event = $this->dispatcher->dispatch(ProductPostUpdateEvent::class, $event);

        return $event->getActions();
    }
}
