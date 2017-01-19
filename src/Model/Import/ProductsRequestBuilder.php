<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 21/11/16
 * Time: 11:48
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Symfony\CtpBundle\Model\Import\VariantData; // Todo dont remove raise exception (it is a symfony issue)
use Commercetools\Commons\Helper\QueryHelper;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Category\CategoryReference;
use Commercetools\Core\Model\Product\ProductDraft;
use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Model\ProductType\ProductTypeCollection;
use Commercetools\Core\Request\ClientRequestInterface;
use Commercetools\Core\Request\Products\Command\ProductAddToCategoryAction;
use Commercetools\Core\Request\Products\Command\ProductAddVariantAction;
use Commercetools\Core\Request\Products\Command\ProductChangeNameAction;
use Commercetools\Core\Request\Products\Command\ProductChangeSlugAction;
use Commercetools\Core\Request\Products\Command\ProductRemoveFromCategoryAction;
use Commercetools\Core\Request\Products\Command\ProductRemoveVariantAction;
use Commercetools\Core\Request\Products\Command\ProductSetDescriptionAction;
use Commercetools\Core\Request\Products\Command\ProductSetKeyAction;
use Commercetools\Core\Request\Products\Command\ProductSetMetaDescriptionAction;
use Commercetools\Core\Request\Products\Command\ProductSetMetaKeywordsAction;
use Commercetools\Core\Request\Products\Command\ProductSetMetaTitleAction;
use Commercetools\Core\Request\Products\Command\ProductSetTaxCategoryAction;
use Commercetools\Core\Request\Products\ProductCreateRequest;
use Commercetools\Core\Request\Products\ProductProjectionQueryRequest;
use Commercetools\Core\Request\Products\ProductUpdateRequest;
use Commercetools\Core\Request\ProductTypes\ProductTypeQueryRequest;
use Commercetools\Core\Model\Common\LocalizedString;

class ProductsRequestBuilder extends AbstractRequestBuilder
{
    const ID= 'id';
    const VALUE= 'value';
    const SKU= 'sku';
    const PRICES='prices';
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
    const VARIANTKEY='variantKey';
    const VARIANTID='variantId';
    const PRODUCTTYPE='productType';
    const TAX='tax';
    const VERSION='version';

