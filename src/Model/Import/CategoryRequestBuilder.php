<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 10/11/16
 * Time: 10:03
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Core\Model\Category\Category;
use Commercetools\Core\Model\Category\CategoryDraft;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Request\Categories\CategoryCreateRequest;
use Commercetools\Core\Request\Categories\CategoryQueryRequest;
use Commercetools\Core\Request\Categories\CategoryUpdateRequest;
use Commercetools\Core\Request\Categories\Command\CategoryChangeNameAction;
use Commercetools\Core\Request\Categories\Command\CategoryChangeOrderHintAction;
use Commercetools\Core\Request\Categories\Command\CategoryChangeParentAction;
use Commercetools\Core\Request\Categories\Command\CategoryChangeSlugAction;
use Commercetools\Core\Request\Categories\Command\CategorySetDescriptionAction;
use Commercetools\Core\Request\Categories\Command\CategorySetExternalIdAction;
use Commercetools\Core\Request\Categories\Command\CategorySetMetaDescriptionAction;
use Commercetools\Core\Request\Categories\Command\CategorySetMetaKeywordsAction;
use Commercetools\Core\Request\Categories\Command\CategorySetMetaTitleAction;
use Commercetools\Core\Request\ClientRequestInterface;

class CategoryRequestBuilder
{

    public function __construct($client)
    {
        $this->client = $client;
    }

    /**
     * @param $categoryData
     * @param $identifiedByColumn
     * @param $identifier
     * @return ClientRequestInterface
     */
    public function createRequest($categoryData, $identifiedByColumn)
    {
        $request = CategoryQueryRequest::of()
            ->where(
                sprintf(
                    $this->getIdentifierQuery($identifiedByColumn),
                    $this->getIdentifierFromArray($identifiedByColumn, $categoryData)
                )
            )
            ->limit(1);
        $response = $request->executeWithClient($this->client);

        $categories = $request->mapFromResponse($response);

        if (count($categories) > 0) {
            /**
             * @var Category $category
             */
            $category = $categories->current();
            $request = $this->getUpdateRequest($category, $categoryData);
        } else {
            $request = $this->getCreateRequest($categoryData);
        }

        return $request;
    }

    public function getIdentifierQuery($identifierName, $query = '= "%s"')
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

    private function getUpdateRequest(Category $category, $categoryData)
    {
        $request = CategoryUpdateRequest::ofIdAndVersion($category->getId(), $category->getVersion());

        $actions = [];
        foreach ($categoryData as $heading => $data) {
            switch ($heading) {
                case 'externalId':
                    if (!$category->getExternalId() || $category->getExternalId() != $data) {
                        $actions[$heading] = CategorySetExternalIdAction::ofExternalId($data);
                    }
                    break;
                case 'name':
                    if (!$category->getName() || !$this->compareLocalizedString($category->getName()->toArray(), $data)) {
                        $actions[$heading] = CategoryChangeNameAction::ofName(
                            LocalizedString::fromArray($data)
                        );
                    }
                    break;
                case 'slug':
                    if (!$category->getSlug() || !$this->compareLocalizedString($category->getSlug()->toArray(), $data)) {
                        $actions[$heading] = CategoryChangeSlugAction::ofSlug(
                            LocalizedString::fromArray($data)
                        );
                    }
                    break;
                case 'description':
                    if (!$category->getDescription() || !$this->compareLocalizedString($category->getDescription()->toArray(), $data)) {
                        $actions[$heading] = CategorySetDescriptionAction::ofDescription(
                            LocalizedString::fromArray($data)
                        );
                    }
                    break;
                case 'orderHint':
                    if (!$category->getOrderHint() || $category->getOrderHint() != $data) {
                        $actions[$heading] = CategoryChangeOrderHintAction::ofOrderHint($data);
                    }
                    break;
                case 'metaTitle':
                    if (!$category->getMetaTitle() || !$this->compareLocalizedString($category->getMetaTitle()->toArray(), $data)) {
                        $actions[$heading] = CategorySetMetaTitleAction::of()->setMetaTitle(LocalizedString::fromArray($data));
                    }
                    break;
                case 'metaDescription':
                    if (!$category->getmetaDescription() || !$this->compareLocalizedString($category->getmetaDescription()->toArray(), $data)) {
                        $actions[$heading] = CategorySetMetaDescriptionAction::of()->setmetaDescription(LocalizedString::fromArray($data));
                    }
                    break;
                case 'metaKeywords':
                    if (!$category->getMetaKeywords() || !$this->compareLocalizedString($category->getMetaKeywords()->toArray(), $data)) {
                        $actions[$heading] = CategorySetMetaKeywordsAction::of()->setMetaKeywords(LocalizedString::fromArray($data));
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

    public function getIdentifierFromArray($identifierName, $row)
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
}
