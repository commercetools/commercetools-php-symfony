<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 09/01/17
 * Time: 10:43
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Core\Client;
use Commercetools\Core\Request\CustomerGroups\CustomerGroupQueryRequest;
use Commercetools\Commons\Helper\QueryHelper;
use Commercetools\Core\Model\Common\Image;
use Commercetools\Core\Model\Common\ImageDimension;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Core\Model\Common\Price;
use Commercetools\Core\Model\Common\PriceDraft;
use Commercetools\Core\Model\Product\ProductVariantDraftCollection;
use Commercetools\Core\Model\ProductType\BooleanType;
use Commercetools\Core\Model\ProductType\ProductType;
use Commercetools\Core\Model\ProductType\SetType;
use Commercetools\Core\Request\Products\Command\ProductAddExternalImageAction;
use Commercetools\Core\Request\Products\Command\ProductAddPriceAction;
use Commercetools\Core\Request\Products\Command\ProductChangePriceAction;
use Commercetools\Core\Request\Products\Command\ProductRemoveImageAction;
use Commercetools\Core\Request\Products\Command\ProductRemovePriceAction;
use Commercetools\Core\Request\Products\Command\ProductSetAttributeAction;
use Commercetools\Core\Request\Products\Command\ProductSetAttributeInAllVariantsAction;
use Commercetools\Core\Request\Products\Command\ProductSetProductVariantKeyAction;
use Commercetools\Core\Request\Products\Command\ProductSetSkuAction;

class VariantData extends AbstractRequestBuilder
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

    private $productVariantDraftPricesByUniqueKey;
    private $customerGroups;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->customerGroups = $this->getCustomerGroups();
    }

    public function getProductVariantsById($productVariants)
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
    public function getDataVariantsById($ProductVariantDraftCollection)
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

    private function mapPriceFromData($data)
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
    public function mapVariantFromData($variantData, ProductType $productType)
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
                    $variantDraftArray[$key]=$this->mapPriceFromData($value);
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

    public function getVariantsDiff($productVariants, $ProductVariantDraftCollection, $toAddFlag = true)
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
    public function getVariantActions($productVariant, $productVariantDraftArray, $productType)
    {
        $actions = [];

        $toChange = $this->getVariantItemsToChange($productVariantDraftArray, $productVariant);
        $toRemove= $this->getVariantItemsToRemove($productVariantDraftArray, $productVariant);
        $toAdd=$this->getVariantItemsToAdd($productVariantDraftArray, $productVariant);

        /**
         * @var ProductType $productType
         */
//        $productType = $productType;

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
}
