<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 24/01/17
 * Time: 17:15
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Core\Model\Cart\LineItemDraft;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Core\Model\Common\PriceDraft;
use Commercetools\Core\Model\Common\TaxedPrice;
use Commercetools\Core\Model\Common\TaxPortion;
use Commercetools\Core\Model\Order\ProductVariantImportDraft;
use Commercetools\Core\Client;
use Commercetools\Core\Model\CustomerGroup\CustomerGroupCollection;
use Commercetools\Core\Request\CustomerGroups\CustomerGroupQueryRequest;
use Commercetools\Commons\Helper\QueryHelper;

class OrderData extends AbstractRequestBuilder
{
    const ID ='id';
    const NAME ='name';
    const VARIANT ='variant';
    const LINEITEMS ='lineItems';
    const TOTALPRICE ='totalPrice';
    const CURRENCYCODE ='currencyCode';
    const CENTAMOUNT ='centAmount';
    const BILLINGADDRESS ='billingAddress';
    const SHIPPINGADDRESS ='shippingAddress';
    const QUANTITY ='quantity';
    const PRICE ='price';
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

    private $client;
    private $customerGroups;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->customerGroups = $this->getCustomerGroups();
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
                    if (isset($data[self::LINEITEMS][self::NAME])) {
                          unset($data[self::LINEITEMS][self::NAME]);
                    }
                    if (isset($data[self::LINEITEMS][self::VARIANT])) {
                        unset($data[self::LINEITEMS][self::VARIANT]);
                    }
                    foreach ($data[self::LINEITEMS] as &$lineItem) {
                        if (isset($lineItem[self::PRICE])) {
                            $priceParts = explode(' ', $lineItem[self::PRICE]);
                            $price[self::CURRENCYCODE] = $priceParts[0];
                            $price[self::CENTAMOUNT] = (int)$priceParts[1];
                            $lineItem[self::PRICE] = [self::VALUE => $price];
                        }
                        if (isset($lineItem[self::QUANTITY])) {
                            $lineItem[self::QUANTITY] = (int)$lineItem[self::QUANTITY];
                        }
                        if (isset($lineItem[self::PRODUCTID]) && !isset($lineItem[self::VARIANT][self::VARIANTID])) {
                            unset($lineItem[self::PRODUCTID]);
                        }
                        if (isset($lineItem[self::LINEITEMS])) {
                            unset($lineItem[self::LINEITEMS]);
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
    public function getOrderObjsFromArr($OrderArr)
    {
        if (isset($OrderArr[self::TAXDPRICE])) {
            $OrderArr[self::TAXDPRICE][self::TOTALNET] = Money::fromArray($OrderArr[self::TAXDPRICE][self::TOTALNET]);
            $OrderArr[self::TAXDPRICE][self::TOTALGROSS] = Money::fromArray($OrderArr[self::TAXDPRICE][self::TOTALGROSS]);
            $OrderArr[self::TAXDPRICE][self::TAXPORTIONS][self::AMOUNT] = Money::fromArray($OrderArr[self::TAXDPRICE][self::TAXPORTIONS][self::AMOUNT]);
            $OrderArr[self::TAXDPRICE][self::TAXPORTIONS] =[TaxPortion::fromArray($OrderArr[self::TAXDPRICE][self::TAXPORTIONS])];
            $OrderArr[self::TAXDPRICE] = TaxedPrice::fromArray($OrderArr[self::TAXDPRICE]);
        }
        if (isset($OrderArr[self::LINEITEMS])) {
            foreach ($OrderArr[self::LINEITEMS] as &$lineItem) {
                if (isset($lineItem[self::PRICE])) {
                    $lineItem[self::PRICE] = PriceDraft::fromArray($lineItem[self::PRICE]);
                }
                if (isset($lineItem[self::VARIANT])) {
                    $lineItem[self::VARIANT] = ProductVariantImportDraft::fromArray($lineItem[self::VARIANT]);
                }
                $lineItem = LineItemDraft::fromArray($lineItem);
            }
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
        return $OrderArr;
    }

    public function getOrderItemsToChange($orderDataArray, $order)
    {
        $intersect = $this->arrayIntersectRecursive($order, $orderDataArray);
        $toChange = $this->arrayDiffRecursive($orderDataArray, $intersect);
        return $toChange;
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
}
