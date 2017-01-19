<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 21/11/16
 * Time: 11:41
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Core\Client;
use Commercetools\Core\Request\ClientRequestInterface;

class ProductsImport
{
    const VARIANTS='variants';
    const ID='id';

    /**
     * @var Client
     */
    private $client;
    private $requestBuilder;
    private $identifiedByColumn;
    private $packedRequests = 10;
    private $requests;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->requestBuilder = new ProductsRequestBuilder($this->client);
    }

    public function import($data)
    {
        $headings = null;
        $import = null;

        $productsDataArr = [];
        $count = 0;
        $productData = [];
        foreach ($data as $key => $row) {
            if ($key == 0) {
                $productData = $row;
                $productData[self::VARIANTS][] = $row;
                continue;
            }
            if (!empty($row[self::ID])) {
                $productsDataArr[]=$productData;
                $count++;
                if ($count >= $this->packedRequests) {
                    $requests = $this->requestBuilder->createRequest($productsDataArr, $this->identifiedByColumn);
                    $productsDataArr=[];
                    $count = 0;
                    foreach ($requests as $request) {
                        if ($request instanceof ClientRequestInterface) {
                            $this->client->addBatchRequest($request);
                            $this->requests++;
                        }
                        $this->execute();
                    }
                }
                $productData = $row;
//                $productData[self::VARIANTS][] = $row; // TODO remove with break ;)
//                break; //TODO remove
            }
            $productData[self::VARIANTS][] = $row;
        }
        $productsDataArr[]=$productData;
        $requests=$this->requestBuilder->createRequest($productsDataArr, $this->identifiedByColumn);
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
