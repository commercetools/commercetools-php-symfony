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

    private $identifiedByColumn;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function import($data)
    {
        $headings = null;
        $import = null;
        $parentIds = [];
        $identifiers = [];
        foreach ($data as $row) {
            $parentValue=$row['parentId'];
            $identifierValue=$this->getIdentifierFromArray($this->identifiedByColumn, $row);
            if (!empty($parentValue)) {
                $parentIds[$parentValue] = $parentValue;
                $identifiers[$identifierValue] = $parentValue;
            }
            $this->createRequest($row);
            $this->execute();
        }

        $this->execute(true);

        $this->setParents($parentIds, $identifiers);
    }

    private function createRequest($categoryData)
    {
        $identifier = $this->getIdentifierFromArray($this->identifiedByColumn, $categoryData);

        $request = CategoryQueryRequest::of()
            ->where(sprintf($this->getIdentifierQuery($this->identifiedByColumn), $identifier))
            ->limit(1);
        $response = $request->executeWithClient($this->client);

        $categories = $request->mapFromResponse($response);

        if (count($categories) > 0) {
            /**
             * @var Category $category
             */
            $category = $categories->current();
            $request = $this->getUpdateRequest($category, $categoryData);
            if ($request->hasActions()) {
                $this->requests++;
                $this->client->addBatchRequest($request);
            }
        } else {
            $request = $this->getCreateRequest($categoryData);
            $this->requests++;
            $this->client->addBatchRequest($request);
        }

        return null;
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

    private function getUpdateRequest(Category $category, $categoryData)
    {
        $request = CategoryUpdateRequest::ofIdAndVersion($category->getId(), $category->getVersion());

        $actions = [];
        foreach ($categoryData as $heading => $data) {
            switch ($heading) {
                case 'externalId':
                    if ($category->getExternalId() != $data) {
                        $actions[$heading] = CategorySetExternalIdAction::ofExternalId($data);
                    }
                    break;
                case 'name':
                    if (!$this->compareLocalizedString($category->getName()->toArray(), $data)) {
                        $actions[$heading] = CategoryChangeNameAction::ofName(
                            LocalizedString::fromArray($data)
                        );
                    }
                    break;
                case 'slug':
                    if (!$this->compareLocalizedString($category->getSlug()->toArray(), $data)) {
                        $actions[$heading] = CategoryChangeSlugAction::ofSlug(
                            LocalizedString::fromArray($data)
                        );
                    }
                    break;
            }
        }
        $request->setActions($actions);
        return $request;
    }

    private function getCreateRequest($categoryData)
    {
        $category = CategoryDraft::fromArray($categoryData);

        $request = CategoryCreateRequest::ofDraft($category);
        return $request;
    }

    private function compareLocalizedString($a, $b)
    {
        foreach ($a as $locale => $str) {
            if (!isset($b[$locale]) || $b[$locale] !== $str) {
                return false;
            }
        }

        return true;
    }

    private function setParents($parentIds, $identifiers)
    {
        $chunks = array_chunk($parentIds, static::CHUNK_SIZE);

        $parentRefs = [];
        foreach ($chunks as $chunk) {
            $request = CategoryQueryRequest::of()
                ->where(
                    sprintf($this->getIdentifierQuery($this->identifiedByColumn, ' in ("%s")'), join('", "', $chunk))
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
                    sprintf($this->getIdentifierQuery($this->identifiedByColumn, ' in ("%s")'), join('", "', $chunk))
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

    private function getIdentifierFromArray($identifierName, $row)
    {
        $parts = explode('.', $identifierName);
        $value="";
        switch ($parts[0]) {
            case "slug":
                $value = $row[$parts[0]][$parts[1]];
                break;
            case "externalId":
            case "id":
                $value = $row[$parts[0]];
                break;
        }
        return $value;
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

    private function getIdentifierQuery($identifierName, $query = '= "%s"')
    {
        $parts = explode('.', $identifierName);
        $value="";
        switch ($parts[0]) {
            case "slug":
                $value = $parts[0].'('.$parts[1]. $query . ')';
                break;
            case "externalId":
            case "id":
                $value = $parts[0].$query;
                break;
        }
        return $value;
    }

    public function setOptions($identifiedByColumn)
    {
        $this->identifiedByColumn = $identifiedByColumn;
    }
}
