<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 09/01/17
 * Time: 10:43
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Common\Image;
use Commercetools\Core\Model\Common\ImageDimension;
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
    const SLUG='slug';
    const DESCRIPTION='description';
    const KEY='key';
    const METATITLE='metaTitle';
    const METADESCRIPTION='metaDescription';
    const METAKEYWORDS='metaKeywords';
    const VARIANTKEY='variantKey';
    const PUBLISH='publish';
    const VARIANTID='variantId';
    const PRODUCTTYPE='productType';
    const TAX='tax';
    const DIMENSIONS='dimensions';
    const URL='url';
    const CREATIONDATE='creationDate';
    const SEARCHKEYWORDS='searchKeywords';

    private $formattedProductVariantDraftPrices;
    private $formattedProductVariantPrices;
    private $priceDataobj;
    private $productVariantDraftArray;
    private $productVariant;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->priceDataobj = new PriceData($this->client);
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
                    $variantDraftArray[$key]=$this->priceDataobj->mapPriceFromData($value);
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

    public function getVariantsDiff($productVariants, $ProductVariantDraftCollection, $toAddFlag = true)
    {
        if ($toAddFlag) {
            $result=[];
            if ($ProductVariantDraftCollection) {
                foreach ($ProductVariantDraftCollection as $variant) {
                    if (!isset($productVariants[$variant[self::VARIANTID]])) {
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
                    if (!isset($ProductVariantDraftCollection[$variant[self::ID]])) {
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
    private function getVariantRemoveActions($toRemove)
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
                        $actions[]= ProductRemoveImageAction::ofVariantIdAndImageUrl($this->productVariant[self::ID], $image[self::URL]);
                    }
                    break;
            }
        }
        return $actions;
    }
    private function getVariantAddActions($toAdd)
    {
        $actions=[];
        foreach ($toAdd as $key => $value) {
            switch ($key) {
                case self::PRICES:
                    foreach ($value as $key) {
                        $actions[]= ProductAddPriceAction::ofVariantIdAndPrice($this->productVariant[self::ID], $this->priceDataobj->getPriceObjByUinqueKey($key));
                    }
                    break;
                case self::IMAGES:
                    foreach ($value as $image) {
                        $actions[]= ProductAddExternalImageAction::ofVariantIdAndImage($this->productVariant[self::ID], Image::fromArray($image));
                    }
                    break;
                case self::SKU:
                    $action = ProductSetSkuAction::ofVariantId($this->productVariant[self::ID]);
                    if (!empty($value)) {
                        $action->setSku($value);
                        $actions[] = $action;
                    }
                    break;
            }
        }
        return $actions;
    }
    private function getVariantChangeActions($toChange, $productType)
    {
        $productDraftAttributes=[];
        if (isset($this->productVariantDraftArray[self::ATTRIBUTES])) {
            $productDraftAttributes = $this->productVariantDraftArray[self::ATTRIBUTES];
        }
        $actions=[];
        foreach ($toChange as $key => $value) {
            switch ($key) {
                case self::IMAGES:
                    break;
                case self::SKU:
                    $action = ProductSetSkuAction::ofVariantId($this->productVariant[self::ID]);
                    if (!empty($value)) {
                        $action->setSku($value);
                    }
                    if (!empty($value) || !empty($this->productVariant[$key])) {
                        $actions[] = $action;
                    }
                    break;
                case self::VARIANTKEY:
                    $action = ProductSetProductVariantKeyAction::of()
                        ->setVariantId($this->productVariant[self::ID]);
                    if (!empty($value)) {
                        $action->setKey($value);
                    }
                    $actions[] = $action;
                    break;
                case self::PRICES:
                    foreach ($value as $id => $price) {
                        foreach ($price as $priceUniqueKey => $value) {
                            $actions[] =
                                ProductChangePriceAction::ofPriceIdAndPrice(
                                    $id,
                                    $this->priceDataobj->getPriceObjByUinqueKey($priceUniqueKey)
                                );
                        }
                    }
                    break;
                default:
                    $attributeDefinition = $productType->getAttributes()->getByName($key);
                    if ($attributeDefinition->getAttributeConstraint() == 'SameForAll') {
                        $action = ProductSetAttributeInAllVariantsAction::ofName($key);
                    } else {
                        $action = ProductSetAttributeAction::ofVariantIdAndName($this->productVariant[self::ID], $key);
                    }

                    if (isset($productDraftAttributes[$key][self::VALUE])) {
                        $action->setValue($productDraftAttributes[$key][self::VALUE]);
                    }
                    $actions['variant' . $this->productVariant[self::ID] . $key] = $action;
            }
        }
        return $actions;
    }
    private function getVariantItemsToChange()
    {
        $productDraftAttributes = [];
        $productAttributes =[];
        $pricesDiff = [];

        if (isset($this->productVariantDraftArray[self::ATTRIBUTES])) {
            $productDraftAttributes = $this->productVariantDraftArray[self::ATTRIBUTES];
        }
        if (isset($this->productVariant[self::ATTRIBUTES])) {
            $productAttributes = $this->productVariant[self::ATTRIBUTES];
        }
        if (isset($this->productVariant[self::PRICES]) && isset($this->productVariantDraftArray[self::PRICES])) {
            $pricesDiff = $this->priceDataobj->getPriceToChange($this->formattedProductVariantDraftPrices, $this->formattedProductVariantPrices[self::ID]);
        }
        $toChange = array_merge(
            $this->arrayDiffRecursive($productAttributes, $productDraftAttributes),
            $this->arrayDiffRecursive($productDraftAttributes, $productAttributes)
        );
        if ($this->productVariantDraftArray[self::VARIANTKEY] != $this->productVariant[self::KEY]) {
            $toChange[self::VARIANTKEY]=$this->productVariantDraftArray[self::VARIANTKEY];
        }

        if (isset($pricesDiff)) {
            $toChange[self::PRICES] = $pricesDiff;
        }

        $generalDiffToChange= $this->arrayDiffRecursive($this->productVariant, $this->productVariantDraftArray);
        if (isset($generalDiffToChange[self::SKU])) {
            $toChange[self::SKU]="";
            if (isset($this->productVariantDraftArray[self::SKU])) {
                $toChange[self::SKU] = $this->productVariantDraftArray[self::SKU];
            }
        }
        return $toChange;
    }
    private function getVariantItemsToAdd()
    {
        $imagesFromData=[];
        $imagesFromVariant = [];

        if (isset($this->productVariantDraftArray[self::IMAGES])) {
            $imagesFromData = $this->productVariantDraftArray[self::IMAGES];
        }
        if (isset($this->productVariant[self::IMAGES])) {
            $imagesFromVariant = $this->productVariant[self::IMAGES];
        }
        if (isset($this->productVariant[self::PRICES]) && isset($this->productVariantDraftArray[self::PRICES])) {
            $pricesDiff = $this->priceDataobj->getPriceToAdd($this->formattedProductVariantDraftPrices, $this->formattedProductVariantPrices[self::KEY]);
        }

        $toAdd=[];
        if (!empty($pricesDiff)) {
            $toAdd[self::PRICES] = $pricesDiff;
        }
        $generalDiffToAdd= array_diff_key($this->productVariantDraftArray, $this->productVariant);
        if (isset($generalDiffToAdd[self::SKU])) {
            $toAdd[self::SKU]=$generalDiffToAdd[self::SKU];
        }
        $toAdd[self::IMAGES]= array_diff_key($imagesFromData, $imagesFromVariant);
        return $toAdd;
    }
    private function getVariantItemsToRemove()
    {
        $imagesFromData=[];
        $imagesFromVariant = [];
        $pricesDiff=[];

        if (isset($this->productVariantDraftArray[self::IMAGES])) {
            $imagesFromData = $this->productVariantDraftArray[self::IMAGES];
        }
        if (isset($this->productVariant[self::IMAGES])) {
            $imagesFromVariant = $this->productVariant[self::IMAGES];
        }
        if (isset($this->productVariant[self::PRICES]) && isset($this->productVariantDraftArray[self::PRICES])) {
            $pricesDiff = $this->priceDataobj->getPriceToRemove($this->formattedProductVariantDraftPrices, $this->formattedProductVariantPrices[self::ID]);
        }

        $toRemove=[];
        if (isset($pricesDiff)) {
            $toRemove[self::PRICES] = $pricesDiff;
        }
        $toRemove[self::IMAGES]= array_diff_key($imagesFromVariant, $imagesFromData);

        return $toRemove;
    }
    public function getVariantActions($productVariant, $productVariantDraftArray, $productType)
    {
        $actions = [];

        $this->formattedProductVariantDraftPrices = $this->priceDataobj->getProductVariantDraftPricesByUniqueKey($productVariantDraftArray[self::PRICES]);
        $this->formattedProductVariantPrices = $this->priceDataobj->getProductPricesByUniqueKeyAndId($productVariant[self::PRICES]);
        $this->productVariantDraftArray = $productVariantDraftArray;
        $this->productVariant = $productVariant;

        $toChange = $this->getVariantItemsToChange();
        $toRemove= $this->getVariantItemsToRemove();
        $toAdd=$this->getVariantItemsToAdd();

        /**
         * @var ProductType $productType
         */
//        $productType = $productType;

        $actions = array_merge_recursive($actions, $this->getVariantRemoveActions($toRemove));
        $actions = array_merge_recursive($actions, $this->getVariantAddActions($toAdd));
        $actions = array_merge_recursive($actions, $this->getVariantChangeActions($toChange, $productType));

        return $actions;
    }
}
