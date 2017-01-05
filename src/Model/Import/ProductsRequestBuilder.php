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
use Commercetools\Core\Model\ProductType\SetType;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Model\TaxCategory\TaxCategoryCollection;
use Commercetools\Core\Request\Categories\CategoryQueryRequest;
use Commercetools\Core\Request\ClientRequestInterface;
use Commercetools\Core\Request\CustomerGroups\CustomerGroupQueryRequest;
use Commercetools\Core\Request\Payments\PaymentUpdateRequest;
use Commercetools\Core\Request\Products\Command\ProductAddExternalImageAction;
use Commercetools\Core\Request\Products\Command\ProductAddPriceAction;
use Commercetools\Core\Request\Products\Command\ProductAddToCategoryAction;
use Commercetools\Core\Request\Products\Command\ProductAddVariantAction;
use Commercetools\Core\Request\Products\Command\ProductChangeMasterVariantAction;
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
use Commercetools\Core\Request\Products\Command\ProductSetProductVariantKeyAction;
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
    const ID= 'id';
    const VALUE= 'value';
    const SKU= 'sku';
    const PRICES='prices';
    const IMAGES='images';
    const ATTRIBUTES='attributes';
    const NAME='name';
    const CATEGORIES='categories';
    const VARIANTS='variants';
    const MASTERVARIANT='masterVariant';
    const SLUG='slug';
    const DESCRIPTION='description';
    const KEY='key';
    const METATITLE='metaTitle';
    const METADESCRIPTION='metaDescription';
    const METAKEYWORDS='metaKeywords';
    const TAXCATEGORY='taxCategory';
    const CHANNEL='channel';
    const VARIANTKEY='variantKey';
    const PUBLISH='publish';
    const VARIANTID='variantId';
    const CUSTOMERGROUP='customerGroup';
    const CURRENCYCODE='currencyCode';
    const COUNTRY='country';
    const CENTAMOUNT='centAmount';
    const PRODUCTTYPE='productType';
    const TAX='tax';
    const DIMENSIONS='dimensions';
    const URL='url';
    const CREATIONDATE='creationDate';
    const SEARCHKEYWORDS='searchKeywords';
    const REFERENCE='reference';
    const ANCESTORS='ancestors';
    const TOCHANGE='toChange';
    const TOADD='toAdd';
    const TOREMOVE='toRemove';
    const VERSION='version';
    const OBJ='obj';

    private $categories;
    private $productTypes;
    private $client;
    private $productVariantsById;
    private $productVariantsDraftById;
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
         * @var CategoryCollection $categories ;
         */
        $catReferences = [];
        foreach ($categories as $category) {
            $catReferences[$category->getId()] = [
                self::NAME => (string)$category->getName(),
                self::REFERENCE => $category->getReference(),
                self::ANCESTORS => $category->getAncestors()
            ];
        }
        $catByPath = [];
        foreach ($catReferences as $categoryInfo) {
            $path = [];
            foreach ($categoryInfo[self::ANCESTORS] as $ancestor) {
                $path[] = $catReferences[$ancestor->getId()][self::NAME];
            }
            $path[] = $categoryInfo[self::NAME];
            $categoryPath = implode('>', $path);
            $catByPath[$categoryPath] = $categoryInfo[self::REFERENCE];
        }
        return $catByPath;
    }

    private function getProductTypes()
    {
        $request = ProductTypeQueryRequest::of();

        $helper = new QueryHelper();
        $productTypes = $helper->getAll($this->client, $request);


        /**
         * @var ProductTypeCollection $productTypes ;
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
         * @var CustomerGroupCollection $customerGroups ;
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
         * @var TaxCategoryCollection $taxCategories ;
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
     * @return ClientRequestInterface|null
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
            if (!$request->hasActions()) {
                $request = null;
            }
        } else {
            $request = $this->getCreateRequest($productData);
        }

        return $request;
    }

    private function createVariantAddRequest($variants)
    {
        $actions = [];
        foreach ($variants as $variant) {
            if ($variant[self::PRICES] == []) {
                unset($variant[self::PRICES]);
            }
            $actions[] = ProductAddVariantAction::fromArray($variant);
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

    private function getUpdateRequestsToAdd($toAdd)
    {
        $actions =[];
        foreach ($toAdd as $heading => $data) {
            switch ($heading) {
                case self::MASTERVARIANT:
                case self::VARIANTS:
                    $actions = array_merge_recursive(
                        $actions,
                        $this->createVariantAddRequest($data)
                    );
                    break;
            }
        }
        return $actions;
    }
    private function getUpdateRequestsToRemove($toRemove)
    {
        $actions=[];
        foreach ($toRemove as $heading => $data) {
            switch ($heading) {
                case self::CATEGORIES:
                    foreach ($toRemove[$heading] as $category) {
                        $actions[$heading.$category[self::ID]] = ProductRemoveFromCategoryAction::ofCategory(CategoryReference::fromArray($category));
                    }
                    break;
                case self::VARIANTS:
                    foreach ($data as $variant) {
                        if (isset($variant[self::ID])) {
                            $actions[$heading . 'remove' . $variant[self::ID]] = ProductRemoveVariantAction::ofVariantId($variant[self::ID]);
                        }
                    }
                    break;
            }
        }
        return $actions;
    }
    private function getUpdateRequestsToChange($toChange, $productDraftArray, $product)
    {
        $actions=[];
        foreach ($toChange as $heading => $data) {
            switch ($heading) {
                case self::NAME:
                    $actions[$heading] = ProductChangeNameAction::ofName(
                        LocalizedString::fromArray($productDraftArray[$heading])
                    );
                    break;
                case self::SLUG:
                    $actions[$heading] = ProductChangeSlugAction::ofSlug(
                        LocalizedString::fromArray($productDraftArray[$heading])
                    );
                    break;
                case self::DESCRIPTION:
                    $action = ProductSetDescriptionAction::of();
                    if (!empty($productDraftArray[$heading])) {
                        $action->setDescription(LocalizedString::fromArray($productDraftArray[$heading]));
                    }
                    if (!empty($productDraftArray[$heading]) || !empty($product[$heading])) {
                        $actions[$heading] = $action;
                    }
                    break;
                case self::KEY:
                    $action = ProductSetKeyAction::of();
                    if (!empty($productDraftArray[$heading])) {
                        $action->setKey($productDraftArray[$heading]);
                    }
                    if (!empty($productDraftArray[$heading]) || !empty($product[$heading])) {
                        $actions[$heading] = $action;
                    }
                    break;
                case self::TAXCATEGORY:
                    $action = ProductSetTaxCategoryAction::of();
                    if (!empty($productDraftArray[$heading])) {
                        $action->setTaxCategory($productDraftArray[$heading]);
                    }
                    if (!empty($productDraftArray[$heading]) || !empty($product[$heading])) {
                        $actions[$heading] = $action;
                    }
                    break;
                case self::CATEGORIES:
                    foreach ($toChange[$heading] as $category) {
                        $actions[$heading.$category[self::ID]] = ProductAddToCategoryAction::ofCategory(CategoryReference::fromArray($category));
                    }
                    break;
                case self::METATITLE:
                    $action = ProductSetMetaTitleAction::of();
                    if (!empty($productDraftArray[$heading])) {
                        $action->setMetaTitle(LocalizedString::fromArray($productDraftArray[$heading]));
                    }
                    if (!empty($productDraftArray[$heading]) || !empty($product[$heading])) {
                        $actions[$heading] = $action;
                    }
                    break;
                case self::METADESCRIPTION:
                    $action = ProductSetMetaDescriptionAction::of();
                    if (!empty($productDraftArray[$heading])) {
                        $action->setMetaDescription(LocalizedString::fromArray($productDraftArray[$heading]));
                    }
                    if (!empty($productDraftArray[$heading]) || !empty($product[$heading])) {
                        $actions[$heading] = $action;
                    }
                    break;
                case self::METAKEYWORDS:
                    $action = ProductSetMetaKeywordsAction::of();
                    if (!empty($productDraftArray[$heading])) {
                        $action->setMetaKeywords(LocalizedString::fromArray($productDraftArray[$heading]));
                    }
                    if (!empty($productDraftArray[$heading]) || !empty($product[$heading])) {
                        $actions[$heading] = $action;
                    }
                    break;
                case self::MASTERVARIANT:
                    //if empty masterVariant break;
                    if (count($product[self::MASTERVARIANT])==2
                        && $product[self::MASTERVARIANT][self::KEY]==""
                        && $product[self::MASTERVARIANT][self::PRICES]==[]) {
                        break;
                    }
                    $productDraftArray[$heading]=$productDraftArray[$heading]->toArray();
                    if (!isset($productDraftArray[$heading][self::VARIANTKEY])) {
                        $productDraftArray[$heading][self::VARIANTKEY]="";

                    }
                    $product[self::MASTERVARIANT]=$this->getProductVariantsById([$product[self::MASTERVARIANT]])[$product[self::MASTERVARIANT][self::ID]];
                    $productDraftArray[$heading]=$this->getDataVariantsById([$productDraftArray[$heading]])[$productDraftArray[self::MASTERVARIANT][self::VARIANTID]];
                    $actions = array_merge_recursive(
                        $actions,
                        $this->getVariantActions($product[self::MASTERVARIANT], $productDraftArray[$heading], $product[self::PRODUCTTYPE])
                    );

                    break;
                case self::VARIANTS:
                    if ($this->productVariantsById) {
                        foreach ($this->productVariantsById as $variant) {
                            if (isset($this->productVariantsDraftById[$variant[self::ID]])) {//change sku to id
                                $actions = array_merge_recursive(
                                    $actions,
                                    $this->getVariantActions($variant, $this->productVariantsDraftById[$variant[self::ID]], $product[self::PRODUCTTYPE])
                                );
                            }
                        }
                    }
                    break;
            }

        }
        return $actions;
    }
    private function getProductItemsToChange($productDataArray, $product)
    {
        if (!isset($productDataArray[self::KEY])) {
            $productDataArray[self::KEY]="";
        }
        $intersect = $this->arrayIntersectRecursive($product, $productDataArray);
        $toChange = $this->arrayDiffRecursive($productDataArray, $intersect);
        $toChange[self::CATEGORIES]=$this->categoriesToAdd($product[self::CATEGORIES], $productDataArray[self::CATEGORIES]);

        if (isset($product[self::TAXCATEGORY]) && isset($productDataArray[self::TAXCATEGORY])) {
            $taxCategoryToChange=$this->taxCategoryDiff($product[self::TAXCATEGORY], $productDataArray[self::TAXCATEGORY]);
            if ($taxCategoryToChange ==null) {
                unset($toChange[self::TAXCATEGORY]); //to avoid unnecessary action
            }
        } elseif (isset($productDataArray[self::TAXCATEGORY])) {
            $toChange[self::TAXCATEGORY]= $productDataArray[self::TAXCATEGORY];
        } else {
            $toChange[self::TAXCATEGORY]=[];
        }
        return $toChange;
    }
    private function getUpdateRequest(ProductProjection $product, $productData)
    {
        $productDraftArray = $this->mapProductFromData($productData);
        $productDataDraft = ProductDraft::fromArray($productDraftArray);
        $productDataArray= $productDataDraft->toArray();

        if (!isset($productDataArray[self::VARIANTS])) {
            $productDataArray[self::VARIANTS] = [];
        }
        if (!isset($productDataArray[self::CATEGORIES])) {
            $productDataArray[self::CATEGORIES] = [];
        }


        $product = $product->toArray();
        if (!isset($product[self::CATEGORIES])) {
            $product[self::CATEGORIES] = [];
        }
        if (!isset($product[self::MASTERVARIANT][self::KEY])) {
            $product[self::MASTERVARIANT][self::KEY]="";
        }
        if (!isset($product[self::MASTERVARIANT][self::PRICES])) {
            $product[self::MASTERVARIANT][self::PRICES]=[];
        }
        if (isset($product[self::MASTERVARIANT][self::ATTRIBUTES])) {
            foreach ($product[self::MASTERVARIANT][self::ATTRIBUTES] as &$attribute) {
                if (isset($attribute[self::VALUE][self::KEY])) {
                    $attribute[self::VALUE] = $attribute[self::VALUE][self::KEY];
                }
            }
        }
        if (isset($productDraftArray[self::MASTERVARIANT])) {
            if (!isset($productDraftArray[self::MASTERVARIANT]->toArray()[self::VARIANTKEY])) {
                $productDraftArray[self::MASTERVARIANT]->toArray()[self::VARIANTKEY] = "";
            }
            if (count($product[self::MASTERVARIANT])==2
                && $product[self::MASTERVARIANT][self::KEY]==""
                && $product[self::MASTERVARIANT][self::PRICES]==[]) {
                $toAdd[self::MASTERVARIANT] =  [$productDraftArray[self::MASTERVARIANT]->toArray()];
            }
        }
        if (isset($product[self::VARIANTS])) {
            foreach ($product[self::VARIANTS] as &$variant) {
                if (!isset($variant[self::PRICES])) {
                    $variant[self::PRICES]=[];
                }
                if (!isset($variant[self::KEY])) {
                    $variant[self::KEY]="";
                }
                if (isset($variant[self::ATTRIBUTES])) {
                    foreach ($variant[self::ATTRIBUTES] as &$attribute) {
                        if (isset($attribute[self::VALUE][self::KEY])) {
                            $attribute[self::VALUE] = $attribute[self::VALUE][self::KEY];
                        }
                    }
                }
            }
            $this->productVariantsById = $this->getProductVariantsById($product[self::VARIANTS]);
        }


        $this->productVariantsDraftById = $this->getDataVariantsById($productDataArray[self::VARIANTS]);

        $toRemove[self::VARIANTS] = $this->getVariantsDiff($this->productVariantsById, $this->productVariantsDraftById, false);
        $toRemove[self::CATEGORIES]=$this->categoriesToRemove($product[self::CATEGORIES], $productDataArray[self::CATEGORIES]);

        $toAdd[self::VARIANTS] = $this->getVariantsDiff($this->productVariantsById, $this->productVariantsDraftById);

        $toChange =$this->getProductItemsToChange($productDataArray, $product);

        $request = ProductUpdateRequest::ofIdAndVersion($product[self::ID], $product[self::VERSION]);

        $actions=[];

        $actions = array_merge_recursive($actions, $this->getUpdateRequestsToAdd($toAdd));
        $actions = array_merge_recursive($actions, $this->getUpdateRequestsToRemove($toRemove));
        $actions = array_merge_recursive($actions, $this->getUpdateRequestsToChange($toChange, $productDraftArray, $product));

        $request->setActions($actions);
//        print_r((string)$request->httpRequest()->getBody());
        return $request;
    }

    private function getProductVariantsById($productVariants)
    {
        $productVariantsById = [];
        foreach ($productVariants as $variant) {
            if (isset($variant[self::IMAGES])) {
                $variant[self::IMAGES]=$this->mapImages($variant[self::IMAGES]);
            }
            $attributes=[];
            if (isset($variant[self::ATTRIBUTES])) {
                foreach ($variant[self::ATTRIBUTES] as $attribute) {
                    $attributes[$attribute[self::NAME]] = $attribute;
                }
            }
            $variant[self::ATTRIBUTES]=$attributes;
            $productVariantsById[$variant[self::ID]] = $variant;
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
            if (isset($variant[self::IMAGES])) {
                foreach ($variant[self::IMAGES] as $image) {
                    $images[] = $image;
                }
                $variant[self::IMAGES]=$this->mapImages($images, true);
            }
            if (!isset($variant[self::VARIANTKEY])) {
                $variant[self::VARIANTKEY]="";
            }
            $attributes=[];
            if (isset($variant[self::ATTRIBUTES])) {
                foreach ($variant[self::ATTRIBUTES] as $attribute) {
                    $attributes[$attribute[self::NAME]] = $attribute;
                }
            }
            $variant[self::ATTRIBUTES]=$attributes;
            $productVariantsDraftById[$variant[self::VARIANTID]] = $variant;
        }

        return $productVariantsDraftById;
    }

    private function getPriceToAdd($productVariantDraftPricesByUniqueKey, $ProductPricesByUniqueKey)
    {
        $pricesToAdd=[];
        foreach ($productVariantDraftPricesByUniqueKey as $key => $value) {
            if (!isset($ProductPricesByUniqueKey[$key])) {
                $pricesToAdd[] = $key;
            }
        }
        return $pricesToAdd;
    }
    private function getPriceToRemove($productVariantDraftPricesByUniqueKey, $ProductPricesById)
    {
        $pricesToRemove=[];
        foreach ($ProductPricesById as $id => $priceArray) {
            foreach ($priceArray as $key => $price) {
                if (!isset($productVariantDraftPricesByUniqueKey[$key])) {
                    $pricesToRemove[] = $id;
                }
            }
        }
        return $pricesToRemove;
    }
    private function getPriceToChange($productVariantDraftPricesByUniqueKey, $ProductPricesById)
    {
        $pricesToChange=[];
        foreach ($ProductPricesById as $id => $priceArray) {
            foreach ($priceArray as $key => $price) {
                if (isset($productVariantDraftPricesByUniqueKey[$key]) && $productVariantDraftPricesByUniqueKey[$key]!=$price) {
                    $pricesToChange[$id]= $priceArray;
                }
            }
        }
        return $pricesToChange;
    }
    private function getProductVariantDraftPricesByUniqueKey($productVariantDraftPrices)
    {
        $this->productVariantDraftPricesByUniqueKey=[]; // this var used in updating and changing prices
        $productVariantDraftPricesByUniqueKey=[];

        foreach ($productVariantDraftPrices as $price) {
            $keyParts = [];
            $priceObj=PriceDraft::fromArray($price->toArray());
            $price=$price->toArray();
            $keyParts[]=$price[self::VALUE][self::CURRENCYCODE];
            if (isset($price[self::COUNTRY])) {
                $keyParts[]=$price[self::COUNTRY];
            }
            if (isset($price[self::CUSTOMERGROUP])) {
                $keyParts[]=$price[self::CUSTOMERGROUP][self::OBJ][self::NAME];
            }
            if (isset($price[self::CHANNEL])) {
                $keyParts[]=$price[self::CHANNEL];
            }
            $key=implode('-', $keyParts);
            $productVariantDraftPricesByUniqueKey[$key]=$price[self::VALUE][self::CENTAMOUNT];
            $this->productVariantDraftPricesByUniqueKey[$key]=$priceObj;
        }
        return $productVariantDraftPricesByUniqueKey;
    }
    private function getProductPricesByUniqueKeyAndId($ProductPrices)
    {
        $ProductPricesById=[];
        $ProductPricesByUniqueKey=[];
        foreach ($ProductPrices as $price) {
            $keyParts = [];
            $keyParts[]=$price[self::VALUE][self::CURRENCYCODE];
            if (isset($price[self::COUNTRY])) {
                $keyParts[]=$price[self::COUNTRY];
            }
            if (isset($price[self::CUSTOMERGROUP])) {
                $keyParts[]=$this->customerGroups[$price[self::CUSTOMERGROUP][self::ID]];
            }
            if (isset($price[self::CHANNEL])) {
                $keyParts[]=$price[self::CHANNEL];
            }
            $key=implode('-', $keyParts);

            $ProductPricesById[$price[self::ID]][$key]=$price[self::VALUE][self::CENTAMOUNT];
            $ProductPricesByUniqueKey[$key]=$price[self::VALUE][self::CENTAMOUNT];
        }
        $formattedProductPrices[self::ID]=$ProductPricesById;
        $formattedProductPrices[self::KEY]=$ProductPricesByUniqueKey;
        return $formattedProductPrices;
    }
    private function getPriceDiff($ProductPrices, $productVariantDraftPrices)
    {
        $productVariantDraftPricesByUniqueKey = $this->getProductVariantDraftPricesByUniqueKey($productVariantDraftPrices);
        $formattedProductPrices = $this->getProductPricesByUniqueKeyAndId($ProductPrices);

        $priceDiff[self::TOCHANGE]=$this->getPriceToChange($productVariantDraftPricesByUniqueKey, $formattedProductPrices[self::ID]);
        $priceDiff[self::TOREMOVE]=$this->getPriceToRemove($productVariantDraftPricesByUniqueKey, $formattedProductPrices[self::ID]);
        $priceDiff[self::TOADD]=$this->getPriceToAdd($productVariantDraftPricesByUniqueKey, $formattedProductPrices[self::KEY]);
        return $priceDiff;
    }

    private function getVariantsDiff($productVariants, $ProductVariantDraftCollection, $toAddFlag = true)
    {
        if ($toAddFlag) {
            $result=[];
            if ($ProductVariantDraftCollection) {
                foreach ($ProductVariantDraftCollection as $variant) {
                    if (!isset($productVariants[$variant[self::VARIANTID]])) {//sku
                        if (isset($variant[self::ATTRIBUTES])) {
                            $variant[self::ATTRIBUTES] = array_values($variant[self::ATTRIBUTES]);
                        }
                        if (isset($variant[self::IMAGES])) {
                            $variant[self::IMAGES] = array_values($variant[self::IMAGES]);
                        }
                        $result[] = $variant;
                    }
                }
            }
        } else {
            $result=[];
            if ($productVariants) {
                foreach ($productVariants as $variant) {
                    if (!isset($ProductVariantDraftCollection[$variant[self::ID]])) {// sku
                        if (isset($variant[self::ATTRIBUTES])) {
                            $variant[self::ATTRIBUTES] = array_values($variant[self::ATTRIBUTES]);
                        }
                        if (isset($variant[self::IMAGES])) {
                            $variant[self::IMAGES] = array_values($variant[self::IMAGES]);
                        }
                        $result[] = $variant;
                    }
                }
            }
        }

        return $result;
    }
    private function getVariantRemoveActions($toRemove, $variantId)
    {
        $actions =[];
        foreach ($toRemove as $key => $value) {
            switch ($key) {
                case self::PRICES:
                    foreach ($value as $priceId) {
                        $actions[]= ProductRemovePriceAction::ofPriceId($priceId);
                    }
                    break;
                case self::IMAGES:
                    foreach ($value as $image) {
                        $actions[]= ProductRemoveImageAction::ofVariantIdAndImageUrl($variantId, $image[self::URL]);
                    }
                    break;
            }
        }
        return $actions;
    }
    private function getVariantAddActions($toAdd, $productVariantDraftArray, $variantId)
    {
        $actions=[];
        foreach ($toAdd as $key => $value) {
            switch ($key) {
                case self::PRICES:
                    foreach ($value as $key) {
                        $actions[]= ProductAddPriceAction::ofVariantIdAndPrice($variantId, $this->productVariantDraftPricesByUniqueKey[$key]);
                    }
                    break;
                case self::IMAGES:
                    foreach ($value as $image) {
                        $actions[]= ProductAddExternalImageAction::ofVariantIdAndImage($variantId, Image::fromArray($image));
                    }
                    break;
                case self::SKU:
                    $action = ProductSetSkuAction::ofVariantId($variantId);
                    if (!empty($productVariantDraftArray[$key])) {
                        $action->setSku($productVariantDraftArray[$key]);
                    }
                    if (!empty($productVariantDraftArray[$key]) || !empty($productVariant[$key])) {
                        $actions[] = $action;
                    }
                    break;
            }
        }
        return $actions;
    }
    private function getVariantChangeActions($toChange, $productVariantDraftArray, $productVariant, $productType)
    {
        $productDraftAttributes=[];
        if (isset($productVariantDraftArray[self::ATTRIBUTES])) {
            $productDraftAttributes = $productVariantDraftArray[self::ATTRIBUTES];
        }
        $actions=[];
        foreach ($toChange as $key => $value) {
            switch ($key) {
                case self::IMAGES:
                    break;
                case self::SKU:
                    $action = ProductSetSkuAction::ofVariantId($productVariant[self::ID]);
                    if (!empty($productVariantDraftArray[$key])) {
                        $action->setSku($productVariantDraftArray[$key]);
                    }
                    if (!empty($productVariantDraftArray[$key]) || !empty($productVariant[$key])) {
                        $actions[] = $action;
                    }
                    break;
                case self::VARIANTKEY:
                    $action = ProductSetProductVariantKeyAction::of()
                        ->setVariantId($productVariant[self::ID]);
                    if (!empty($productVariantDraftArray[$key])) {
                        $action->setKey($productVariantDraftArray[$key]);
                    }
                    $actions[] = $action;
                    break;
                case self::PRICES:
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
                        $action = ProductSetAttributeAction::ofVariantIdAndName($productVariant[self::ID], $key);
                    }

                    if (isset($productDraftAttributes[$key][self::VALUE])) {
                        $action->setValue($productDraftAttributes[$key][self::VALUE]);
                    }
                    $actions['variant' . $productVariant[self::ID] . $key] = $action;
            }
        }
        return $actions;
    }
    private function getVariantItemsToChange($productVariantDraftArray, $productVariant)
    {
        $productDraftAttributes = [];
        $productAttributes =[];
        $pricesDiff = [];

        if (isset($productVariantDraftArray[self::ATTRIBUTES])) {
            $productDraftAttributes = $productVariantDraftArray[self::ATTRIBUTES];
        }
        if (isset($productVariant[self::ATTRIBUTES])) {
            $productAttributes = $productVariant[self::ATTRIBUTES];
        }
        if (isset($productVariant[self::PRICES]) && isset($productVariantDraftArray[self::PRICES])) {
            $pricesDiff = $this->getPriceDiff($productVariant[self::PRICES], $productVariantDraftArray[self::PRICES]);
        }
        $toChange = array_merge(
            $this->arrayDiffRecursive($productAttributes, $productDraftAttributes),
            $this->arrayDiffRecursive($productDraftAttributes, $productAttributes)
        );
        if ($productVariantDraftArray[self::VARIANTKEY] != $productVariant[self::KEY]) {
            $toChange[self::VARIANTKEY]=$productVariantDraftArray[self::VARIANTKEY];
        }

        if (isset($pricesDiff[self::TOCHANGE])) {
            $toChange[self::PRICES] = $pricesDiff[self::TOCHANGE];
        }

        $generalDiffToChange= $this->arrayDiffRecursive($productVariant, $productVariantDraftArray);
        if (isset($generalDiffToChange[self::SKU])) {
            $toChange[self::SKU]=$generalDiffToChange[self::SKU];
        }
        return $toChange;
    }
    private function getVariantItemsToAdd($productVariantDraftArray, $productVariant)
    {
        $imagesFromData=[];
        $imagesFromVariant = [];

        if (isset($productVariantDraftArray[self::IMAGES])) {
            $imagesFromData = $productVariantDraftArray[self::IMAGES];
        }
        if (isset($productVariant[self::IMAGES])) {
            $imagesFromVariant = $productVariant[self::IMAGES];
        }
        if (isset($productVariant[self::PRICES]) && isset($productVariantDraftArray[self::PRICES])) {
            $pricesDiff = $this->getPriceDiff($productVariant[self::PRICES], $productVariantDraftArray[self::PRICES]);
        }

        $toAdd=[];
        if (isset($pricesDiff[self::TOADD])) {
            $toAdd[self::PRICES] = $pricesDiff[self::TOADD];
        }
        $generalDiffToAdd= array_diff_key($productVariantDraftArray, $productVariant);
        if (isset($generalDiffToAdd[self::SKU])) {
            $toAdd[self::SKU]=$generalDiffToAdd[self::SKU];
        }
        $toAdd[self::IMAGES]= array_diff_key($imagesFromData, $imagesFromVariant);
        return $toAdd;
    }
    private function getVariantItemsToRemove($productVariantDraftArray, $productVariant)
    {
        $imagesFromData=[];
        $imagesFromVariant = [];
        $pricesDiff=[];

        if (isset($productVariantDraftArray[self::IMAGES])) {
            $imagesFromData = $productVariantDraftArray[self::IMAGES];
        }
        if (isset($productVariant[self::IMAGES])) {
            $imagesFromVariant = $productVariant[self::IMAGES];
        }
        if (isset($productVariant[self::PRICES]) && isset($productVariantDraftArray[self::PRICES])) {
            $pricesDiff = $this->getPriceDiff($productVariant[self::PRICES], $productVariantDraftArray[self::PRICES]);
        }

        $toRemove=[];
        if (isset($pricesDiff[self::TOREMOVE])) {
            $toRemove[self::PRICES] = $pricesDiff[self::TOREMOVE];
        }
        $toRemove[self::IMAGES]= array_diff_key($imagesFromVariant, $imagesFromData);

        return $toRemove;
    }
    private function getVariantActions($productVariant, $productVariantDraftArray, $productType)
    {
        $actions = [];

        $toChange = $this->getVariantItemsToChange($productVariantDraftArray, $productVariant);
        $toRemove= $this->getVariantItemsToRemove($productVariantDraftArray, $productVariant);
        $toAdd=$this->getVariantItemsToAdd($productVariantDraftArray, $productVariant);

        /**
         * @var ProductType $productType
         */
        $productType = $this->productTypes[$productType[self::ID]];

        $actions = array_merge_recursive($actions, $this->getVariantRemoveActions($toRemove, $productVariant[self::ID]));
        $actions = array_merge_recursive($actions, $this->getVariantAddActions($toAdd, $productVariantDraftArray, $productVariant[self::ID]));
        $actions = array_merge_recursive(
            $actions,
            $this->getVariantChangeActions(
                $toChange,
                $productVariantDraftArray,
                $productVariant,
                $productType
            )
        );

        return $actions;
    }

    private function mapImages($images, $imageFromData = false)
    {
        $imagesArray=[];
        foreach ($images as $image) {
            $keyParts = [];
            $keyParts[] = $image[self::URL];
            if ($imageFromData) {
                $keyParts[] = implode('-', $image[self::DIMENSIONS]->toArray());
            } else {
                $keyParts[] = implode('-', $image[self::DIMENSIONS]);
            }
            $imagesArray[implode('-', $keyParts)] = $image;
        }
        return $imagesArray;
    }
    private function mapVariantFromData($variantData, ProductType $productType)
    {
        $variantDraftArray= [];
        if (!isset($variantData[self::PRICES])) {
            $variantData[self::PRICES]="";
        }
        foreach ($variantData as $key => $value) {
            switch ($key) {
                case self::METATITLE:
                case self::METADESCRIPTION:
                case self::METAKEYWORDS:
                case self::NAME:
                case self::KEY:
                case self::SLUG:
                case self::DESCRIPTION:
                case self::PUBLISH:
                case self::TAX:
                case self::CATEGORIES:
                case self::PRODUCTTYPE:
                case self::ID:
                case self::CREATIONDATE:
                    break;
                case self::VARIANTKEY:
                case self::VARIANTID:
                    $variantDraftArray[$key] = $value;
                    break;
                case self::SKU:
                    $variantDraftArray[$key] = $value;
                    break;
                case self::IMAGES:
                    $images=[];
                    $dimension= ImageDimension::fromArray(["w"=> 0, "h"=> 0]);
                    $value=explode(';', $value);
                    foreach ($value as $imageUrl) {
                        if ($imageUrl!='') {
                            $image[self::URL] = $imageUrl;
                            $image[self::DIMENSIONS] = $dimension;
                            $images[]=$image;
                        }
                    }
                    $variantDraftArray[$key] = Image::fromArray($images);
                    break;
                case self::SEARCHKEYWORDS:
                    break;
                case self::PRICES:
                    $variantDraftArray[$key]=$this->mapPriceFtomData($value);
                    break;
                default:
                    if (!isset($variantDraftArray[self::ATTRIBUTES])) {
                        $variantDraftArray[self::ATTRIBUTES] = [];
                    }
                    if (!is_null($value) && $value !== '') {
                        $attributeDefinition = $productType->getAttributes()->getByName($key);
                        if ($attributeDefinition) {
                            $attributeType = $attributeDefinition->getType();
                            switch (true) {
                                case $attributeType instanceof SetType:
                                    if (!is_array($value)) {
                                        $value = explode(';', $value);
                                    }
                                    break;
                                case $attributeType instanceof BooleanType:
                                    $value = $value == 'true' ? true: false;
                                    break;
                            }
                            if ($value) {
                                $variantDraftArray[self::ATTRIBUTES][] = [self::NAME => $key, self::VALUE => $value];
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
                $price[self::CUSTOMERGROUP] = $this->customerGroups[$splittedcurrencyAndPrice[2]];
            }
            $countryCurrency=explode('-', $splittedcurrencyAndPrice[0]);
            if (count($countryCurrency)> 1) {
                $price[self::COUNTRY]=$countryCurrency[0];
            } else {
                $money[self::CURRENCYCODE]=$countryCurrency[0];
            }
            if (count($splittedcurrencyAndPrice)>= 2) {
                $splitedPrice=explode('|', $splittedcurrencyAndPrice[1]);
                $money[self::CENTAMOUNT]= intval($splitedPrice[0]);
                $price[self::VALUE]=Money::fromArray($money);
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
                case self::METATITLE:
                case self::METADESCRIPTION:
                case self::METAKEYWORDS:
                case self::KEY:
                case self::NAME:
                case self::SLUG:
                case self::DESCRIPTION:
                case self::PUBLISH:
                    if (!$ignoreEmpty || !empty($value) && $value !== '') {
                        $productDraftArray[$key]= $value;
                    }
                    break;
                case self::PRODUCTTYPE:
                    $productDraftArray[$key]= ProductTypeReference::ofKey($value);
                    break;
                case self::TAX:
                    $productDraftArray[self::TAXCATEGORY]= $this->taxCategories[$value];
                    break;
                case "state":
                    $productDraftArray[$key]= StateReference::ofKey($value);
                    break;
                case self::CATEGORIES:
                    $categories = CategoryReferenceCollection::of();
                    $productCategories= explode(';', $value);
                    foreach ($productCategories as $category) {
                        if (isset($this->categories[$category])) {
                            $categories->add($this->categories[$category]);
                        }
                    }
                    $productDraftArray[$key]= $categories;
                    break;
                case self::VARIANTS:
                    $variants=[];
                    foreach ($value as $variant) {
                        $variantData = $this->mapVariantFromData($variant, $this->productTypes[$productData[self::PRODUCTTYPE]]);
                        if ($variantData[self::VARIANTID] === '1') {
                            $productDraftArray[self::MASTERVARIANT]= ProductVariantDraft::fromArray($variantData);
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
            case self::SLUG:
                $value = $parts[0].'('.$parts[1]. $query . ')';
                break;
            case self::SKU:
                $value = self::MASTERVARIANT.'('.self::SKU.'= "%1$s") or '. self::VARIANTS.'('.self::SKU.'= "%1$s")';
                break;
            case self::KEY:
            case self::ID:
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
            case self::SLUG:
                $value = $row[$parts[0]][$parts[1]];
                break;
            case self::SKU:
            case self::KEY:
            case self::ID:
                $value = $row[$parts[0]];
                break;
        }
        return $value;
    }

    private function categoriesToAdd($productCategories, $dataCategories)
    {
        $toAdd=[];
        foreach ($dataCategories as $category) {
            if (!$this->searchArray($category[self::ID], $productCategories)) {
                $toAdd []= $category;
            }
        }
        return $toAdd;
    }
    private function taxCategoryDiff($productCategory, $dataCategory)
    {
        if ($productCategory[self::ID] != $dataCategory [self::ID]) {
            return $dataCategory;
        }
    }
    private function categoriesToRemove($productCategories, $dataCategories)
    {
        $toRemove=[];
        foreach ($productCategories as $category) {
            if (!$this->searchArray($category[self::ID], $dataCategories)) {
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
