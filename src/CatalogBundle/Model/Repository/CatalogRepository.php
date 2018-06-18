<?php
/**
 */

namespace Commercetools\Symfony\CatalogBundle\Model\Repository;

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Model\Product\SuggestionCollection;
use Commercetools\Core\Request\Products\ProductsSuggestRequest;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Symfony\CatalogBundle\Model\Search;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\UriInterface;

class CatalogRepository extends Repository
{
    const NAME = 'products';

    /**
     * @var Search
     */
    private $searchModel;

    public function __construct(
        $enableCache,
        CacheItemPoolInterface $cache,
        Client $client,
        MapperFactory $mapperFactory,
        Search $searchModel
    ) {
        $this->searchModel = $searchModel;
        parent::__construct($enableCache, $cache, $client, $mapperFactory);
    }


    /**
     * @param $slug
     * @param $locale
     * @param $currency
     * @param $country
     * @return ProductProjection|null
     */
    public function getProductBySlug($slug, $locale, $currency, $country)
    {
        $client = $this->getClient();
        $cacheKey = static::NAME . '-' . $slug . '-' . $locale;

        $productRequest = RequestBuilder::of()->productProjections()
            ->getBySlug($slug, $client->getConfig()->getContext()->getLanguages())
            ->country($country)
            ->currency($currency);

        $productProjection = $this->retrieve($client, $cacheKey, $productRequest, $locale);

        return $productProjection;
    }

    /**
     * @param $id
     * @param $locale
     * @return \Commercetools\Core\Model\Common\JsonDeserializeInterface|null
     */
    public function getProductById($id, $locale)
    {
        $client = $this->getClient();
        $cacheKey = static::NAME . '-' . $id . '-' . $locale;

        $productRequest = RequestBuilder::of()->productProjections()->getById($id);

        $product = $this->retrieve($client, $cacheKey, $productRequest, $locale);

        return $product;
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
        $client = $this->getClient();
        $suggestRequest = ProductsSuggestRequest::ofKeywords(LocalizedString::ofLangAndText($locale, $term));
        $response = $suggestRequest->executeWithClient($client);
        $data = $response->toArray();
        $language = \Locale::getPrimaryLanguage($locale);

        if (isset($data['searchKeywords.'. $language])) {
            $suggestions = SuggestionCollection::fromArray($data['searchKeywords.'. $language]);

            $suggestion = $suggestions->current();

            if (!is_null($suggestion)) {
                $term = $suggestion->getText();
            }
        }

        $searchRequest = RequestBuilder::of()->productProjections()->search()
            ->limit($limit)
            ->currency($currency)
            ->country($country)
            ->fuzzy(true)
            ->addParam('text.' . $language, $term);

        return $this->executeRequest($searchRequest, $locale);
    }

    /**
     * @param $locale
     * @param $itemsPerPage
     * @param $currentPage
     * @param $sort
     * @param $currency
     * @param $country
     * @param UriInterface $uri
     * @param $search
     * @param array $filters
     * @return array
     */
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

        $searchRequest = RequestBuilder::of()->productProjections()->search()
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

        $selectedValues = $this->searchModel->getSelectedValues($uri);
        $searchRequest = $this->searchModel->addFacets($searchRequest, $selectedValues);

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

        $response = $searchRequest->executeWithClient($this->getClient());
        $products = $searchRequest->mapFromResponse(
            $response,
            $this->getMapper($locale)
        );

        return [$products, $response->getFacets(), $response->getOffset(), $response->getTotal()];
    }

    /**
     * @param $locale
     * @param $sort
     * @return mixed
     */
    public function getProductTypes($locale, $sort)
    {
        $productTypesRequest = $productTypes = RequestBuilder::of()->productTypes()->query()->sort($sort);

        return $this->executeRequest($productTypesRequest, $locale);
    }

    /**
     * @param $locale
     * @param $sort
     * @return mixed
     */
    public function getCategories($locale, $sort)
    {
        $categoriesRequest = RequestBuilder::of()->categories()->query()->sort($sort);

        return $this->executeRequest($categoriesRequest, $locale);
    }
}
