<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 09/01/17
 * Time: 10:19
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Commons\Helper\QueryHelper;
use Commercetools\Symfony\CtpBundle\Model\Import\VariantData; // TODO dont remove raise exception (it is a symfony issue)
use Commercetools\Core\Model\ProductType\ProductTypeReference;
use Commercetools\Core\Model\Product\ProductVariantDraft;
use Commercetools\Core\Client;
use Commercetools\Core\Request\Categories\CategoryQueryRequest;
use Commercetools\Core\Request\TaxCategories\TaxCategoryQueryRequest;
use Commercetools\Core\Model\Category\CategoryReferenceCollection;

class ProductData
{
    const ID= 'id';
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
    const PUBLISH='publish';
    const VARIANTID='variantId';
    const PRODUCTTYPE='productType';
    const TAX='tax';
    const SEARCHKEYWORDS='searchKeywords';
    const REFERENCE='reference';
    const ANCESTORS='ancestors';

    private $categories;
    private $client;
    private $taxCategories;
    private $variantDataObj;

    public function __construct(Client $client, variantData $variantDataObj)
    {
        $this->client = $client;
        $this->categories = $this->getCategories();
        $this->taxCategories = $this->getTaxCategories();
        $this->variantDataObj = $variantDataObj;
    }
    public function mapProductFromData($productData, $productType, $ignoreEmpty = false)
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
                    if (isset($this->taxCategories[$value])) {
                        $productDraftArray[self::TAXCATEGORY] = $this->taxCategories[$value];
                    }
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
                        $variantData = $this->variantDataObj->mapVariantFromData($variant, $productType);
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
    public function categoriesToAdd($productCategories, $dataCategories)
    {
        $toAdd=[];
        foreach ($dataCategories as $category) {
            if (!$this->searchArray($category[self::ID], $productCategories)) {
                $toAdd []= $category;
            }
        }
        return $toAdd;
    }
    public function taxCategoryDiff($productCategory, $dataCategory)
    {
        if ($productCategory[self::ID] != $dataCategory [self::ID]) {
            return $this->taxCategories[$dataCategory['obj']['name']];
        }
    }
    public function categoriesToRemove($productCategories, $dataCategories)
    {
        $toRemove=[];
        foreach ($productCategories as $category) {
            if (!$this->searchArray($category[self::ID], $dataCategories)) {
                $toRemove []= $category;
            }
        }
        return $toRemove;
    }
    public function getTaxCategoryRefByName($name)
    {
        if (isset($this->taxCategories[$name])) {
            return $this->taxCategories[$name];
        }
        return null;
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
}
