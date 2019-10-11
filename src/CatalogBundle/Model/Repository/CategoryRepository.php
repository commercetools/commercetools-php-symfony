<?php
/**
 */

namespace Commercetools\Symfony\CatalogBundle\Model\Repository;

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Client\ApiClient;
use Commercetools\Core\Error\InvalidArgumentException;
use Commercetools\Core\Model\Category\Category;
use Commercetools\Core\Model\Category\CategoryDraft;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\Product\Product;
use Commercetools\Core\Model\Product\ProductDraft;
use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Model\Product\ProductProjectionCollection;
use Commercetools\Core\Model\Product\SuggestionCollection;
use Commercetools\Core\Model\ProductType\ProductTypeDraft;
use Commercetools\Core\Model\ProductType\ProductTypeReference;
use Commercetools\Core\Request\Products\ProductProjectionSearchRequest;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Symfony\CatalogBundle\Model\Search;
use Commercetools\Symfony\CtpBundle\Service\ContextFactory;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\UriInterface;

class CategoryRepository extends Repository
{
    const NAME = 'categories';

    /**
     * @param $locale
     * @param QueryParams $params
     * @return mixed
     */
    public function getCategories($locale, QueryParams $params = null)
    {
        $cacheKey = static::NAME . '-' . $locale;

        $categoriesRequest = RequestBuilder::of()->categories()->query();

        if (!is_null($params)) {
            foreach ($params->getParams() as $param) {
                $categoriesRequest->addParamObject($param);
            }
        }

        return $this->retrieve($cacheKey, $categoriesRequest, $locale);
    }

    /**
     * @param $locale
     * @param string $slug
     * @return Category|null
     */
    public function getCategoryBySlug($locale, string $slug)
    {
        $cacheKey = static::NAME . '-' . $slug . '-' . $locale;

        $categoriesRequest = RequestBuilder::of()->categories()->query()
            ->where('slug(' . $locale . ' = "' . $slug . '")')->limit(1);

        $categories = $this->retrieve($cacheKey, $categoriesRequest, $locale);
        $category = $categories->current();

        return $category;
    }

    /**
     * @param $locale
     * @param string $id
     * @return Category|null
     */
    public function getCategoryById($locale, string $id)
    {
        $cacheKey = static::NAME . '-' . $id . '-' . $locale;

        $categoriesRequest = RequestBuilder::of()->categories()->getById($id);

        return $this->retrieve($cacheKey, $categoriesRequest, $locale);
    }

    /**
     * @param Category $category
     * @param array $actions
     * @param QueryParams|null $params
     * @return mixed
     */
    public function update(Category $category, array $actions, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->categories()->update($category)->setActions($actions);

        if (!is_null($params)) {
            foreach ($params->getParams() as $param) {
                $request->addParamObject($param);
            }
        }

        return $this->executeRequest($request);
    }

    /**
     * @param $locale
     * @param $name
     * @param $slug
     * @return mixed
     */
    public function createCategory($locale, $name, $slug)
    {
        $categoryDraft = CategoryDraft::ofNameAndSlug(
            LocalizedString::ofLangAndText($locale, $name),
            LocalizedString::ofLangAndText($locale, $slug)
        );

        $request = RequestBuilder::of()->categories()->create($categoryDraft);

        return $this->executeRequest($request, $locale);
    }
}
