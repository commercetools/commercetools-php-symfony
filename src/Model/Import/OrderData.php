<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 24/01/17
 * Time: 17:15
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Core\Model\Cart\CustomLineItemDraft;
use Commercetools\Core\Model\Cart\LineItemDraft;
use Commercetools\Core\Model\Channel\ChannelCollection;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\Common\Image;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Core\Model\Common\PriceDraft;
use Commercetools\Core\Model\Common\TaxedPrice;
use Commercetools\Core\Model\Common\TaxPortion;
use Commercetools\Core\Model\CustomField\CustomFieldObjectDraft;
use Commercetools\Core\Model\Order\ProductVariantImportDraft;
use Commercetools\Core\Client;
use Commercetools\Core\Model\CustomerGroup\CustomerGroupCollection;
use Commercetools\Core\Model\TaxCategory\ExternalTaxRateDraft;
use Commercetools\Core\Model\TaxCategory\TaxCategoryCollection;
use Commercetools\Core\Model\TaxCategory\TaxRate;
use Commercetools\Core\Request\Channels\ChannelQueryRequest;
use Commercetools\Core\Request\CustomerGroups\CustomerGroupQueryRequest;
use Commercetools\Commons\Helper\QueryHelper;
use Commercetools\Core\Request\TaxCategories\TaxCategoryQueryRequest;

class OrderData extends AbstractRequestBuilder
{
    const ID ='id';
    const NAME ='name';
    const VARIANT ='variant';
    const LINEITEMS ='lineItems';
    const CUSTOMLINEITEMS='customLineItems';
    const TOTALPRICE ='totalPrice';
    const CURRENCYCODE ='currencyCode';
    const CENTAMOUNT ='centAmount';
    const BILLINGADDRESS ='billingAddress';
    const SHIPPINGADDRESS ='shippingAddress';
    const QUANTITY ='quantity';
    const PRICE ='price';
    const PRICES ='prices';
    const VALUE ='value';
    const PRODUCTID ='productId';
    const VARIANTID ='variantId';
    const TOTALNET ='totalNet';
    const TAXDPRICE ='taxedPrice';
    const TOTALGROSS ='totalGross';
    const TAXPORTIONS ='taxPortions';
    const RATE ='rate';
    const AMOUNT ='amount';
    const COMPLETEDAT ='completedAt';
    const CUSTOMERGROUP='customerGroup';
    const CUSTOM='custom';
    const SUPPLYCHANNEL='supplyChannel';
    const TAXRATE='taxRate';
    const MONEY='money';
    const TAXCATEGORY='taxCategory';
    const EXTERNALTAXRATE='externalTaxRate';
    const IMAGES='images';
    const URL='url';
    const DIMENSIONS='dimensions';
    const COUNTRY='country';

