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
use Commercetools\Core\Model\Order\ProductVariantImportDraft;

class OrderData
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

    public function mapOrderFromData($data)
    {
        if (isset($data[self::TOTALPRICE])) {
            $priceParts = explode(' ', $data[self::TOTALPRICE]);
            $price[self::CURRENCYCODE] = $priceParts[0];
            $price[self::CENTAMOUNT] = (int)$priceParts[1];
            $data[self::TOTALPRICE] = $price;
        }
        if (isset($data[self::LINEITEMS])) {
            if (isset($data [self::LINEITEMS][self::NAME])) {
                unset($data[self::LINEITEMS][self::NAME]);
            }
            if (isset($data [self::LINEITEMS][self::VARIANT])) {
                unset($data[self::LINEITEMS][self::VARIANT]);
            }

            foreach ($data[self::LINEITEMS] as &$lineItem) {
                if (isset($lineItem[self::PRICE])) {
                    $priceParts = explode(' ', $lineItem[self::PRICE]);
                    $price[self::CURRENCYCODE] = $priceParts[0];
                    $price[self::CENTAMOUNT] = (int)$priceParts[1];
                    $lineItem[self::PRICE] = [self::VALUE=>$price];
                }
                if (isset($lineItem[self::QUANTITY])) {
                    $lineItem[self::QUANTITY] = (int) $lineItem[self::QUANTITY];
                }
                if (isset($lineItem[self::PRODUCTID]) &&  !isset($lineItem[self::VARIANTID])) {
                    unset($lineItem[self::PRODUCTID]);
                }
                if (isset($lineItem[self::LINEITEMS])) {
                    unset($lineItem[self::LINEITEMS]);
                }
                if (isset($lineItem[self::ID])) {
                    unset($lineItem[self::ID]);
                }
            }
        }
        return $data;
    }
    public function getOrderObjsFromArr($OrderArr)
    {
        if (isset($OrderArr[self::TOTALPRICE])) {
            $OrderArr[self::TOTALPRICE] = Money::fromArray($OrderArr[self::TOTALPRICE]);
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
        } elseif (isset($OrderArr[self::BILLINGADDRESS])) {
            unset($OrderArr[self::BILLINGADDRESS]);
        }
        if (isset($OrderArr[self::SHIPPINGADDRESS]) && !empty($OrderArr[self::SHIPPINGADDRESS])) {
            $OrderArr[self::SHIPPINGADDRESS] = Address::fromArray($OrderArr[self::SHIPPINGADDRESS]);
        } elseif (isset($OrderArr[self::SHIPPINGADDRESS])) {
            unset($OrderArr[self::SHIPPINGADDRESS]);
        }
        return $OrderArr;
    }
}
