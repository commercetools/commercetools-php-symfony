<?php
/**
 */

namespace Commercetools\Symfony\CatalogBundle\Manager;


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
        $search = null,
        UriInterface $uri,
        $filters = null
    ){
        return $this->repository->getProducts($locale, $itemsPerPage, $currentPage, $sort, $currency, $country, $search, $uri, $filters);
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

    public function getProductTypes()
    {
        // TODO:
        return $this->repository->getProductTypes();
    }

    public function getCategories($locale, $sort)
    {
        return $this->repository->getCategories($locale, $sort);
    }
}
