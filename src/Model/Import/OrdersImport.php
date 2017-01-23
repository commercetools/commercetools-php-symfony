<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 23/01/17
 * Time: 13:37
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Core\Client;
use Commercetools\Core\Request\ClientRequestInterface;

class OrdersImport
{
    const TOTALPRICE='totalPrice';
    const LINENITEMS='lineItems';

    private $client;
    private $requestBuilder;
    private $identifiedByColumn;
    private $packedRequests;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->requestBuilder = new OrdersRequestBuilder($this->client);
    }

    public function import($data)
    {
        $ordersDataArr = [];
        $count = 0;
        $orderData = [];
        foreach ($data as $key => $row) {
            if ($key == 0 && !empty($row[self::TOTALPRICE])) {
                $orderData = $row;
                continue;
            }
            if (!empty($row[self::TOTALPRICE])) {
                $ordersDataArr[]=$orderData;
                $count++;
                if ($count >= $this->packedRequests) {
                    $requests = $this->requestBuilder->createRequest($ordersDataArr, $this->identifiedByColumn);
                    $ordersDataArr=[];
                    $count = 0;
                    foreach ($requests as $request) {
                        if ($request instanceof ClientRequestInterface) {
                            $this->client->addBatchRequest($request);
                            $this->requests++;
                        }
                        $this->execute();
                    }
                }
                $orderData = $row;
            }
            $orderData[self::LINENITEMS][] = $row;
        }
        $ordersDataArr[]=$orderData;
        $requests=$this->requestBuilder->createRequest($ordersDataArr, $this->identifiedByColumn);
        foreach ($requests as $request) {
            if ($request instanceof ClientRequestInterface) {
                $this->client->addBatchRequest($request);
            }
        }
        $this->execute(true);
    }

    private function execute($force = false)
    {
        $responses = null;
        if ($force || $this->requests > 25) {
            $responses = $this->client->executeBatch();
            $this->requests = 0;
        }

        return $responses;
    }
    public function setOptions($identifiedByColumn, $packedRequests = 10)
    {
        $this->identifiedByColumn = $identifiedByColumn;
        $this->packedRequests = $packedRequests;
    }
}
