<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Model\View;

use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Model\Product\ProductVariant;
use Commercetools\Core\Model\ProductType\ProductType;
use Commercetools\Symfony\CatalogBundle\Manager\CatalogManager;
use Commercetools\Symfony\ExampleBundle\Model\ViewData;
use Commercetools\Symfony\ExampleBundle\Model\ViewDataCollection;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductModel
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var CatalogManager
     */
    private $catalogManager;

    /**
     * ProductModel constructor.
     * @param CacheItemPoolInterface $cache
     * @param CatalogManager $catalogManager
     */
    public function __construct(CacheItemPoolInterface $cache, CatalogManager $catalogManager, $country, $currency)
    {
        $this->cache = $cache;
        $this->catalogManager = $catalogManager;
    }


    protected function getProductData(
        $cachePrefix,
        ProductProjection $product,
        ProductVariant $productVariant,
        $locale,
        $includeDetails,
        $selectSku = null
    ) {
        $cacheKey = $cachePrefix . '-' . $productVariant->getSku() . $selectSku . '-' . $locale;
        if (!$this->cache->hasItem($cacheKey)) {
            $unserialize = unserialize($this->cache->getItem($cacheKey)->get());
//            dump($unserialize);
            return $unserialize;
        }

        $productModel = new ViewData();
        $productModelProduct = new ViewData();
        $productModelVariant = new ViewData();
        $price = $productVariant->getPrice();

        if (empty($selectSku)) {
            $productUrl = [
                'pdp-master',
                [
                    'slug' => (string)$product->getSlug(),
                ]
            ];
        } else {
            $productUrl = [
                'pdp',
                [
                    'slug' => (string)$product->getSlug(),
                    'sku' => $productVariant->getSku()
                ]
            ];
        }

        $productModelProduct->variantId = $productVariant->getId();
        $productModelVariant->url = $productUrl;
        $productModelProduct->productId = $product->getId();
        $productModelProduct->slug = (string)$product->getSlug();
        $productModelVariant->name = (string)$product->getName();

        if (!is_null($price->getDiscounted())) {
            $productModelVariant->price = (string)$price->getDiscounted()->getValue();
            $productModelVariant->priceOld = (string)$price->getValue();
        } else {
            $productModelVariant->price = (string)$price->getValue();
        }
        $productModel->sale = isset($productModelVariant->priceOld);

        $productModelProduct->gallery = new ViewData();
        $productModelVariant->image = (string)$productVariant->getImages()->current()->getUrl();
        $productModelProduct->gallery->list = new ViewDataCollection();
        foreach ($productVariant->getImages() as $image) {
            $imageData = new ViewData();
            $imageData->thumbImage = $image->getUrl();
            $imageData->bigImage = $image->getUrl();
            $productModelProduct->gallery->list->add($imageData);
        }

        if ($includeDetails) {
            $productModelVariant->description = (string)$product->getDescription();

            $productType = $this->catalogManager->getProductTypeById($locale, $product->getProductType()->getId());
            list($attributes, $variantKeys, $variantIdentifiers) = $this->getVariantSelectors($product, $productType, $selectSku);
            $productModelProduct->variants = $variantKeys;
            $productModelProduct->variantIdentifiers = $variantIdentifiers;

            if ($selectSku || count($variantIdentifiers) == 0) {
                $productModelVariant->variantId = $productVariant->getId();
                $productModelVariant->sku = $productVariant->getSku();
            }

            $productModelProduct->attributes = $attributes;


            $productModelProduct->details = new ViewData();
            $productModelProduct->details->list = new ViewDataCollection();
            $productVariant->getAttributes()->setAttributeDefinitions(
                $productType->getAttributes()
            );
            if (isset($this->config['sunrise.products.details.attributes'][$productType->getName()])) {
                $attributeList = $this->config['sunrise.products.details.attributes.'.$productType->getName()];
                foreach ($attributeList as $attributeName) {
                    $attribute = $productVariant->getAttributes()->getByName($attributeName);
                    if ($attribute) {
                        $attributeDefinition = $productType->getAttributes()->getByName(
                            $attributeName
                        );
                        $attributeData = new ViewData();
                        $attributeData->text = (string)$attributeDefinition->getLabel() . ': ' . (string)$attribute->getValue();
                        $productModelProduct->details->list->add($attributeData);
                    }
                }
            }
        }

        $productModel->product = $productModelProduct;
        $productModel->product->variant = $productModelVariant;

        $productModel = $productModel->toArray();
        $item = $this->cache->getItem($cacheKey)->set(serialize($productModel));
        $this->cache->save($item);

//        dump($product->getAllVariants());
//        dump($productModel);

        return $productModel;
    }

    public function getProductOverviewData(
        ProductProjection $product,
        ProductVariant $productVariant,
        $locale
    ) {
        $cachePrefix = 'product-overview-model';
        return $this->getProductData($cachePrefix, $product, $productVariant, $locale, true);
    }

    public function getProductDetailData(ProductProjection $product, $sku, $locale)
    {
        $requestSku = $sku;
        if (empty($sku)) {
            $sku = $product->getMasterVariant()->getSku();
        }

        $productVariant = $product->getVariantBySku($sku);
        if (empty($productVariant)) {
            throw new NotFoundHttpException("resource not found");
        }

        $cachePrefix = 'product-detail-model';
        $productModel = $this->getProductData($cachePrefix, $product, $productVariant, $locale, true, $requestSku);

        if (isset($productModel['product'])) {
            return $productModel['product'];
        }
        return [];
    }

    public function getVariantSelectors(ProductProjection $product, ProductType $productType, $sku)
    {
        $variantSelectors = [];
        if (isset($this->config['sunrise.products.variantsSelector'][$productType->getName()])) {
            $variantSelectors = $this->config['sunrise.products.variantsSelector'][$productType->getName()];
        }
        $variants = [];
        $attributes = [];
        /**
         * @var ProductVariant $variant
         */
        foreach ($product->getAllVariants() as $variant) {
            $variantId = $variant->getId();
            if (is_null($variant->getAttributes())) {
                continue;
            }
            $variant->getAttributes()->setAttributeDefinitions($productType->getAttributes());
            $selected = ($sku == $variant->getSku());
            foreach ($variantSelectors as $attributeName) {
                $attribute = $variant->getAttributes()->getByName($attributeName);
                if ($attribute) {
                    $value = (string)$attribute->getValue();
                    $variants[$variantId][$attributeName] = $value;
                    if (!isset($attributes[$attributeName])) {
                        $attributes[$attributeName] = [
                            'key' => $attributeName,
                            'name' => (string)$attribute->getName(),
                        ];
                    }
                    if (!isset($attributes[$attributeName]['list'][$value])) {
                        $attributes[$attributeName]['list'][$value] = [
                            'label' => $value,
                            'value' => $value,
                            'selected' => false
                        ];
                    }
                    if ($selected) {
                        $attributes[$attributeName]['list'][$value]['selected'] = $selected;
                    }
                }
            }
        }
        $variantKeys = [];
        $identifiers = array_values(array_intersect($variantSelectors, array_keys($attributes)));
        foreach ($variants as $variantId => $variantAttributes) {
            foreach ($identifiers as $selectorX) {
                foreach ($identifiers as $selectorY) {
                    if ($selectorX == $selectorY) {
                        continue;
                    }
                    if (isset($variantAttributes[$selectorX]) && isset($variantAttributes[$selectorY])) {
                        $valueX = $variantAttributes[$selectorX];
                        $valueY = $variantAttributes[$selectorY];
                        if (isset($attributes[$selectorX]['selectData'][$valueX][$selectorY]) &&
                            in_array($valueY, $attributes[$selectorX]['selectData'][$valueX][$selectorY])
                        ) {
                            // ignore duplicates in combination values
                            continue;
                        }
                        $attributes[$selectorX]['selectData'][$valueX][$selectorY][] = $valueY;
                    }
                }
            }
            if (count($variantAttributes) == count($identifiers)) {
                $variantKey = implode('-', $variantAttributes);
                $variantKeys[$variantKey] = $variantId;
            }
        }

        return [$attributes, $variantKeys, $identifiers];
    }
}
