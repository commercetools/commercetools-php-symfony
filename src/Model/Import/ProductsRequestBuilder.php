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
use Commercetools\Core\Model\Product\Product;
use Commercetools\Core\Model\Product\ProductDraft;
use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Model\Product\ProductVariantDraft;
use Commercetools\Core\Model\Product\ProductVariantDraftCollection;
use Commercetools\Core\Model\Product\SearchKeywords;
use Commercetools\Core\Model\ProductType\AttributeDefinition;
use Commercetools\Core\Model\ProductType\BooleanType;
use Commercetools\Core\Model\ProductType\LocalizedStringType;
use Commercetools\Core\Model\ProductType\ProductType;
use Commercetools\Core\Model\ProductType\ProductTypeCollection;
use Commercetools\Core\Model\ProductType\ProductTypeReference;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Model\TaxCategory\TaxCategoryCollection;
use Commercetools\Core\Request\Categories\CategoryQueryRequest;
use Commercetools\Core\Request\Payments\PaymentUpdateRequest;
use Commercetools\Core\Request\Products\Command\ProductAddToCategoryAction;
use Commercetools\Core\Request\Products\Command\ProductAddVariantAction;
use Commercetools\Core\Request\Products\Command\ProductChangeNameAction;
use Commercetools\Core\Request\Products\Command\ProductChangeSlugAction;
use Commercetools\Core\Request\Products\Command\ProductRemoveFromCategoryAction;
use Commercetools\Core\Request\Products\Command\ProductRemoveVariantAction;
use Commercetools\Core\Request\Products\Command\ProductSetAttributeAction;
use Commercetools\Core\Request\Products\Command\ProductSetAttributeInAllVariantsAction;
use Commercetools\Core\Request\Products\Command\ProductSetDescriptionAction;
use Commercetools\Core\Request\Products\Command\ProductSetKeyAction;
use Commercetools\Core\Request\Products\Command\ProductSetMetaDescriptionAction;
use Commercetools\Core\Request\Products\Command\ProductSetMetaKeywordsAction;
use Commercetools\Core\Request\Products\Command\ProductSetMetaTitleAction;
use Commercetools\Core\Request\Products\Command\ProductSetTaxCategoryAction;
use Commercetools\Core\Request\Products\ProductCreateRequest;
use Commercetools\Core\Request\Products\ProductProjectionQueryRequest;
use Commercetools\Core\Request\Products\ProductQueryRequest;
use Commercetools\Core\Request\Products\ProductUpdateRequest;
use Commercetools\Core\Request\ProductTypes\ProductTypeQueryRequest;
use Commercetools\Core\Request\TaxCategories\TaxCategoryQueryRequest;
use Commercetools\Core\Request\TaxCategories\TaxCategoryUpdateRequest;
use Commercetools\Core\Model\Common\LocalizedString;

class ProductsRequestBuilder extends AbstractRequestBuilder
{
    private $categories;
    private $productTypes;
    private $client;
    private $productVariantsBySku;
    private $productVariantsDraftBySku;

