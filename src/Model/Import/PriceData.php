<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 12/01/17
 * Time: 13:54
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Core\Client;
use Commercetools\Core\Request\CustomerGroups\CustomerGroupQueryRequest;
use Commercetools\Commons\Helper\QueryHelper;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Core\Model\Common\Price;
use Commercetools\Core\Model\Common\PriceDraft;

class PriceData
{
    const ID= 'id';
    const CUSTOMERGROUP='customerGroup';
    const NAME='name';
    const COUNTRY='country';
    const CURRENCYCODE='currencyCode';
    const CENTAMOUNT='centAmount';
    const VALUE= 'value';
    const CHANNEL='channel';
    const OBJ='obj';
    const KEY='key';

    private $productVariantDraftPricesByUniqueKey;
    private $customerGroups;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->customerGroups = $this->getCustomerGroups();
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
    public function getPriceObjByUinqueKey($key)
    {
        return $this->productVariantDraftPricesByUniqueKey[$key];
    }

    public function mapPriceFromData($data)
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

    public function getPriceToAdd($productVariantDraftPricesByUniqueKey, $ProductPricesByUniqueKey)
    {
        $pricesToAdd=[];
        foreach ($productVariantDraftPricesByUniqueKey as $key => $value) {
            if (!isset($ProductPricesByUniqueKey[$key])) {
                $pricesToAdd[] = $key;
            }
        }
        return $pricesToAdd;
    }
    public function getPriceToRemove($productVariantDraftPricesByUniqueKey, $ProductPricesById)
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
    public function getPriceToChange($productVariantDraftPricesByUniqueKey, $ProductPricesById)
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
    public function getProductVariantDraftPricesByUniqueKey($productVariantDraftPrices)
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
    public function getProductPricesByUniqueKeyAndId($ProductPrices)
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
}
