<?php

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Category\Category;
use Commercetools\Core\Model\Category\CategoryDraft;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Request\Categories\CategoryCreateRequest;
use Commercetools\Core\Request\Categories\CategoryQueryRequest;
use Commercetools\Core\Request\Categories\CategoryUpdateRequest;
use Commercetools\Core\Request\Categories\Command\CategoryChangeNameAction;
use Commercetools\Core\Request\Categories\Command\CategoryChangeParentAction;
use Commercetools\Core\Request\Categories\Command\CategoryChangeSlugAction;
use Commercetools\Core\Request\Categories\Command\CategorySetExternalIdAction;

class CategoryImport
{
    const CHUNK_SIZE = 25;

    /**
     * @var Client
     */
    private $client;

    private $requests;

    private $requestBuilder;

    private $identifiedByColumn;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->requestBuilder = new CategoryRequestBuilder($this->client);
    }

    public function import($data)
    {
        $headings = null;
        $import = null;
        $parentIds = [];
        $identifiers = [];
        foreach ($data as $row) {
            $parentValue=$row['parentId'];
            $identifierValue=$this->requestBuilder->getIdentifierFromArray($this->identifiedByColumn, $row);
            if (!empty($parentValue)) {
                $parentIds[$parentValue] = $parentValue;
                $identifiers[$identifierValue] = $parentValue;
            }
            $this->client->addBatchRequest(
                $this->requestBuilder->createRequest($row, $this->identifiedByColumn)
            );
            $this->requests++;
            $this->execute();
        }

        $this->execute(true);

        $this->setParents($parentIds, $identifiers);
    }

    private function execute($force = false)
    {
        $responses = null;
        if ($force || $this->requests > 25) {
            $responses = $this->client->executeBatch();
            var_dump($responses);
            $this->requests = 0;
        }

        return $responses;
    }

    private function setParents($parentIds, $identifiers)
    {
        $chunks = array_chunk($parentIds, static::CHUNK_SIZE);

        $parentRefs = [];
        foreach ($chunks as $chunk) {
            $request = CategoryQueryRequest::of()
                ->where(
                    sprintf($this->requestBuilder
                        ->getIdentifierQuery($this->identifiedByColumn, ' in ("%s")'), join('", "', $chunk))
                )
                ->limit(static::CHUNK_SIZE);
            $response = $request->executeWithClient($this->client);
            $categories = $request->mapFromResponse($response);

            foreach ($categories as $category) {
                $identifier = $this->getIdentifierFromCategory($this->identifiedByColumn, $category);
                $parentRefs[$identifier] = $category->getReference();
            }
        }
        $chunks = array_chunk(array_keys($identifiers), static::CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            $request = CategoryQueryRequest::of()
                ->where(
                    sprintf($this->requestBuilder
                        ->getIdentifierQuery($this->identifiedByColumn, ' in ("%s")'), join('", "', $chunk))
                )
                ->limit(static::CHUNK_SIZE);

            $response = $request->executeWithClient($this->client);
            $categories = $request->mapFromResponse($response);

            foreach ($categories as $category) {
                $identifier = $this->getIdentifierFromCategory($this->identifiedByColumn, $category);
                $parentId = $identifiers[$identifier];
                if ($category->getParent()->getId() == $parentRefs[$parentId]->getId()) {
                    continue;
                }
                $request = CategoryUpdateRequest::ofIdAndVersion($category->getId(), $category->getVersion());
                $request->addAction(CategoryChangeParentAction::ofParentCategory($parentRefs[$parentId]));
                $response = $request->executeWithClient($this->client);
            }
        }
    }

    private function getIdentifierFromCategory($identifierName, Category $category)
    {
        $parts = explode('.', $identifierName);
        $value="";
        switch ($parts[0]) {
            case "slug":
                $locale = $parts[1];
                $value = $category->getSlug()->$locale;
                break;
            case "externalId":
                $value = $category->getExternalId();
                break;
            case "id":
                $value = $category->getId();
                break;
        }
        return $value;
    }

    public function setOptions($identifiedByColumn)
    {
        $this->identifiedByColumn = $identifiedByColumn;
    }
}
