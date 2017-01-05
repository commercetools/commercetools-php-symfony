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

        $productData = [];
        foreach ($data as $key => $row) {
            if ($key == 0) {
                $productData = $row;
                $productData[self::VARIANTS][] = $row;
                continue;
            }
            if (!empty($row[self::ID])) {
                $request=$this->requestBuilder->createRequest($productData, $this->identifiedByColumn);
                if ($request instanceof ClientRequestInterface) {
                    $this->client->addBatchRequest($request);
                    $this->requests++;
                }
                $this->execute();
                $productData = $row;
                $productData[self::VARIANTS][] = $row; // TODO remove with break ;)
                break;
            }
            $productData[self::VARIANTS][] = $row;
        }
        $request=$this->requestBuilder->createRequest($productData, $this->identifiedByColumn);
        if ($request instanceof ClientRequestInterface) {
            $this->client->addBatchRequest($request);
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
        $this->identifiedByColumn = $identifiedByColumn;
    }
}