    public function __construct(Client $client)
    {
        $this->client = $client;

        $this->categories = $this->getCategories();
        $this->taxCategories = $this->getTaxCategories();
        $this->productTypes = $this->getProductTypes();
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

    private function getProductTypes()
    {
        $request = ProductTypeQueryRequest::of();

        $helper = new QueryHelper();
        $productTypes = $helper->getAll($this->client, $request);


        /**
         * @var ProductTypeCollection $productTypes;
         */
        $productTypesByKey = [];
        foreach ($productTypes as $productType) {
            $productTypesByKey[$productType->getKey()] = $productType;
            $productTypesByKey[$productType->getId()] = $productType;
        }

        return $productTypesByKey;
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
        $request = ProductProjectionQueryRequest::of()
            ->where(
                sprintf(
                    $this->getIdentifierQuery($identifiedByColumn),
                    $this->getIdentifierFromArray($identifiedByColumn, $productData)
                )
            )
            ->staged(true)
            ->limit(1);

        $response = $request->executeWithClient($this->client);
        $products = $request->mapFromResponse($response);


        if (count($products) > 0) {
            /**
             * @var ProductProjection $product
             */
            $product = $products->current();
            $request = $this->getUpdateRequest($product, $productData);
        } else {
            $request = $this->getCreateRequest($productData);
        }

        return $request;
    }
    private function createVariantAddRequest($variants)
    {
        $actions = [];

        foreach ($variants as $variant) {
            $actions[]=ProductAddVariantAction::fromArray($variant);
        }
        return $actions;
    }
    private function getCreateRequest($productData)
    {
        $productDraftArray = $this->mapProductFromData($productData);
        $product = ProductDraft::fromArray($productDraftArray);
        $request = ProductCreateRequest::ofDraft($product);
        return $request;
    }

    private function getUpdateRequest(ProductProjection $product, $productData)
    {
        $productDraftArray = $this->mapProductFromData($productData);
        $productDataDraft = ProductDraft::fromArray($productDraftArray);
        $productDataArray= $productDataDraft->toArray();

        $product = $product->toArray();
        if (isset($product['masterVariant']['attributes'])) {
            foreach ($product['masterVariant']['attributes'] as &$attribute) {
                if (isset($attribute['value']['key'])) {
                    $attribute['value'] = $attribute['value']['key'];
                }
            }
        }
        if (isset($product['variants'])) {
            foreach ($product['variants'] as &$variant) {
                if (isset($variant['attributes'])) {
                    foreach ($variant['attributes'] as &$attribute) {
                        if (isset($attribute['value']['key'])) {
                            $attribute['value'] = $attribute['value']['key'];
                        }
                    }
                }
            }
        }

        $this->productVariantsBySku = $this->getProductVariantsBySku($product['variants']);
        $this->productVariantsDraftBySku = $this->getDataVariantsBySku($productDataArray['variants']);

        $intersect = $this->arrayIntersectRecursive($product, $productDataArray);

        $toRemove['variants'] = $this->getVariantsDiff($this->productVariantsBySku, $this->productVariantsDraftBySku, false);
        $toAdd['variants'] = $this->getVariantsDiff($this->productVariantsBySku, $this->productVariantsDraftBySku);

        $toChange= $this->arrayDiffRecursive($productDataArray, $intersect);
        $toChange['categories']=$this->categoriesToAdd($product['categories'], $productDataArray['categories']);


        $toRemove['categories']=$this->categoriesToRemove($product['categories'], $productDataArray['categories']);

        $request = ProductUpdateRequest::ofIdAndVersion($product['id'], $product['version']);

        $actions = [];

        foreach ($toAdd as $heading => $data) {
            switch ($heading) {
                case "variants":
                    $actions = array_merge_recursive(
                        $actions,
                        $this->createVariantAddRequest($data)
                    );
                    break;
            }
        }
        foreach ($toChange as $heading => $data) {
            switch ($heading) {
                case 'name':
                    $actions[$heading] = ProductChangeNameAction::ofName(
                        LocalizedString::fromArray($productDraftArray[$heading])
                    );
                    break;
                case 'slug':
                    $actions[$heading] = ProductChangeSlugAction::ofSlug(
                        LocalizedString::fromArray($productDraftArray[$heading])
                    );
                    break;
                case 'description':
                    $actions[$heading] = ProductSetDescriptionAction::ofDescription(
                        LocalizedString::fromArray($productDraftArray[$heading])
                    );
                    break;
                case 'key':
                    $actions[$heading] = ProductSetKeyAction::ofKey(
                        $productDraftArray[$heading]
                    );
                    break;
                case 'taxCategory':
                    $actions[$heading] = ProductSetTaxCategoryAction::of()->setTaxCategory($productDraftArray[$heading]);
                    break;
                case 'categories':
                    foreach ($toChange[$heading] as $category) {
                        $actions[$heading.$category['id']] = ProductAddToCategoryAction::ofCategory(CategoryReference::fromArray($category));
                    }
                    break;
                case 'metaTitle':
                    $actions[$heading] = ProductSetMetaTitleAction::of()->setMetaTitle(LocalizedString::fromArray($data));
                    break;
                case 'metaDescription':
                    $actions[$heading] = ProductSetMetaDescriptionAction::of()->setMetaDescription(LocalizedString::fromArray($data));
                    break;
                case 'metaKeywords':
                    $actions[$heading] = ProductSetMetaKeywordsAction::of()->setMetaKeywords(LocalizedString::fromArray($data));
                    break;
                case "masterVariant":
                    $actions = array_merge_recursive(
                        $actions,
                        $this->getVariantActions($product['masterVariant'], $productDraftArray[$heading], $product['productType'])
                    );
                    break;
                case "variants":
//                    foreach ($product['variants'] as $variant) {
//                        if (isset($this->productVariantsDraftBySku[$variant['sku']])) {
//                            $actions = array_merge_recursive(
//                                $actions,
//                                $this->getVariantActions($variant, ProductVariantDraft::fromArray($this->productVariantsDraftBySku[$variant['sku']]), $product['productType'])
//                            );
//                        }
//                    }
                    for($i=0; $i<count($product['variants']); $i++)
                    {
                        if (isset($productDraftArray[$heading][$i])) {
                            $actions = array_merge_recursive(
                                $actions,
                                $this->getVariantActions($product['variants'][$i], $productDraftArray[$heading][$i], $product['productType'])
                            );
                        }
                    }
                    break;
            }
        }
        foreach ($toRemove as $heading => $data) {
            switch ($heading) {
                case 'categories':
                    foreach ($toRemove[$heading] as $category) {
                        $actions[$heading.$category['id']] = ProductRemoveFromCategoryAction::ofCategory(CategoryReference::fromArray($category));
                    }
                    break;
                case "variants":
                    foreach ($data as $variant) {
                        if (isset($variant['sku'])) {
                            $actions[$heading . 'remove' . $variant['sku']] = ProductRemoveVariantAction::ofSku($variant['sku']);
                        }
                    }
                    break;
            }
        }
        $request->setActions($actions);
//        print_r((string)$request->httpRequest()->getBody());exit;

        return $request;
    }

    private function getProductVariantsBySku($productVariants)
    {
        $productVariantsBySku = [];
        foreach ($productVariants as $variant) {
            $productVariantsBySku[$variant['sku']] = $variant;
        }

        return $productVariantsBySku;
    }
    private function getDataVariantsBySku($ProductVariantDraftCollection)
    {
        /**
         * @var ProductVariantDraftCollection $variants
         */
        $variants= ProductVariantDraftCollection::fromArray($ProductVariantDraftCollection);
        $ProductVariantDraftCollection = $variants->toArray();

        $productVariantsDraftBySku = [];

        foreach ($ProductVariantDraftCollection as $variant) {
            unset($variant['variantId']);
            $productVariantsDraftBySku[$variant['sku']] = $variant;
        }
        return $productVariantsDraftBySku;
    }
    private function getVariantsDiff($productVariants, $ProductVariantDraftCollection, $toAddFlag = true)
    {
        if ($toAddFlag) {
            $result = $this->arrayDiffRecursive($ProductVariantDraftCollection, $productVariants);
        } else {
            $result = $this->arrayDiffRecursive($productVariants, $ProductVariantDraftCollection);
        }

        return $result;
    }
    private function getVariantActions($productVariant, $productVariantDraftArray, $productType)
    {
        $actions=[];
        $productAttributes = [];
        $productDraftAttributes = [];
        foreach ($productVariant['attributes'] as $attribute) {
            $productAttributes[$attribute['name']] = $attribute;
        }
        foreach ($productVariantDraftArray->toArray()['attributes'] as $attribute) {
            $productDraftAttributes[$attribute['name']] = $attribute;
        }

        $toChange = $this->arrayDiffRecursive($productAttributes, $productDraftAttributes);
//        var_dump($toChange);
        /**
         * @var ProductType $productType
         */
        $productType = $this->productTypes[$productType['id']];

        foreach ($toChange as $key => $value) {
            $attributeDefinition = $productType->getAttributes()->getByName($key);
            if ($attributeDefinition->getAttributeConstraint() == 'SameForAll') {
                $action = ProductSetAttributeInAllVariantsAction::ofName($key);
            } else {
                $action = ProductSetAttributeAction::ofVariantIdAndName($productVariant['id'], $key);
            }

            if ($productDraftAttributes[$key]['value']) {
                $action->setValue($productDraftAttributes[$key]['value']);
            }
            $actions['variant' . $productVariant['id'] . $key] = $action;
        }
        return $actions;
    }
    private function mapVariantFromData($variantData, ProductType $productType)
    {
        $variantDraftArray= [];
        foreach ($variantData as $key => $value) {
            switch ($key) {
                case "metaTitle":
                case "metaDescription":
                case "metaKeywords":
                case "key":
                case "name":
                case "slug":
                case "description":
                case "publish":
                case "tax":
                case "categories":
                case "productType":
                case "id":
                case "creationDate":
                    break;
                case "variantId":
                case "sku":
                    $variantDraftArray[$key] = $value;
                    break;
                case "images":
                case "searchKeywords":
                case "prices":
//                    $variantDraftArray[$key] = $value;
                    break;
                default:
                    if (!is_null($value) && $value !== '') {
                        $attributeDefinition = $productType->getAttributes()->getByName($key);
                        if ($attributeDefinition) {
                            $attributeType = $attributeDefinition->getType();
                            switch (true) {
                                case $attributeType instanceof BooleanType:
                                    $value = $value == 'true' ? true: false;
                                    break;
                            }
                            if ($value) {
                                $variantDraftArray['attributes'][] = ['name' => $key, 'value' => $value];
                            }
                        }
                    }
                    break;
            }
        }
        return $variantDraftArray;
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
                    if (!is_null($value) && $value !== '') {
                        $productDraftArray[$key]= $value;
                    }
                    break;
                case "productType":
                    $productDraftArray[$key]= ProductTypeReference::ofKey($value);
                    break;
                case "tax":
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
//                case "baseId":
//                    $productDraftArray['masterVariant']['attributes'][] = ProductVariantDraft::fromArray([ 'name' => 'baseId', 'value' => $value]);
//                    break;
                case "variants":
                    $variants=[];
                    foreach ($value as $variant) {
                        $variantData = $this->mapVariantFromData($variant, $this->productTypes[$productData['productType']]);
                        if ($variantData['variantId'] === '1') {
                            $productDraftArray['masterVariant']= ProductVariantDraft::fromArray($variantData);
                            continue;
                        }
                        $variants[]= ProductVariantDraft::fromArray($variantData);
                    }
                    $productDraftArray[$key]= $variants;
            }
        }
//        if (array_key_exists('variants', $productDraftArray)){
//            $productDraftArray['masterVariant']['attributes'][] = ProductVariantDraft::fromArray([ 'name' => 'baseId', 'value' => $productData['baseId']]);
//        }
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
            case "key":
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
            case "key":
            case "id":
                $value = $row[$parts[0]];
                break;
        }
        return $value;
    }
    private function categoriesToAdd($productCategories, $dataCategories)
    {
        $toAdd=[];
        foreach ($dataCategories as $category) {
            if (!$this->searchArray($category['id'], $productCategories)) {
                $toAdd []= $category;
            }
        }
        return $toAdd;
    }
    private function categoriesToRemove($productCategories, $dataCategories)
    {
        $toRemove=[];
        foreach ($productCategories as $category) {
            if (!$this->searchArray($category['id'], $dataCategories)) {
                $toRemove []= $category;
            }
        }
        return $toRemove;
    }
    private function searchArray($needle, $haystack)
    {
        if (in_array($needle, $haystack)) {
            return true;
        }
        foreach ($haystack as $element) {
            if (is_array($element) && $this->searchArray($needle, $element))
                return true;
        }
        return false;
    }
}
