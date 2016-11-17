<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 08/11/16
 * Time: 13:44
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Core\Client;

class ProductTypesImport
{
    private $client;

    private $requests=0;

    private $identifier;

    private $requestBuilder;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->requestBuilder = new ProductTypeRequestBuilder($this->client);
    }

    public function import($data)
    {
        foreach ($data as $type) {
            $this->client->addBatchRequest(
                $this->requestBuilder->createRequest($type, $this->identifier)
            );
            $this->requests++;
            $this->execute();
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

    public function setOptions($identifiedByColumn)
    {
        $this->identifier = $identifiedByColumn;
    }
}
