<?php
/**
 * @author jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Model\Repository;

use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Request\Products\ProductProjectionByIdGetRequest;
use Commercetools\Core\Request\Products\ProductProjectionBySlugGetRequest;
use Commercetools\Core\Request\Products\ProductProjectionSearchRequest;
use Commercetools\Symfony\CtpBundle\Model\Repository;

class ProductRepository extends Repository
{
    const NAME = 'products';

    /**
     * @param $slug
     * @param $locale
     * @return ProductProjection|null
     */
    public function getProductBySlug($slug, $locale)
    {
        $client = $this->getClient($locale);
        $cacheKey = static::NAME . '-' . $slug . '-' . $locale;

//        $language = \Locale::getPrimaryLanguage($locale);
//        $productRequest = ProductProjectionSearchRequest::of();
//        $productRequest->addFilter(Filter::of()->setName('slug.'.$language)->setValue($slug));
//        /**
//         * @var ProductProjectionCollection $products
//         */
//        $products = $this->retrieve(static::NAME, $cacheKey, $productRequest);
//        $product = $products->current();

        $productRequest = ProductProjectionBySlugGetRequest::ofSlugAndContext(
            $slug,
            $client->getConfig()->getContext()
        );
        $product = $this->retrieve($client, $cacheKey, $productRequest);

        return $product;
    }

    public function getProductById($id, $locale)
    {
        $client = $this->getClient($locale);
        $cacheKey = static::NAME . '-' . $id . '-' . $locale;

        $productRequest = ProductProjectionByIdGetRequest::ofId(
            $id
        );
        $product = $this->retrieve($client, $cacheKey, $productRequest);

        return $product;
    }

    /**
     * @param $locale
     * @param $itemsPerPage
     * @param $currentPage
     * @param $sort
     * @param $currency
     * @param $country
     * @param $search
     * @param array $filters
     * @param array $facets
     * @return array
     */
    public function getProducts(
        $locale,
        $itemsPerPage,
        $currentPage,
        $sort,
        $currency,
        $country,
        $search = null,
        $filters = null,
        $facets = null
    ){

        $searchRequest = ProductProjectionSearchRequest::of()
            ->sort($sort)
            ->limit($itemsPerPage)
            ->currency($currency)
            ->country($country)
            ->offset(min($itemsPerPage * ($currentPage - 1),100000));

        if (!is_null($search)) {
            $language = \Locale::getPrimaryLanguage($locale);
            $searchRequest->addParam('text.' . $language, $search);
            $searchRequest->fuzzy(true);
        }
        if (!is_null($facets)) {
            foreach ($facets as $facet) {
                $searchRequest->addFacet($facet);
            }
        }
        if (!is_null($filters)) {
            foreach ($filters as $type => $typeFilters) {
                foreach ($typeFilters as $filter) {
                    switch ($type) {
                        case 'filter':
                            $searchRequest->addFilter($filter);
                            break;
                        case 'filter.query':
                            $searchRequest->addFilterQuery($filter);
                            break;
                        case 'filter.facets':
                            $searchRequest->addFilterFacets($filter);
                            break;
                    }
                }
            }
        }
        $response = $searchRequest->executeWithClient($this->getClient($locale));
        $products = $searchRequest->mapResponse($response);
        return [$products, $response->getFacets(), $response->getOffset(), $response->getTotal()];
    }
}
