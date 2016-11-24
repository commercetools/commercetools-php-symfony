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
use Commercetools\Core\Model\Type\FieldDefinition;
use Commercetools\Core\Request\Carts\Command\CartSetCustomLineItemCustomFieldAction;
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
use Commercetools\Core\Request\CustomField\Command\SetCustomFieldAction;
use Commercetools\Core\Request\CustomField\Command\SetCustomTypeAction;
use Commercetools\Core\Model\Type\TypeReference;

class CategoryRequestBuilder extends AbstractRequestBuilder
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
        $intersect = $this->arrayIntersectRecursive($category->toArray(), $categoryData);
        $addToCategory = $this->arrayDiffRecursive($categoryData, $intersect);
        $removeFromCategory = $this->arrayDiffRecursive($intersect, $category->toArray());
        $request = CategoryUpdateRequest::ofIdAndVersion($category->getId(), $category->getVersion());

        $actions = [];
        foreach ($addToCategory as $heading => $data) {
            switch ($heading) {
                case 'externalId':
                    $actions[$heading] = CategorySetExternalIdAction::ofExternalId($data);
                    break;
                case 'name':
                    $actions[$heading] = CategoryChangeNameAction::ofName(
                        LocalizedString::fromArray($categoryData[$heading])
                    );
                    break;
                case 'slug':
                    $actions[$heading] = CategoryChangeSlugAction::ofSlug(
                        LocalizedString::fromArray($categoryData[$heading])
                    );
                    break;
                case 'description':
                    $actions[$heading] = CategorySetDescriptionAction::ofDescription(
                        LocalizedString::fromArray($categoryData[$heading])
                    );
                    break;
                case 'orderHint':
                    $actions[$heading] = CategoryChangeOrderHintAction::ofOrderHint($data);
                    break;
                case 'metaTitle':
                    $actions[$heading] = CategorySetMetaTitleAction::of()->setMetaTitle(LocalizedString::fromArray($categoryData[$heading]));
                    break;
                case 'metaDescription':
                    $actions[$heading] = CategorySetMetaDescriptionAction::of()->setmetaDescription(LocalizedString::fromArray($categoryData[$heading]));
                    break;
                case 'metaKeywords':
                    $actions[$heading] = CategorySetMetaKeywordsAction::of()->setMetaKeywords(LocalizedString::fromArray($categoryData[$heading]));
                    break;
                case 'custom':
                    if ($data['type']) {
                        $actions[$heading.'type'] =
                            SetCustomTypeAction::ofType(TypeReference::ofTypeAndKey('type', $data['type']['key']));
                    }
                    if ($data['fields']) {
                        foreach ($data['fields'] as $fieldName => $value) {
                            $actions[$heading.'field'.$fieldName] = SetCustomFieldAction::ofName($fieldName)->setValue($value);
                        }
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
