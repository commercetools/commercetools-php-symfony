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
use Commercetools\Core\Model\Common\Image;
use Commercetools\Core\Model\Common\ImageDimension;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Core\Model\Common\Price;
use Commercetools\Core\Model\Common\PriceDraft;
use Commercetools\Core\Model\CustomerGroup\CustomerGroup;
use Commercetools\Core\Model\CustomerGroup\CustomerGroupCollection;
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
use Commercetools\Core\Request\CustomerGroups\CustomerGroupQueryRequest;
use Commercetools\Core\Request\Payments\PaymentUpdateRequest;
use Commercetools\Core\Request\Products\Command\ProductAddExternalImageAction;
use Commercetools\Core\Request\Products\Command\ProductAddPriceAction;
use Commercetools\Core\Request\Products\Command\ProductAddToCategoryAction;
use Commercetools\Core\Request\Products\Command\ProductAddVariantAction;
use Commercetools\Core\Request\Products\Command\ProductChangeNameAction;
use Commercetools\Core\Request\Products\Command\ProductChangePriceAction;
use Commercetools\Core\Request\Products\Command\ProductChangeSlugAction;
use Commercetools\Core\Request\Products\Command\ProductRemoveFromCategoryAction;
use Commercetools\Core\Request\Products\Command\ProductRemoveImageAction;
use Commercetools\Core\Request\Products\Command\ProductRemovePriceAction;
use Commercetools\Core\Request\Products\Command\ProductRemoveVariantAction;
use Commercetools\Core\Request\Products\Command\ProductSetAttributeAction;
use Commercetools\Core\Request\Products\Command\ProductSetAttributeInAllVariantsAction;
use Commercetools\Core\Request\Products\Command\ProductSetDescriptionAction;
use Commercetools\Core\Request\Products\Command\ProductSetKeyAction;
use Commercetools\Core\Request\Products\Command\ProductSetMetaDescriptionAction;
use Commercetools\Core\Request\Products\Command\ProductSetMetaKeywordsAction;
use Commercetools\Core\Request\Products\Command\ProductSetMetaTitleAction;
use Commercetools\Core\Request\Products\Command\ProductSetSkuAction;
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
    private $productVariantsById;
    private $productVariantsDraftById;
    private $productVariantsBySku;
    private $productVariantsDraftBySku;
    private $productVariantDraftPricesByUniqueKey;

    public function __construct(Client $client)
    {
        $this->client = $client;

        $this->categories = $this->getCategories();
        $this->taxCategories = $this->getTaxCategories();
        $this->productTypes = $this->getProductTypes();
        $this->customerGroups = $this->getCustomerGroups();
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

    private function getCustomerGroups()
    {
        $request = CustomerGroupQueryRequest::of();

        $helper = new QueryHelper();
        $customerGroups = $helper->getAll($this->client, $request);


        /**
         * @var CustomerGroupCollection $customerGroups;
         */
        $customerGroupsByName = [];
        foreach ($customerGroups as $customerGroup) {
            $customerGroupsByName[$customerGroup->getName()] = $customerGroup->getReference();
            $customerGroupsByName[$customerGroup->getId()] = $customerGroup->getName();
        }

        return $customerGroupsByName;
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
            if ($variant['prices']==[]) {
                unset($variant['prices']);
            }
            $actions[]=ProductAddVariantAction::fromArray($variant);
        }
        return $actions;
    }
    private function getCreateRequest($productData)
    {
        $productDraftArray = $this->mapProductFromData($productData, true);
        $product = ProductDraft::fromArray($productDraftArray);
        $request = ProductCreateRequest::ofDraft($product);
        return $request;
    }
    private function getUpdateRequest(ProductProjection $product, $productData)
    {
        $productDraftArray = $this->mapProductFromData($productData);
        $productDataDraft = ProductDraft::fromArray($productDraftArray);
        $productDataArray= $productDataDraft->toArray();

        if (!isset($productDataArray['variants'])) {
            $productDataArray['variants'] = [];
        }
        if (!isset($productDataArray['categories'])) {
            $productDataArray['categories'] = [];
        }

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


        $this->productVariantsById = $this->getProductVariantsById($product['variants']);
        $this->productVariantsDraftById = $this->getDataVariantsById($productDataArray['variants']);

        $intersect = $this->arrayIntersectRecursive($product, $productDataArray);

        $toRemove['variants'] = $this->getVariantsDiff($this->productVariantsById, $this->productVariantsDraftById, false);
//        var_dump($this->productVariantsById, $this->productVariantsDraftById);

        $toRemove['categories']=$this->categoriesToRemove($product['categories'], $productDataArray['categories']);



        $toAdd['variants'] = $this->getVariantsDiff($this->productVariantsById, $this->productVariantsDraftById);

        $toChange = $this->arrayDiffRecursive($productDataArray, $intersect);
        $toChange['categories']=$this->categoriesToAdd($product['categories'], $productDataArray['categories']);

        if (isset($product['taxCategory']) && isset($productDataArray['taxCategory'])) {
            $taxCategoryToChange=$this->taxCategoryDiff($product['taxCategory'], $productDataArray['taxCategory']);
            if ($taxCategoryToChange ==null) {
                unset($toChange['taxCategory']);
            }
        } elseif (isset($productDataArray['taxCategory'])) {
            $toChange['taxCategory']= $productDataArray['taxCategory'];
        } else {
            $toChange['taxCategory']=[];
        }


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
                    $action = ProductSetDescriptionAction::of();
                    if (!empty($productDraftArray[$heading])) {
                        $action->setDescription(LocalizedString::fromArray($productDraftArray[$heading]));
                    }
                    if (!empty($productDraftArray[$heading]) || !empty($product[$heading])) {
                        $actions[$heading] = $action;
                    }
                    break;
                case 'key':
                    $actions[$heading] = ProductSetKeyAction::ofKey(
                        $productDraftArray[$heading]
                    );
                    break;
                case 'taxCategory':
                    $action = ProductSetTaxCategoryAction::of();
                    if (!empty($productDraftArray[$heading])) {
                        $action->setTaxCategory($productDraftArray[$heading]);
                    }
                    if (!empty($productDraftArray[$heading]) || !empty($product[$heading])) {
                        $actions[$heading] = $action;
                    }
                    break;
                case 'categories':
                    foreach ($toChange[$heading] as $category) {
                        $actions[$heading.$category['id']] = ProductAddToCategoryAction::ofCategory(CategoryReference::fromArray($category));
                    }
                    break;
                case 'metaTitle':
                    $action = ProductSetMetaTitleAction::of();
                    if (!empty($productDraftArray[$heading])) {
                        $action->setMetaTitle(LocalizedString::fromArray($productDraftArray[$heading]));
                    }
                    if (!empty($productDraftArray[$heading]) || !empty($product[$heading])) {
                        $actions[$heading] = $action;
                    }
                    break;
                case 'metaDescription':
                    $action = ProductSetMetaDescriptionAction::of();
                    if (!empty($productDraftArray[$heading])) {
                        $action->setMetaDescription(LocalizedString::fromArray($productDraftArray[$heading]));
                    }
                    if (!empty($productDraftArray[$heading]) || !empty($product[$heading])) {
                        $actions[$heading] = $action;
                    }
                    break;
                case 'metaKeywords':
                    $action = ProductSetMetaKeywordsAction::of();
                    if (!empty($productDraftArray[$heading])) {
                        $action->setMetaKeywords(LocalizedString::fromArray($productDraftArray[$heading]));
                    }
                    if (!empty($productDraftArray[$heading]) || !empty($product[$heading])) {
                        $actions[$heading] = $action;
                    }
                    break;
                case "masterVariant":
                    $actions = array_merge_recursive(
                        $actions,
                        $this->getVariantActions($product['masterVariant'], $productDraftArray[$heading], $product['productType'])
                    );
                    break;

                case "variants":
                    if ($this->productVariantsById) {
                        foreach ($this->productVariantsById as $variant) {
                            if (isset($this->productVariantsDraftById[$variant['id']])) {//change sku to id
                                $actions = array_merge_recursive(
                                    $actions,
                                    $this->getVariantActions($variant, ProductVariantDraft::fromArray($this->productVariantsDraftById[$variant['id']]), $product['productType'])
                                );
                            }
                        }
                        $this->productVariantsBySku=[];  //free the array
                        $this->productVariantsDraftBySku = []; //free the array
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
                        if (isset($variant['id'])) {
                            $actions[$heading . 'remove' . $variant['id']] = ProductRemoveVariantAction::ofVariantId($variant['id']);
                        }
                    }
                    break;
            }
        }
        $request->setActions($actions);
