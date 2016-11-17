<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 10/11/16
 * Time: 16:45
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Core\Model\Common\Attribute;
use Commercetools\Core\Model\ProductType\AttributeDefinition;
use Commercetools\Core\Model\ProductType\AttributeDefinitionCollection;
use Commercetools\Core\Model\ProductType\EnumType;
use Commercetools\Core\Model\ProductType\LocalizedEnumType;
use Commercetools\Core\Model\Common\Enum;
use Commercetools\Core\Model\ProductType\ProductType;
use Commercetools\Core\Model\ProductType\ProductTypeDraft;
use Commercetools\Core\Model\ProductType\SetType;
use Commercetools\Core\Request\ClientRequestInterface;
use Commercetools\Core\Request\ProductTypes\Command\ProductTypeAddAttributeDefinitionAction;
use Commercetools\Core\Request\ProductTypes\Command\ProductTypeAddLocalizedEnumValueAction;
use Commercetools\Core\Request\ProductTypes\Command\ProductTypeAddPlainEnumValueAction;
use Commercetools\Core\Request\ProductTypes\Command\ProductTypeChangeIsSearchableAction;
use Commercetools\Core\Request\ProductTypes\Command\ProductTypeChangeLabelAction;
use Commercetools\Core\Request\ProductTypes\Command\ProductTypeChangeLocalizedEnumLabelAction;
use Commercetools\Core\Request\ProductTypes\Command\ProductTypeChangePlainEnumLabelAction;
use Commercetools\Core\Request\ProductTypes\Command\ProductTypeRemoveAttributeDefinitionAction;
use Commercetools\Core\Request\ProductTypes\Command\ProductTypeSetInputTipAction;
use Commercetools\Core\Request\ProductTypes\ProductTypeCreateRequest;
use Commercetools\Core\Request\ProductTypes\ProductTypeQueryRequest;
use Commercetools\Core\Request\ProductTypes\ProductTypeUpdateByKeyRequest;
use Commercetools\Core\Request\ProductTypes\ProductTypeByKeyGetRequest;
use Commercetools\Core\Request\ProductTypes\Command\ProductTypeSetKeyAction;
use Commercetools\Core\Request\ProductTypes\Command\ProductTypeChangeNameAction;
use Commercetools\Core\Request\ProductTypes\Command\ProductTypeChangeDescriptionAction;
use Commercetools\Core\Request\ProductTypes\ProductTypeUpdateRequest;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\Common\LocalizedEnum;

class ProductTypeRequestBuilder
{
    public function __construct($client)
    {
        $this->client = $client;
    }
    public function createRequest($productTypesData, $identifier)
    {
        $request = ProductTypeQueryRequest::of()
            ->where(sprintf($this->getIdentifierQuery($identifier), $productTypesData[$identifier]));

        $response = $request->executeWithClient($this->client);
        $productTypes = $request->mapFromResponse($response);

        if (count($productTypes) > 0) {
            $productType = $productTypes->current();
            $request = $this->getUpdateRequest($productType, $productTypesData);
        } else {
            $request = $this->getCreateRequest($productTypesData);
        }
        return $request;
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
        $request = ProductTypeUpdateRequest::ofIdAndVersion($productType->getId(), $productType->getVersion());

        $actions = [];
        foreach ($productTypeData as $heading => $data) {
            switch ($heading) {
                case 'key':
                    if (!$productType->getKey() || $productType->getKey() != $data) {
                        $actions[$heading] = ProductTypeSetKeyAction::ofKey($data);
                    }
                    break;
                case 'name':
                    if (!$productType->getName() || $productType->getName() != $data) {
                        $actions[$heading] = ProductTypeChangeNameAction::ofName($data);
                    }
                    break;
                case 'description':
                    if (! $productType->getDescription() || $productType->getDescription() != $data) {
                        $actions[$heading] = ProductTypeChangeDescriptionAction::ofDescription($data);
                    }
                    break;
                case "attributes":
                    $actions = array_merge(
                        $actions,
                        $this->updateAttributes($productType->getAttributes(), $data)
                    );
            }
        }
        $request->setActions($actions);

        return $request;
    }