    private $productDataObj;
    private $variantDataObj;
    private $productTypes;
    private $productDraftArray;
    private $product;
    private $client;
    private $productVariantsById;
    private $productVariantsDraftById;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->variantDataObj = new variantData($client);
        $this->productDataObj = new ProductData($client, $this->variantDataObj);
        $this->productTypes = $this->getProductTypes();
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
    private function getProductsByIdentifiedByColumn($products, $identifiedByColumn)
    {
        $parts = explode('.', $identifiedByColumn);
        $productsArr=[];
        foreach ($products as $product) {
            switch ($parts[0]) {
                case self::SKU:
                    if (isset($product->toArray()[self::MASTERVARIANT])) {
                        $productsArr[$product->toArray()[self::MASTERVARIANT][$identifiedByColumn]] = $product;
                    } else {
                        $productsArr[$product->toArray()[self::VARIANTS][0][$identifiedByColumn]] = $product;
                    }
                    break;
                case self::ID:
                case self::KEY:
                    $productsArr[$product->toArray()[$identifiedByColumn]] = $product;
                    break;
                case self::SLUG:
                    $productsArr[$product->toArray()[$parts[0]][$parts[1]]] = $product;
                    break;
            }
        }
        return $productsArr;
    }
    private function getProductsDataByIdentifiedByColumn($productsData, $identifiedByColumn)
    {
        $productsDataArr=[];
        $parts = explode('.', $identifiedByColumn);
        foreach ($productsData as $productData) {
            switch ($parts[0]) {
                case self::SKU:
                case self::ID:
                case self::KEY:
                    $productsDataArr[$productData[$identifiedByColumn]] = $productData;
                    break;
                case self::SLUG:
                    $productsDataArr[$productData[$parts[0]][$parts[1]]] = $productData;
                    break;
            }
        }
        return $productsDataArr;
    }
    /**
     * @param $productsData
     * @param $identifiedByColumn
     * @return ClientRequestInterface[]|null
     */
    public function createRequest($productsData, $identifiedByColumn)
    {
        $requests=[];
        $request = ProductProjectionQueryRequest::of()
            ->where(
                sprintf(
                    $this->getIdentifierQuery($identifiedByColumn),
                    $this->getIdentifierFromArray($identifiedByColumn, $productsData)
                )
            )
            ->limit(500)
            ->staged(true);

        $response = $request->executeWithClient($this->client);
        $products = $request->mapFromResponse($response);

        $productsArr=$this->getProductsByIdentifiedByColumn($products, $identifiedByColumn);
        $productsDataArr=$this->getProductsDataByIdentifiedByColumn($productsData, $identifiedByColumn);

        /**
        * @var ProductProjection $product
        */
        foreach ($productsDataArr as $key => $productData) {
            if (isset($productsArr[$key])) {
                $product = $productsArr[$key];
                $request = $this->getUpdateRequest($product, $productData);
                if (!$request->hasActions()) {
                    $request = null;
                }
                $requests []=$request;
            } else {
                $request  = $this->getCreateRequest($productData);
                $requests []= $request;
            }
        }
        return $requests;
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
        $productDraftArray = $this->productDataObj->mapProductFromData($productData, $this->productTypes[$productData[self::PRODUCTTYPE]], true);
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
    private function getUpdateRequestsToChange($toChange)
    {
        $actions=[];
        foreach ($toChange as $heading => $data) {
            switch ($heading) {
                case self::NAME:
                    $actions[$heading] = ProductChangeNameAction::ofName(
                        LocalizedString::fromArray($this->productDraftArray[$heading])
                    );
                    break;
                case self::SLUG:
                    $actions[$heading] = ProductChangeSlugAction::ofSlug(
                        LocalizedString::fromArray($this->productDraftArray[$heading])
                    );
                    break;
                case self::DESCRIPTION:
                    $action = ProductSetDescriptionAction::of();
                    if (!empty($this->productDraftArray[$heading])) {
                        $action->setDescription(LocalizedString::fromArray($this->productDraftArray[$heading]));
                    }
                    if (!empty($this->productDraftArray[$heading]) || !empty($this->product[$heading])) {
                        $actions[$heading] = $action;
                    }
                    break;
                case self::KEY:
                    $action = ProductSetKeyAction::of();
                    if (!empty($this->productDraftArray[$heading])) {
                        $action->setKey($this->productDraftArray[$heading]);
                    }
                    if (!empty($this->productDraftArray[$heading]) || !empty($this->product[$heading])) {
                        $actions[$heading] = $action;
                    }
                    break;
                case self::TAXCATEGORY:
                    $action = ProductSetTaxCategoryAction::of();
                    if (!empty($this->productDraftArray[$heading])) {
                        $action->setTaxCategory($data);
                    }
                    if (!empty($this->productDraftArray[$heading]) || !empty($this->product[$heading])) {
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
                    if (!empty($this->productDraftArray[$heading])) {
                        $action->setMetaTitle(LocalizedString::fromArray($this->productDraftArray[$heading]));
                    }
                    if (!empty($this->productDraftArray[$heading]) || !empty($this->product[$heading])) {
                        $actions[$heading] = $action;
                    }
                    break;
                case self::METADESCRIPTION:
                    $action = ProductSetMetaDescriptionAction::of();
                    if (!empty($this->productDraftArray[$heading])) {
                        $action->setMetaDescription(LocalizedString::fromArray($this->productDraftArray[$heading]));
                    }
                    if (!empty($this->productDraftArray[$heading]) || !empty($this->product[$heading])) {
                        $actions[$heading] = $action;
                    }
                    break;
                case self::METAKEYWORDS:
                    $action = ProductSetMetaKeywordsAction::of();
                    if (!empty($this->productDraftArray[$heading])) {
                        $action->setMetaKeywords(LocalizedString::fromArray($this->productDraftArray[$heading]));
                    }
                    if (!empty($this->productDraftArray[$heading]) || !empty($this->product[$heading])) {
                        $actions[$heading] = $action;
                    }
                    break;
                case self::MASTERVARIANT:
                    //if empty masterVariant break;
                    if (count($this->product[self::MASTERVARIANT])==2
                        && $this->product[self::MASTERVARIANT][self::KEY]==""
                        && $this->product[self::MASTERVARIANT][self::PRICES]==[]) {
                        break;
                    }
                    if (!isset($this->productDraftArray[$heading][self::VARIANTKEY])) {
                        $this->productDraftArray[$heading][self::VARIANTKEY]="";

                    }
                    $this->product[self::MASTERVARIANT]=$this->variantDataObj->getProductVariantsById([$this->product[self::MASTERVARIANT]])[$this->product[self::MASTERVARIANT][self::ID]];
                    $this->productDraftArray[$heading]=$this->variantDataObj->getDataVariantsById([$this->productDraftArray[$heading]])[$this->productDraftArray[self::MASTERVARIANT][self::VARIANTID]];
                    $actions = array_merge_recursive(
                        $actions,
                        $this->variantDataObj->getVariantActions($this->product[self::MASTERVARIANT], $this->productDraftArray[$heading], $this->productTypes[$this->product[self::PRODUCTTYPE][self::ID]])
                    );

                    break;
                case self::VARIANTS:
                    if ($this->productVariantsById) {
                        foreach ($this->productVariantsById as $variant) {
                            if (isset($this->productVariantsDraftById[$variant[self::ID]])) {//change sku to id
                                $actions = array_merge_recursive(
                                    $actions,
                                    $this->variantDataObj->getVariantActions($variant, $this->productVariantsDraftById[$variant[self::ID]], $this->productTypes[$this->product[self::PRODUCTTYPE][self::ID]])
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
        $toChange[self::CATEGORIES]=$this->productDataObj->categoriesToAdd($product[self::CATEGORIES], $productDataArray[self::CATEGORIES]);

        if (isset($product[self::TAXCATEGORY]) && isset($productDataArray[self::TAXCATEGORY])) {
            $taxCategoryToChange=$this->productDataObj->taxCategoryDiff($product[self::TAXCATEGORY], $productDataArray[self::TAXCATEGORY]);
            if ($taxCategoryToChange ==null) {
                unset($toChange[self::TAXCATEGORY]); //to avoid unnecessary action
            } else {
                $toChange[self::TAXCATEGORY ]= $this->productDataObj->getTaxCategoryRefByName($productDataArray[self::TAXCATEGORY]['obj']['name']);
            }
        } elseif (isset($productDataArray[self::TAXCATEGORY])) {
            $toChange[self::TAXCATEGORY ]= $this->productDataObj->getTaxCategoryRefByName($productDataArray[self::TAXCATEGORY]['obj']['name']);
        } else {
            $toChange[self::TAXCATEGORY]=[];
        }
        return $toChange;
    }
    private function prepareDataAndProduct(&$productDataArray, &$product)
    {
        if (!isset($productDataArray[self::VARIANTS])) {
            $productDataArray[self::VARIANTS] = [];
        }
        if (!isset($productDataArray[self::CATEGORIES])) {
            $productDataArray[self::CATEGORIES] = [];
        }
        if (!isset($product[self::CATEGORIES])) {
            $product[self::CATEGORIES] = [];
        }
        if (isset($productDataArray[self::TAXCATEGORY])) {
            $productDataArray[self::TAXCATEGORY] = $this->productDataObj->getTaxCategoryRefByName($productDataArray[self::TAXCATEGORY]['obj']['name'])->toArray();
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
        if (isset($productDataArray[self::MASTERVARIANT])) {
            if (!isset($productDataArray[self::MASTERVARIANT][self::VARIANTKEY])) {
                $productDataArray[self::MASTERVARIANT][self::VARIANTKEY] = "";
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
            $this->productVariantsById = $this->variantDataObj->getProductVariantsById($product[self::VARIANTS]);
        }
    }
    private function getUpdateRequest(ProductProjection $product, $productData)
    {
        $productDraftArray = $this->productDataObj->mapProductFromData($productData, $this->productTypes[$productData[self::PRODUCTTYPE]]);
        $productDataDraft = ProductDraft::fromArray($productDraftArray);
        $productDataArray= $productDataDraft->toArray();

        $product = $product->toArray();
        $this->prepareDataAndProduct($productDataArray, $product);
        $this->productDraftArray = $productDataArray;
        $this->product = $product;

        if (isset($productDataArray[self::MASTERVARIANT])) {
            if (count($product[self::MASTERVARIANT])==2
                && $product[self::MASTERVARIANT][self::KEY]==""
                && $product[self::MASTERVARIANT][self::PRICES]==[]) { // empty product's master variant
                $toAdd[self::MASTERVARIANT] =  [$productDataArray[self::MASTERVARIANT]];
            }
        }

        $this->productVariantsDraftById = $this->variantDataObj->getDataVariantsById($productDataArray[self::VARIANTS]);

        $toRemove[self::VARIANTS] = $this->variantDataObj->getVariantsDiff($this->productVariantsById, $this->productVariantsDraftById, false);
        $toRemove[self::CATEGORIES]=$this->productDataObj->categoriesToRemove($product[self::CATEGORIES], $productDataArray[self::CATEGORIES]);

        $toAdd[self::VARIANTS] = $this->variantDataObj->getVariantsDiff($this->productVariantsById, $this->productVariantsDraftById);

        $toChange =$this->getProductItemsToChange($productDataArray, $product);

        $request = ProductUpdateRequest::ofIdAndVersion($product[self::ID], $product[self::VERSION]);

        $actions=[];
        $actions = array_merge_recursive($actions, $this->getUpdateRequestsToAdd($toAdd));
        $actions = array_merge_recursive($actions, $this->getUpdateRequestsToRemove($toRemove));
        $actions = array_merge_recursive($actions, $this->getUpdateRequestsToChange($toChange));

        $request->setActions($actions);
//        print_r((string)$request->httpRequest()->getBody());
        return $request;
    }

    public function getIdentifierQuery($identifierName, $query = ' in (%s)')
    {
        $parts = explode('.', $identifierName);
        $value="";
        switch ($parts[0]) {
            case self::SLUG:
                $value = $parts[0].'('.$parts[1]. $query . ')';
                break;
            case self::SKU:
                $value = self::MASTERVARIANT.'('.self::SKU.' in (%1$s)) or '. self::VARIANTS.'('.self::SKU.' in (%1$s)  )';
                break;
            case self::KEY:
            case self::ID:
                $value = $parts[0].$query;
                break;
        }
        return $value;
    }
    public function getIdentifierFromArray($identifierName, $rows)
    {
        $parts = explode('.', $identifierName);
        $value=[];
        foreach ($rows as $row) {
            switch ($parts[0]) {
                case self::SLUG:
                    $value []= '"'.$row[$parts[0]][$parts[1]].'"';
                    break;
                case self::SKU:
                case self::KEY:
                case self::ID:
                    $value [] = '"'.$row[$parts[0]].'"';
                    break;
            }
        }
        return implode(',', $value);
    }
}
