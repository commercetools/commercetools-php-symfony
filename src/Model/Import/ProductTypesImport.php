<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 08/11/16
 * Time: 13:44
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Core\Client;
use Commercetools\Core\Model\ProductType\ProductType;
use Commercetools\Core\Model\ProductType\ProductTypeDraft;
use Commercetools\Core\Request\ProductTypes\ProductTypeCreateRequest;
use Commercetools\Core\Request\ProductTypes\ProductTypeQueryRequest;
use Commercetools\Core\Request\ProductTypes\ProductTypeUpdateByKeyRequest;
use Commercetools\Core\Request\ProductTypes\ProductTypeByKeyGetRequest;
use Commercetools\Core\Request\ProductTypes\Command\ProductTypeSetKeyAction;
use Commercetools\Core\Request\ProductTypes\Command\ProductTypeChangeNameAction;
use Commercetools\Core\Request\ProductTypes\Command\ProductTypeChangeDescriptionAction;

class ProductTypesImport
{
    private $client;

    private $requests=0;

    private $identifier;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function import($data)
    {
        foreach ($data as $type) {
            $this->createRequest($type);
            $this->execute();
        }
        $this->execute(true);
    }

    private function createRequest($productTypesData)
    {
        $request = ProductTypeQueryRequest::of()
            ->where(sprintf($this->getIdentifierQuery($this->identifier), $productTypesData[$this->identifier]));

        $response = $request->executeWithClient($this->client);

        $productTypes = $request->mapFromResponse($response);

        if (count($productTypes) > 0) {
            $productType = $productTypes->current();
            $request = $this->getUpdateRequest($productType, $productTypesData);
            if ($request->hasActions()) {
                $this->requests++;
                $this->client->addBatchRequest($request);
            }
        } else {
            $request = $this->getCreateRequest($productTypesData);
            $this->requests++;
            $this->client->addBatchRequest($request);
        }
    }

    private function getIdentifierQuery($identifierName, $query = '= "%s"')
    {
        $value = $identifierName.$query;
        return $value;
    }

    private function getCreateRequest($productTypesData)
    {
        $productType = ProductTypeDraft::fromArray($productTypesData);
        $request = ProductTypeCreateRequest::ofDraft($productType);
        return $request;
    }

    public function getUpdateRequest(ProductType $productType, $productTypeData)
    {
        $request = ProductTypeUpdateByKeyRequest::ofKeyAndVersion($productType->getKey(), $productType->getVersion());

        $actions = [];
        foreach ($productTypeData as $heading => $data) {
            switch ($heading) {
                case 'key':
                    if ($productType->getKey() != $data) {
                        $actions[$heading] = ProductTypeSetKeyAction::ofKey($data);
                    }
                    break;
                case 'name':
                    if ($productType->getName() != $data) {
                        $actions[$heading] = ProductTypeChangeNameAction::ofName($data);
                    }
                    break;
                case 'description':
                    if ($productType->getDescription() != $data) {
                        $actions[$heading] = ProductTypeChangeDescriptionAction::ofDescription($data);
                    }
                    break;
            }
        }
        $request->setActions($actions);
        return $request;
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
