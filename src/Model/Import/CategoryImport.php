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
    private $headings;

    private $requests;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function import($file)
    {
        $headings = null;
        $import = null;
        $parentIds = [];
        $externalIds = [];
        foreach ($file as $data) {
            if (empty($data)) {
                continue;
            }
            if (is_null($this->headings)) {
                $this->headings = array_flip($data);
                continue;
            }

            $categoryData = $this->arrange($data);
            if (!empty(($categoryData['parentId']))) {
                $parentIds[$categoryData['parentId']] = $categoryData['parentId'];
                $externalIds[$categoryData['externalId']] = $categoryData['parentId'];
            }
            $this->createRequest($categoryData);
            $this->execute();
        }

        $this->execute(true);

        $this->setParents($parentIds, $externalIds);
    }

    private function createRequest($categoryData)
    {
        $externalId = $categoryData['externalId'];

        $request = CategoryQueryRequest::of()->where(sprintf('externalId="%s"', $externalId))->limit(1);
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

    private function arrange($data)
    {
        $category = [];
        foreach ($this->headings as $heading => $column) {
            $headingParts = explode('.', $heading);

            $category = $this->arrangeData($headingParts, $category, $data[$column]);
        }

        return $category;
    }

    private function arrangeData($parts, $context, $data)
    {
        $actualPart = array_shift($parts);

        if (count($parts) > 0) {
            if (!isset($context[$actualPart])) {
                $context[$actualPart] = [];
            }
            $context[$actualPart] = $this->arrangeData($parts, $context[$actualPart], $data);
        } else {
            $context[$actualPart] = $data;
        }

        return $context;
    }

    private function setParents($parentIds, $externalIds)
    {
        $chunks = array_chunk($parentIds, static::CHUNK_SIZE);

        $parentRefs = [];
        foreach ($chunks as $chunk) {
            $request = CategoryQueryRequest::of()
                ->where(sprintf('externalId in ("%s")', join('", "', $chunk)))
                ->limit(static::CHUNK_SIZE);
            $response = $request->executeWithClient($this->client);
            $categories = $request->mapFromResponse($response);

            foreach ($categories as $category) {
                $parentRefs[$category->getExternalId()] = $category->getReference();
            }
        }
        $chunks = array_chunk(array_keys($externalIds), static::CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            $request = CategoryQueryRequest::of()
                ->where(sprintf('externalId in ("%s")', join('", "', $chunk)))
                ->limit(static::CHUNK_SIZE);

            $response = $request->executeWithClient($this->client);
            $categories = $request->mapFromResponse($response);

            foreach ($categories as $category) {
                $parentExternalId = $externalIds[$category->getExternalId()];
                if ($category->getParent()->getId() == $parentRefs[$parentExternalId]->getId()) {
                    continue;
                }
                $request = CategoryUpdateRequest::ofIdAndVersion($category->getId(), $category->getVersion());
                $request->addAction(CategoryChangeParentAction::ofParentCategory($parentRefs[$parentExternalId]));
                $response = $request->executeWithClient($this->client);
            }
        }
    }
}