    private $client;
    private $customerGroups;
    private $supplyChannels;
    private $taxCategories;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->customerGroups = $this->getCustomerGroups();
        $this->supplyChannels = $this->getSupplyChannels();
        $this->taxCategories = $this->getTaxGategories();
    }

    public function mapOrderFromData($data)
    {
        foreach ($data as $key => $value) {
            switch ($key) {
                case self::TOTALNET:
                    if (!empty($value) && $value !='') {
                        $priceParts = explode(' ', $data[self::TOTALNET]);
                        $price[self::CURRENCYCODE] = $priceParts[0];
                        $price[self::CENTAMOUNT] = (int)$priceParts[1];
                        $data[self::TOTALNET] = $price;

                        $priceParts = explode(' ', $data[self::TOTALGROSS]);
                        $price[self::CURRENCYCODE] = $priceParts[0];
                        $price[self::CENTAMOUNT] = (int)$priceParts[1];
                        $data[self::TOTALGROSS] = $price;

                        $data[self::TAXDPRICE][self::TOTALNET] = $data[self::TOTALNET];
                        $data[self::TAXDPRICE][self::TOTALGROSS] = $data[self::TOTALGROSS];

                        $priceParts = explode(' ', $data[self::TAXPORTIONS][self::AMOUNT]);
                        $price[self::CURRENCYCODE] = $priceParts[0];
                        $price[self::CENTAMOUNT] = (int)$priceParts[1];
                        $data[self::TAXPORTIONS][self::AMOUNT] = $price;

                        $data[self::TAXDPRICE][self::TAXPORTIONS][self::NAME] = $data[self::TAXPORTIONS][self::NAME];
                        $data[self::TAXDPRICE][self::TAXPORTIONS][self::RATE] = (int)$data[self::TAXPORTIONS][self::RATE];
                        $data[self::TAXDPRICE][self::TAXPORTIONS][self::AMOUNT] = $data[self::TAXPORTIONS][self::AMOUNT];
                    }
                    break;
                case self::TOTALPRICE:
                    $priceParts = explode(' ', $data[self::TOTALPRICE]);
                    $price[self::CURRENCYCODE] = $priceParts[0];
                    $price[self::CENTAMOUNT] = (int)$priceParts[1];
                    $data[self::TOTALPRICE] = $price;
                    break;
                case self::BILLINGADDRESS:
                    if (empty($data[self::BILLINGADDRESS])) {
                        unset($data[self::BILLINGADDRESS]);
                    }
                    break;
                case self::SHIPPINGADDRESS:
                    if (empty($data[self::SHIPPINGADDRESS])) {
                        unset($data[self::SHIPPINGADDRESS]);
                    }
                    break;
                case self::LINEITEMS:
                case self::CUSTOMLINEITEMS:
                    if (isset($data[$key][self::NAME])) {
                          unset($data[$key][self::NAME]);
                    }
                    if (isset($data[$key][self::VARIANT])) {
                        unset($data[$key][self::VARIANT]);
                    }
                    foreach ($data[$key] as &$lineItem) {
                        if (isset($lineItem[self::PRICE]) && !empty($lineItem[self::PRICE])) {
                            $lineItem[self::PRICE] = $this->mapPriceFromData($lineItem[self::PRICE])[0];
                        }
                        if (isset($lineItem[self::MONEY]) && !empty($lineItem[self::MONEY])) {
                            $priceParts = explode(' ', $lineItem[self::MONEY]);
                            $price[self::CURRENCYCODE] = $priceParts[0];
                            $price[self::CENTAMOUNT] = (int)$priceParts[1];
                            $lineItem[self::MONEY] = $price;
                        }
                        if (isset($lineItem[self::QUANTITY]) && !empty($lineItem[self::QUANTITY])) {
                            $lineItem[self::QUANTITY] = (int)$lineItem[self::QUANTITY];
                        }
                        if (isset($lineItem[self::PRODUCTID]) && !isset($lineItem[self::VARIANT][self::VARIANTID])) {
                            unset($lineItem[self::PRODUCTID]);
                        }
                        if (isset($lineItem[$key])) {
                            unset($lineItem[$key]);
                        }
                        if (isset($lineItem[self::ID])) {
                            unset($lineItem[self::ID]);
                        }
                    }
                    break;
            }
        }
        return $data;
    }
    private function getItemsObjFromArr($items, $lineItemFlag = true)
    {
        foreach ($items as &$lineItem) {
            if (isset($lineItem[self::PRICE]) && !empty($lineItem[self::PRICE])) {
                $lineItem[self::PRICE] = PriceDraft::fromArray($lineItem[self::PRICE]);
            }
            if (isset($lineItem[self::MONEY]) && !empty($lineItem[self::MONEY])) {
                $lineItem[self::MONEY] = Money::fromArray($lineItem[self::MONEY]);
            }
            if (isset($lineItem[self::VARIANT]) && !empty($lineItem[self::VARIANT])) {
                if (isset($lineItem[self::VARIANT][self::IMAGES]) && !empty($lineItem[self::VARIANT][self::IMAGES])) {
                    $lineItem[self::VARIANT][self::IMAGES] = $this->mapImagesFromData($lineItem[self::VARIANT][self::IMAGES]);
                }
                if (isset($lineItem[self::VARIANT][self::PRICES]) && !empty($lineItem[self::VARIANT][self::PRICES])) {
                    $prices =$this->mapPriceFromData($lineItem[self::VARIANT][self::PRICES]);
                    foreach ($prices as $price) {
                        $lineItem[self::VARIANT][self::PRICES][] = PriceDraft::fromArray($price);
                    }
                }
                $lineItem[self::VARIANT] = ProductVariantImportDraft::fromArray($lineItem[self::VARIANT]);
            }
            if (isset($lineItem[self::CUSTOM]) && !empty($lineItem[self::CUSTOM])) {
                $lineItem[self::CUSTOM] = CustomFieldObjectDraft::fromArray($lineItem[self::CUSTOM]);
            }
            if (isset($lineItem[self::SUPPLYCHANNEL]) && !empty($lineItem[self::SUPPLYCHANNEL])) {
                $lineItem[self::SUPPLYCHANNEL] = $this->supplyChannels[$lineItem[self::SUPPLYCHANNEL]];
            }
            if (isset($lineItem[self::TAXRATE]) && !empty($lineItem[self::TAXRATE])) {
                $lineItem[self::TAXRATE] = TaxRate::fromArray($lineItem[self::TAXRATE]);
            }
            if (isset($lineItem[self::TAXCATEGORY]) && !empty($lineItem[self::TAXCATEGORY])) {
                $lineItem[self::TAXCATEGORY] = $this->taxCategories[$lineItem[self::TAXCATEGORY]];
            }
            if (isset($lineItem[self::EXTERNALTAXRATE]) && !empty($lineItem[self::EXTERNALTAXRATE])) {
                $lineItem[self::EXTERNALTAXRATE] = ExternalTaxRateDraft::fromArray($lineItem[self::EXTERNALTAXRATE]);
            }
            if ($lineItemFlag) {
                $lineItem = LineItemDraft::fromArray($lineItem);
            } else {
                $lineItem = CustomLineItemDraft::fromArray($lineItem);
            }
        }
        return $items;
    }
    public function getOrderObjsFromArr($OrderArr)
    {
        if (isset($OrderArr[self::TAXDPRICE])) {
            $OrderArr[self::TAXDPRICE][self::TOTALNET] = Money::fromArray($OrderArr[self::TAXDPRICE][self::TOTALNET]);
            $OrderArr[self::TAXDPRICE][self::TOTALGROSS] = Money::fromArray($OrderArr[self::TAXDPRICE][self::TOTALGROSS]);
            $OrderArr[self::TAXDPRICE][self::TAXPORTIONS][self::AMOUNT] = Money::fromArray($OrderArr[self::TAXDPRICE][self::TAXPORTIONS][self::AMOUNT]);
            $OrderArr[self::TAXDPRICE][self::TAXPORTIONS] =[TaxPortion::fromArray($OrderArr[self::TAXDPRICE][self::TAXPORTIONS])];
            $OrderArr[self::TAXDPRICE] = TaxedPrice::fromArray($OrderArr[self::TAXDPRICE]);
        }

        if (isset($OrderArr[self::LINEITEMS])&& !empty($lineItem[self::LINEITEMS])) {
            $OrderArr[self::LINEITEMS]= $this->getItemsObjFromArr($OrderArr[self::LINEITEMS]);
        } elseif (isset($OrderArr[self::CUSTOMLINEITEMS])&& !empty($lineItem[self::CUSTOMLINEITEMS])) {
            $OrderArr[self::CUSTOMLINEITEMS]= $this->getItemsObjFromArr($OrderArr[self::CUSTOMLINEITEMS], false);
        }

        if (isset($OrderArr[self::BILLINGADDRESS]) && !empty($OrderArr[self::BILLINGADDRESS])) {
            $OrderArr[self::BILLINGADDRESS] = Address::fromArray($OrderArr[self::BILLINGADDRESS]);
        }
        if (isset($OrderArr[self::SHIPPINGADDRESS]) && !empty($OrderArr[self::SHIPPINGADDRESS])) {
            $OrderArr[self::SHIPPINGADDRESS] = Address::fromArray($OrderArr[self::SHIPPINGADDRESS]);
        }
        if (isset($OrderArr[self::CUSTOMERGROUP]) && !empty($OrderArr[self::CUSTOMERGROUP])) {
            $OrderArr[self::CUSTOMERGROUP] = $this->customerGroups[$OrderArr[self::CUSTOMERGROUP]];
        }
        if (isset($OrderArr[self::CUSTOM]) && !empty($OrderArr[self::CUSTOM])) {
            $OrderArr[self::CUSTOM] = CustomFieldObjectDraft::fromArray($OrderArr[self::CUSTOM]);
        }
        return $OrderArr;
    }

    public function getOrderItemsToChange($orderDataArray, $order)
    {
        $intersect = $this->arrayIntersectRecursive($order, $orderDataArray);
        $toChange = $this->arrayDiffRecursive($orderDataArray, $intersect);
        return $toChange;
    }

    private function mapImagesFromData($imagesString)
    {
        $images=[];
        $dimension= ["w"=> 0, "h"=> 0];
        $value=explode(';', $imagesString);
        foreach ($value as $imageUrl) {
            if ($imageUrl!='') {
                $image[self::URL] = $imageUrl;
                $image[self::DIMENSIONS] = $dimension;
                $images[]=Image::fromArray($image);
            }
        }
        return $images;
    }
    private function getSupplyChannels()
    {
        $request = ChannelQueryRequest::of();
        $helper = new QueryHelper();
        $channels = $helper->getAll($this->client, $request);
        /**
         * @var ChannelCollection $channels ;
         */
        $supplyChannels = [];
        foreach ($channels as $channel) {
            $supplyChannels[$channel->getKey()] = $channel->getReference();
            $supplyChannels[$channel->getId()] = $channel->getReference();
        }
        return $supplyChannels;
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
                $money[self::CENTAMOUNT]= intval($splittedcurrencyAndPrice[1]);
                $price[self::VALUE]=$money;
                $prices[]= $price;
            }
        }
        return $prices;
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
        }
        return $customerGroupsByName;
    }
    private function getTaxGategories()
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
