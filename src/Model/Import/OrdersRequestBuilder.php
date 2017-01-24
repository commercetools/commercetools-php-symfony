<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 23/01/17
 * Time: 14:14
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Cart\LineItemDraft;
use Commercetools\Core\Model\Cart\LineItemDraftCollection;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Core\Model\Common\PriceDraft;
use Commercetools\Core\Model\Order\ImportOrder;
use Commercetools\Core\Model\Order\LineItemImportDraft;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Order\ProductVariantImportDraft;
use Commercetools\Core\Request\Orders\OrderImportRequest;
use Commercetools\Core\Request\Orders\OrderQueryRequest;

class OrdersRequestBuilder extends AbstractRequestBuilder
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

    private $client;
    private $orderDataObj;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->orderDataObj= new OrderData();
    }

    private function getOrdersByIdentifiedByColumn($orders, $identifiedByColumn)
    {
        $parts = explode('.', $identifiedByColumn);
        $ordersArr=[];
        foreach ($orders as $order) {
            switch ($parts[0]) {
                case self::ID:
                    $ordersArr[$order->toArray()[$identifiedByColumn]] = $order;
                    break;
            }
        }
        return $ordersArr;
    }
    private function getOrdersDataByIdentifiedByColumn($ordersData, $identifiedByColumn)
    {
        $ordersDataArr=[];
        $parts = explode('.', $identifiedByColumn);
        foreach ($ordersData as $orderData) {
            switch ($parts[0]) {
                case self::ID:
                    $ordersDataArr[$orderData[$identifiedByColumn]] = $orderData;
                    break;
            }
        }
        return $ordersDataArr;
    }
    /**
     * @param $ordersData
     * @param $identifiedByColumn
     * @return ClientRequestInterface[]|null
     */
    public function createRequest($ordersData, $identifiedByColumn)
    {
        $requests=[];
        $request = OrderQueryRequest::of()
            ->where(
                sprintf(
                    $this->getIdentifierQuery($identifiedByColumn),
                    $this->getIdentifierFromArray($identifiedByColumn, $ordersData)
                )
            )

            ->limit(500);
        $response = $request->executeWithClient($this->client);
        $orders = $request->mapFromResponse($response);

        $ordersArr=$this->getOrdersByIdentifiedByColumn($orders, $identifiedByColumn);
        $ordersDataArr=$this->getOrdersDataByIdentifiedByColumn($ordersData, $identifiedByColumn);
        /**
         * @var Order $order
         */
        foreach ($ordersDataArr as $key => $orderData) {
            if (isset($ordersArr[$key])) {
//                $order = $ordersArr[$key];
//                $request = $this->getUpdateRequest($order, $orderData);
//                if (!$request->hasActions()) {
//                    $request = null;
//                }
//                $requests []=$request;
            } else {
                $request  = $this->getCreateRequest($orderData);
                $requests []= $request;
            }
        }
        return $requests;
    }

    private function getCreateRequest($orderDataArray)
    {
        $orderDataArray= $this->orderDataObj->mapOrderFromData($orderDataArray);
        $orderDataobj= $this->orderDataObj->getOrderObjsFromArr($orderDataArray);

        $order = ImportOrder::fromArray($orderDataobj);
        $request = OrderImportRequest::ofImportOrder($order);
        return $request;
    }

    public function getIdentifierQuery($identifierName, $query = ' in (%s)')
    {
        $value = '';
        switch ($identifierName) {
            case self::ID:
                $value = $identifierName. $query;
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
                case self::ID:
                    $value [] = '"'.$row[$parts[0]].'"';
                    break;
            }
        }
        return implode(',', $value);
    }
}
