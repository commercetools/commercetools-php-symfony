<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 06/02/17
 * Time: 15:37
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Core\Client;
use Commercetools\Core\Request\ClientRequestInterface;

class StatesImport
{
    private $client;
    private $requestBuilder;
    private $identifiedByColumn;
    private $packedRequests = 25;
    private $requests =0;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->requestBuilder = new StatesRequestBuilder($this->client);
    }
    public function import($data)
    {
        $statesDataArr = [];
        $count = 0;
        foreach ($data as $state) {
            $statesDataArr[]=$state;
            $count++;
            if ($count >= $this->packedRequests) {
                $requests = $this->requestBuilder->createRequest($statesDataArr, $this->identifiedByColumn);
                $statesDataArr=[];
                $count = 0;
                foreach ($requests as $request) {
                    if ($request instanceof ClientRequestInterface) {
                        $this->client->addBatchRequest($request);
                        $this->requests++;
                    }
                    $this->execute();
                }
            }
        }
        $statesDataArr[]=$state;
        $requests=$this->requestBuilder->createRequest($statesDataArr, $this->identifiedByColumn);
        foreach ($requests as $request) {
            if ($request instanceof ClientRequestInterface) {
                $this->client->addBatchRequest($request);
            }
        }
        $this->execute(true);

        if ($this->requestBuilder->getSecondPassFlag()) {
            $requests = $this->requestBuilder->getTransitionsUpdate();
            foreach ($requests as $request) {
                if ($request instanceof ClientRequestInterface) {
                    $this->client->addBatchRequest($request);
                }
            }
            $this->execute(true);
        }
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