//        print_r((string)$request->httpRequest()->getBody());
        return $request;
    }

    private function getProductVariantsById($productVariants)
    {
        $productVariantsById = [];
        foreach ($productVariants as $variant) {
            $productVariantsById[$variant['id']] = $variant;
            $this->productVariantsBySku[$variant['sku']] = $variant;
        }
        return $productVariantsById;
    }
    private function getDataVariantsById($ProductVariantDraftCollection)
    {
        /**
         * @var ProductVariantDraftCollection $variants
         */
        $variants= ProductVariantDraftCollection::fromArray($ProductVariantDraftCollection);
        $ProductVariantDraftCollection = $variants->toArray();

        $productVariantsDraftById = [];

        foreach ($ProductVariantDraftCollection as $variant) {
            $images=[];
            if (isset($variant['images'])) {
                foreach ($variant['images'] as $image) {
                    $images[] = $image;
                }

                $variant['images'] = $images;
            }
            $productVariantsDraftById[$variant['variantId']] = $variant;
            $this->productVariantsDraftBySku[$variant['sku']] = $variant;
//            unset($productVariantsDraftById[$variant['variantId']]['variantId']);
        }
        return $productVariantsDraftById;
    }
    private function getVariantsDiff($productVariants, $ProductVariantDraftCollection, $toAddFlag = true)
    {
        if ($toAddFlag) {
            $result=[];
            if ($ProductVariantDraftCollection) {
                foreach ($ProductVariantDraftCollection as $variant) {
                    if (!isset($productVariants[$variant['variantId']])) {//sku
                        $result[] = $variant;
                    }
                }
            }
        } else {
            $result=[];
            if ($productVariants) {
                foreach ($productVariants as $variant) {
                    if (!isset($ProductVariantDraftCollection[$variant['id']])) {// sku
                        $result[] = $variant;
                    }
                }
            }
        }

        return $result;
    }
    private function getPriceDiff($ProductPrices, $productVariantDraftPrices)
    {
        $this->productVariantDraftPricesByUniqueKey=[];
        $productVariantDraftPricesByUniqueKey=[];

        foreach ($productVariantDraftPrices as $price) {
            $keyParts = [];
            $priceObj=PriceDraft::fromArray($price->toArray());
            $price=$price->toArray();
            $keyParts[]=$price['value']['currencyCode'];
            if (isset($price['country'])) {
                $keyParts[]=$price['country'];
            }
            if (isset($price['customerGroup'])) {
                $keyParts[]=$price['customerGroup']['obj']['name'];
            }
            if (isset($price['channel'])) {
                $keyParts[]=$price['channel'];
            }
            $key=implode('-', $keyParts);
            $productVariantDraftPricesByUniqueKey[$key]=$price['value']['centAmount'];
            $this->productVariantDraftPricesByUniqueKey[$key]=$priceObj;
        }

        $ProductPricesById=[];
        $ProductPricesByUniqueKey=[];
        foreach ($ProductPrices as $price) {
            $keyParts = [];
            $keyParts[]=$price['value']['currencyCode'];
            if (isset($price['country'])) {
                $keyParts[]=$price['country'];
            }
            if (isset($price['customerGroup'])) {
                $keyParts[]=$this->customerGroups[$price['customerGroup']['id']];
            }
            if (isset($price['channel'])) {
                $keyParts[]=$price['channel'];
            }
            $key=implode('-', $keyParts);

            $ProductPricesById[$price['id']][$key]=$price['value']['centAmount'];
            $ProductPricesByUniqueKey[$key]=$price['value']['centAmount'];
        }

        $pricesToChange=[];
        foreach ($ProductPricesById as $id => $priceArray) {
            foreach ($priceArray as $key => $price) {
                if (isset($productVariantDraftPricesByUniqueKey[$key]) && $productVariantDraftPricesByUniqueKey[$key]!=$price) {
                    $pricesToChange[$id]= $priceArray;
                }
            }
        }
        $diffToReturn['toChange']=$pricesToChange;

        $pricesToRemove=[];
        foreach ($ProductPricesById as $id => $priceArray) {
            foreach ($priceArray as $key => $price) {
                if (!isset($productVariantDraftPricesByUniqueKey[$key])) {
                    $pricesToRemove[] = $id;
                }
            }
        }
        $diffToReturn['toRemove']=$pricesToRemove;

        $pricesToAdd=[];
        foreach ($productVariantDraftPricesByUniqueKey as $key => $value) {
            if (!isset($ProductPricesByUniqueKey[$key])) {
                   $pricesToAdd[] = $key;
            }
        }
        $diffToReturn['toAdd']=$pricesToAdd;
        return $diffToReturn;
    }

    private function getVariantActions($productVariant, $productVariantDraftArray, $productType)
    {
        $actions = [];
        $productAttributes = [];
        $productDraftAttributes = [];

        if (isset($productVariant['attributes'])) {
            foreach ($productVariant['attributes'] as $attribute) {
                $productAttributes[$attribute['name']] = $attribute;
            }
        }

        if (isset($productVariantDraftArray->toArray()['attributes'])) {
            foreach ($productVariantDraftArray->toArray()['attributes'] as $attribute) {
                $productDraftAttributes[$attribute['name']] = $attribute;
            }
        }

        $pricesDiff = $this->getPriceDiff($productVariant['prices'], $productVariantDraftArray->toArray()['prices']);

        $toChange = $this->arrayDiffRecursive($productAttributes, $productDraftAttributes);

        $generalDiffToChange= $this->arrayDiffRecursive($productVariant, $productVariantDraftArray->toArray());
        if (isset($generalDiffToChange['sku'])) {
            $toChange['sku']=$generalDiffToChange['sku'];
        }

        $imagesFromData=[];
        $imagesFromVariant = [];

        if (isset($productVariantDraftArray->toArray()['images'])) {
            $imagesFromData=$this->mapImages($productVariantDraftArray->toArray()['images'], true);
        }

        if (isset($productVariant['images'])) {
            $imagesFromVariant=$this->mapImages($productVariant['images']);
        }

        if (isset($pricesDiff['toChange'])) {
            $toChange['prices'] = $pricesDiff['toChange'];
        }

        $toRemove=[];
        if (isset($pricesDiff['toRemove'])) {
            $toRemove['prices'] = $pricesDiff['toRemove'];
        }


        $toRemove['images']= array_diff_key($imagesFromVariant, $imagesFromData);

        $toAdd=[];
        if (isset($pricesDiff['toAdd'])) {
            $toAdd['prices'] = $pricesDiff['toAdd'];
        }
        $generalDiffToAdd= $this->arrayDiffRecursive($productVariantDraftArray->toArray(), $productVariant);
        if (isset($generalDiffToAdd['sku'])) {
            $toAdd['sku']=$generalDiffToAdd['sku'];
        }

        $toAdd['images']= array_diff_key($imagesFromData, $imagesFromVariant);

        /**
         * @var ProductType $productType
         */
        $productType = $this->productTypes[$productType['id']];

        foreach ($toRemove as $key => $value) {
            switch ($key) {
                case "prices":
                    foreach ($value as $priceId) {
                        $actions[]= ProductRemovePriceAction::ofPriceId($priceId);
                    }
                    break;
                case 'images':
                    foreach ($value as $image) {
                        $actions[]= ProductRemoveImageAction::ofVariantIdAndImageUrl($productVariant['id'], $image['url']);
                    }
                    break;
            }
        }

        foreach ($toAdd as $key => $value) {
            switch ($key) {
                case "prices":
                    foreach ($value as $key) {
                        $actions[]= ProductAddPriceAction::ofVariantIdAndPrice($productVariant['id'], $this->productVariantDraftPricesByUniqueKey[$key]);
                    }
                    break;
                case "images":
                    foreach ($value as $image) {
                        $actions[]= ProductAddExternalImageAction::ofVariantIdAndImage($productVariant['id'], Image::fromArray($image));
                    }
                    break;
                case 'sku':
                    $action = ProductSetSkuAction::ofVariantId($productVariant['id']);
                    if (!empty($productVariantDraftArray->toArray()[$key])) {
                        $action->setSku($productVariantDraftArray->toArray()[$key]);
                    }
                    if (!empty($productVariantDraftArray->toArray()[$key]) || !empty($productVariant[$key])) {
                        $actions[$key] = $action;
                    }
                    break;
            }
        }
        foreach ($toChange as $key => $value) {
            switch ($key) {
                case "images":
                    break;
                case 'sku':
                    $action = ProductSetSkuAction::ofVariantId($productVariant['id']);
                    if (!empty($productVariantDraftArray->toArray()[$key])) {
                        $action->setSku($productVariantDraftArray->toArray()[$key]);
                    }
                    if (!empty($productVariantDraftArray->toArray()[$key]) || !empty($productVariant[$key])) {
                        $actions[$key] = $action;
                    }
                    var_dump($action);
                    break;
                case "prices":
                    foreach ($value as $id => $price) {
                        foreach ($price as $priceUniqueKey => $value) {
                            $actions[] =
                                ProductChangePriceAction::ofPriceIdAndPrice(
                                    $id,
                                    $this->productVariantDraftPricesByUniqueKey[$priceUniqueKey]
                                );
                        }
                    }
                    break;
                default:
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
        }
        return $actions;
    }

    private function mapImages($images, $imageFromData = false)
    {
        $imagesArray=[];
        foreach ($images as $image) {
            $keyParts = [];
            $keyParts[] = $image['url'];
            if ($imageFromData) {
                $keyParts[] = implode('-', $image['dimensions']->toArray());
            } else {
                $keyParts[] = implode('-', $image['dimensions']);
            }
            $imagesArray [implode('-', $keyParts)] = $image;
        }
        return $imagesArray;
    }
    private function mapVariantFromData($variantData, ProductType $productType)
    {
        $variantDraftArray= [];
        if (!isset($variantData['prices'])) {
            $variantData['prices']="";
        }
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
                    $variantDraftArray[$key] = $value;
                    break;
                case "sku":
                    $variantDraftArray[$key] = $value;
                    break;
                case "images":
                    $images=[];
                    $dimension= ImageDimension::fromArray(["w"=> 0, "h"=> 0]);
                    $value=explode(';', $value);
                    foreach ($value as $imageUrl) {
                        if ($imageUrl!='') {
                            $image['url'] = $imageUrl;
                            $image['dimensions'] = $dimension;
                            $images[]=$image;
                        }
                    }
                    $variantDraftArray[$key] = Image::fromArray($images);
                    break;
                case "searchKeywords":
                    break;
                case "prices":
                    $variantDraftArray[$key]=$this->mapPriceFtomData($value);
                    break;
                default:
                    if (!isset($variantDraftArray['attributes'])) {
                        $variantDraftArray['attributes'] = [];
                    }
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
    private function mapPriceFtomData($data)
    {
        $prices=[];
        $currencyAndPrices=explode(';', $data);
        foreach ($currencyAndPrices as $currencyAndPrice) {
            $price =[];
            $splittedcurrencyAndPrice=explode(' ', $currencyAndPrice);
            if (count($splittedcurrencyAndPrice)>=3) {
                $price['customerGroup'] = $this->customerGroups[$splittedcurrencyAndPrice[2]];
            }
            $countryCurrency=explode('-', $splittedcurrencyAndPrice[0]);
            if (count($countryCurrency)> 1) {
                $price['country']=$countryCurrency[0];
            } else {
                $money['currencyCode']=$countryCurrency[0];
            }
            if (count($splittedcurrencyAndPrice)>= 2) {
                $splitedPrice=explode('|', $splittedcurrencyAndPrice[1]);
                $money['centAmount']= intval($splitedPrice[0]);
                $price['value']=Money::fromArray($money);
                $prices[]= Price::fromArray($price);
            }
        }

        return $prices;
    }
    private function mapProductFromData($productData, $ignoreEmpty = false)
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
                    if (!$ignoreEmpty || !empty($value) && $value !== '') {
                        $productDraftArray[$key]= $value;
                    }
                    break;
                case "productType":
                    $productDraftArray[$key]= ProductTypeReference::ofKey($value);
                    break;
                case "tax":
                    $productDraftArray['taxCategory']= $this->taxCategories[$value];
                    break;
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
    private function taxCategoryDiff($productCategory, $dataCategory)
    {
        if ($productCategory['id'] != $dataCategory ['id']) {
            return $dataCategory;
        }
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
