<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 23/01/17
 * Time: 14:14
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Cart\LineItemDraftCollection;
use Commercetools\Core\Model\Order\ImportOrder;
use Commercetools\Core\Model\Order\LineItemImportDraft;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Request\Orders\OrderImportRequest;
use Commercetools\Core\Request\Orders\OrderQueryRequest;

class OrdersRequestBuilder extends AbstractRequestBuilder
{
    const ID ='id';
    const NAME ='name';
    const VARIANT ='variant';
    const LINEITEMS ='lineItems';
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
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
        var_dump($orderDataArray);
//        $orderDataArray[self::LINEITEMS] = LineItemDraftCollection::fromArray($this->mapLineItemFromData($orderDataArray[self::LINEITEMS]));
////        var_dump($orderDataArray[self::LINEITEMS]);exit;
//        $order = ImportOrder::fromArray($orderDataArray);
//        $request = OrderImportRequest::ofImportOrder($order);
//        return $request;
    }

    private function mapLineItemFromData($data)
    {
        if (isset($data[self::NAME])) {
            unset($data[self::NAME]);
        }
        if (isset($data[self::VARIANT])) {
            unset($data[self::VARIANT]);
        }
        foreach ($data as &$lineItem) {
            if (isset($lineItem[self::LINEITEMS])) {
                if (isset($lineItem[self::LINEITEMS][self::ID])) {
                    unset($lineItem[self::LINEITEMS][self::ID]);
                }
                $lineItem = $lineItem[self::LINEITEMS];
            }
        }
        return $data;
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