    protected function updateAttributes(
        AttributeDefinitionCollection $attributes,
        $attributeData
    ) {
        $actions = [];
        $attributeDataByName = [];


        foreach ($attributeData as $attribute) {
            $attributeDataByName[$attribute['name']] = $attribute;
        }
        $attributeByName = [];
        foreach ($attributes as $attribute) {
            $attributeByName[$attribute->getName()] = $attribute;
        }

        $toChange = array_intersect(array_keys($attributeDataByName), array_keys($attributeByName));

        $toAdd = array_diff_key($attributeDataByName, array_flip($toChange));
        $toRemove = array_diff_key($attributeByName, array_flip($toChange));

        foreach ($toAdd as $attribute) {
            $actions['addAttribute_' . $attribute['name']] = ProductTypeAddAttributeDefinitionAction::ofAttribute(
                AttributeDefinition::fromArray($attribute)
            );
        }
        foreach ($toRemove as $attribute) {
            $actions['removeAttribute_' . $attribute->getName()] = ProductTypeRemoveAttributeDefinitionAction::ofName(
                $attribute->getName()
            );
        }


        foreach ($toChange as $attributeName) {
            /**
             * @var AttributeDefinition $attribute
             */
            $attribute = $attributeByName[$attributeName];
            $attributeData = $attributeDataByName[$attributeName];
            if (isset($attributeData['label']) &&
                !$this->compareLocalizedString($attribute->getLabel()->toArray(), $attributeData['label'])
            ) {
                $actions['updateAttributeLabel_' . $attribute->getName()]= ProductTypeChangeLabelAction::ofAttributeNameAndLabel(
                    $attributeData['name'],
                    LocalizedString::fromArray($attributeData['label'])
                );
            }

            if (isset($attributeData['inputTip']) &&
                !$this->compareLocalizedString($attribute->getInputTip()->toArray(), $attributeData['inputTip'])
            ) {
                $actions['updateAttributeInputTip_' . $attribute->getName()]=
                    ProductTypeSetInputTipAction::ofAttributeName($attributeData['name'])->setInputTip(
                        LocalizedString::fromArray($attributeData['inputTip'])
                    );
            }

            if (isset($attributeData['isSearchable']) &&
                !$attribute->getIsSearchable() != $attributeData['isSearchable']
            ) {
                $actions['updateAttributeInputTip_' . $attribute->getName()]=
                    ProductTypeChangeIsSearchableAction::ofAttributeNameAndIsSearchable(
                        $attributeData['name'],
                        $attributeData['isSearchable']
                    );
            }

            if (isset($attributeData['type'])) {
                $attributeType = $attribute->getType();

                if ($attributeType instanceof EnumType ||
                    $attributeType instanceof SetType && $attributeType->getElementType() instanceof EnumType
                ) {
                    $attributeEnumValues=$attributeType->getValues();

                    $enumDataByName = [];

                    foreach ($attributeData['type']['values'] as $enum) {
                        $enumDataByName[$enum['key']] = $enum;
                    }
                    $enumByName = [];
                    foreach ($attributeEnumValues as $enum) {
                        $enumByName[$enum->getKey()] = $enum;
                    }

                    $toChange = array_intersect(array_keys($enumDataByName), array_keys($enumByName));
                    $toAdd = array_diff_key($enumDataByName, array_flip($toChange));

                    foreach ($toAdd as $item) {
                        $actions['addPlainEnumValue_' . $item['key']]=
                            ProductTypeAddPlainEnumValueAction::ofAttributeNameAndValue(
                                $attributeData['name'],
                                Enum::fromArray($item)
                            );
                    }

                    foreach ($toChange as $item) {
                        if (strcmp($enumDataByName[$item]['label'], $enumByName[$item]->getLabel())!= 0) {
                            $actions['changePlainEnumLabel_' . $item]=
                                ProductTypeChangePlainEnumLabelAction::ofAttributeNameAndEnumValue(
                                    $attributeData['name'],
                                    Enum::fromArray($enumDataByName[$item])
                                );
                        }
                    }
                }
                if ($attributeType instanceof LocalizedEnumType ||
                    $attributeType instanceof SetType && $attributeType->getElementType() instanceof LocalizedEnumType
                ) {
                    $attributeEnumValues=$attributeType->getValues();

                    $enumDataByName = [];

                    foreach ($attributeData['type']['values'] as $enum) {
                        $enumDataByName[$enum['key']] = $enum;
                    }
                    $enumByName = [];
                    foreach ($attributeEnumValues as $enum) {
                        $enumByName[$enum->getKey()] = $enum;
                    }

                    $toChange = array_intersect(array_keys($enumDataByName), array_keys($enumByName));
                    $toAdd = array_diff_key($enumDataByName, array_flip($toChange));

                    foreach ($toAdd as $item) {
                        $item['label']=LocalizedString::fromArray($item['label']);
                        $actions['addLocalizedEnumValue_' . $item['key']]=
                            ProductTypeAddLocalizedEnumValueAction::ofAttributeNameAndValue(
                                $attributeData['name'],
                                LocalizedEnum::fromArray($item)
                            );
                    }

                    foreach ($toChange as $item) {
                        if (!$this->compareLocalizedString($enumDataByName[$item]['label'], $enumByName[$item]->getLabel()->toArray())) {
                            $enumDataByName[$item]['label'] = LocalizedString::fromArray($enumDataByName[$item]['label']);
                            $actions['changeLocalizedEnumLabel_' . $item]=
                                ProductTypeChangeLocalizedEnumLabelAction::ofAttributeNameAndEnumValue(
                                    $attributeData['name'],
                                    LocalizedEnum::fromArray($enumDataByName[$item])
                                );
                        }
                    }
                }
            }
        }
        return $actions;
    }

    private function compareLocalizedString($a, $b)
    {
        if (count($a) != count($b)) {
            return false;
        }
        foreach ($a as $locale => $str) {
            if (!isset($b[$locale]) || $b[$locale] !== $str) {
                return false;
            }
        }

        return true;
    }
}
