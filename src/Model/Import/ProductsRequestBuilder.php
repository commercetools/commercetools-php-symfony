<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 21/11/16
 * Time: 11:48
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;


use Commercetools\Commons\Helper\QueryHelper;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Category\CategoryCollection;
use Commercetools\Core\Model\Category\CategoryReference;
use Commercetools\Core\Model\Category\CategoryReferenceCollection;
use Commercetools\Core\Model\Product\ProductDraft;
use Commercetools\Core\Model\Product\ProductVariantDraft;
use Commercetools\Core\Model\Product\SearchKeywords;
use Commercetools\Core\Model\ProductType\ProductTypeReference;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Model\TaxCategory\TaxCategoryCollection;
use Commercetools\Core\Request\Categories\CategoryQueryRequest;
use Commercetools\Core\Request\Products\ProductCreateRequest;
use Commercetools\Core\Request\Products\ProductQueryRequest;
use Commercetools\Core\Request\TaxCategories\TaxCategoryQueryRequest;
use Commercetools\Core\Request\TaxCategories\TaxCategoryUpdateRequest;

class ProductsRequestBuilder extends AbstractRequestBuilder
{
    private $categories;

    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;

        $this->categories = $this->getCategories();
        $this->taxCategories = $this->getTaxCategories();
    }

    private function getCategories()
    {
        $request = CategoryQueryRequest::of();

        $helper = new QueryHelper();
        $categories = $helper->getAll($this->client, $request);


        /**
         * @var CategoryCollection $categories;
         */
        $catReferences = [];
        foreach ($categories as $category) {
            $catReferences[$category->getId()] = [
                'name' => (string)$category->getName(),
                'reference' => $category->getReference(),
                'ancestors' => $category->getAncestors()
            ];
        }
        $catByPath = [];
        foreach ($catReferences as $categoryInfo) {
            $path = [];
            foreach ($categoryInfo['ancestors'] as $ancestor) {
                $path[] = $catReferences[$ancestor->getId()]['name'];
            }
            $path[] = $categoryInfo['name'];
            $categoryPath = implode('>', $path);
            $catByPath[$categoryPath] = $categoryInfo['reference'];
        }
        return $catByPath;
    }

    private function getTaxCategories()
    {
        $request = TaxCategoryQueryRequest::of();

        $helper = new QueryHelper();
        $taxCategories = $helper->getAll($this->client, $request);


        /**
         * @var TaxCategoryCollection $taxCategories;
         */
        $taxCatReferences = [];
        foreach ($taxCategories as $category) {
            $taxCatReferences[(string)$category->getName()] = $category->getReference();
        }
//        var_dump($taxCatReferences);exit;
        return $taxCatReferences;
    }
    /**
     * @param $productData
     * @param $identifiedByColumn
     * @param $identifier
     * @return ClientRequestInterface
     */
    public function createRequest($productData, $identifiedByColumn)
    {
//        var_dump($identifiedByColumn);exit;
        $request = ProductQueryRequest::of()
            ->where(
                sprintf(
                    $this->getIdentifierQuery($identifiedByColumn),
                    $this->getIdentifierFromArray($identifiedByColumn, $productData)
                )
            )
            ->limit(1);
        $response = $request->executeWithClient($this->client);

        $products = $request->mapFromResponse($response);

        if (count($products) > 0) {
            /**
             * @var Category $category
             */
//            $category = $categories->current();

//            $request = $this->getUpdateRequest($category, $productData);
        } else {
            $request = $this->getCreateRequest($productData);
        }

        return $request;
    }

    private function getCreateRequest($productData)
    {
        $productDraftArray = $this->mapProductFromData($productData);
        $product = ProductDraft::fromArray($productDraftArray);
        $request = ProductCreateRequest::ofDraft($product);
        return $request;
    }

    private function mapProductFromData($productData)
    {

        $productDraftArray= [];
        foreach ($productData as $key => $value) {
            switch ($key) {
                case "metaTitle":
                case "metaDescription":
                case "metaKeywords":
                case "key":
                case "name":
                case "slug":
                case "description":
                case "publish":
                    $productDraftArray[$key]= $value;
                    break;
                case "productType":
                    $productDraftArray[$key]= ProductTypeReference::ofKey($value);
                    break;
                case "tax":
//                    var_dump( $value);exit;
                    $productDraftArray['taxCategory']= $this->taxCategories[$value];
                    break;
//                case "searchKeywords":
//                    $keywords =
//                    $productDraftArray[$key]= SearchKeywords::fromArray($value);
//                    break;
                case "state":
                    $productDraftArray[$key]= StateReference::ofKey($value);
                    break;
                case "categories":
                    $categories = CategoryReferenceCollection::of();
                    $productCategories= explode(';', $value);
                    foreach ($productCategories as $category) {
                        $categories->add($this->categories[$category]);
                    }
                    $productDraftArray[$key]= $categories;
                    break;
                case "baseId":
                    $productDraftArray['masterVariant']['attributes'][] = ProductVariantDraft::fromArray([ 'name' => 'baseId', 'value' => $value]);
                    break;
            }
        }
        return $productDraftArray;
    }
    public function getIdentifierQuery($identifierName, $query = '= "%s"')
    {
        $parts = explode('.', $identifierName);
        $value="";
        switch ($parts[0]) {
            case "slug":
                $value = $parts[0].'('.$parts[1]. $query . ')';
                break;
            case "id":
                $value = $parts[0].$query;
                break;
        }
        return $value;
    }
    public function getIdentifierFromArray($identifierName, $row)
    {
        $parts = explode('.', $identifierName);
        $value="";
        switch ($parts[0]) {
            case "slug":
                $value = $row[$parts[0]][$parts[1]];
                break;
            case "id":
                $value = $row[$parts[0]];
                break;
        }
        return $value;
    }
}
