<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Model;


use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\Type\FieldDefinition;
use Commercetools\Core\Model\Type\TypeCollection;
use Commercetools\Core\Model\Type\TypeDraft;
use Commercetools\Core\Request\Types\Command\TypeAddFieldDefinitionAction;
use Commercetools\Core\Request\Types\Command\TypeChangeKeyAction;
use Commercetools\Core\Request\Types\Command\TypeChangeLabelAction;
use Commercetools\Core\Request\Types\Command\TypeChangeNameAction;
use Commercetools\Core\Request\Types\Command\TypeRemoveFieldDefinitionAction;
use Commercetools\Core\Request\Types\Command\TypeSetDescriptionAction;
use Commercetools\Core\Request\Types\TypeCreateRequest;
use Commercetools\Core\Request\Types\TypeDeleteByKeyRequest;
use Commercetools\Core\Request\Types\TypeUpdateByKeyRequest;

class ProcessCustomTypes
{
    const TYPE_ACTIONS = [
        'key' => [TypeChangeKeyAction::class, 'setKey'],
        'name' => [TypeChangeNameAction::class, 'setName'],
        'description' => [TypeSetDescriptionAction::class, 'setDescription'],
        'fieldDefinitions' => [
            'create' => [TypeAddFieldDefinitionAction::class, 'setFieldDefinition'],
            'delete' => [TypeRemoveFieldDefinitionAction::class, 'setFieldName'],
            'update' => [
                'label' => [TypeChangeLabelAction::class, 'changeLabelAction']
            ]
        ]
    ];

    const LOCALIZED_TYPES = ['name', 'description'];

    const VALID_TYPE_FIELDS = [
        'key', 'name', 'description', 'fieldDefinitions', 'label', 'version'
    ];

    public function getConfigArray(TypeCollection $typeCollection)
    {
        return [
            'setup' => [
                'custom_types' => $this->parseTypes($typeCollection)
            ]
        ];
    }

    public function parseTypes($typeCollection)
    {
        $types = [];

        foreach ($typeCollection as $type) {
            if (!is_null($type->key)) {
                $types[$type->key] = $type->toArray();

                if (!is_null($type->fieldDefinitions)) {
                    $fd = [];
                    foreach ($type->fieldDefinitions as $fieldDefinition) {
                        $fd[$fieldDefinition->name] = $fieldDefinition->toArray();
                    }
                    $types[$type->key]['fieldDefinitions'] = $fd;
                }
            }
        }

        return $types;
    }


    public function getChangesForServerSync(TypeCollection $localTypes, TypeCollection $serverTypes)
    {
        $serverTypes = $serverTypes->toArray();
        $localTypes = $localTypes->toArray();

        $changedTypes = $this->arrayDiffFirstLevel($localTypes, $serverTypes);

        return ($changedTypes);
    }

    public function mapChangesToRequests(array $changedTypes)
    {
        $requests = [];

        foreach ($changedTypes['delete'] as $changedTypeKey => $changedTypeValue) {
            $requests[] = TypeDeleteByKeyRequest::ofKeyAndVersion($changedTypeKey, $changedTypeValue['version']);
        }

        foreach ($changedTypes['create'] as $changedTypeKey => $changedTypeValue) {
            if (array_key_exists('fieldDefinitions', $changedTypeValue)) {
                $changedTypeValue['fieldDefinitions'] = array_values($changedTypeValue['fieldDefinitions']);
            }

            $requests[] = TypeCreateRequest::ofDraft(TypeDraft::fromArray($changedTypeValue));
        }

        foreach ($changedTypes['update'] as $changedTypeKey => $changedTypeFields) {
            foreach ($changedTypeFields as $changedTypeFieldKey => $changedTypeFieldValue) {

                $allActions = [];
                if ($changedTypeFieldKey === 'fieldDefinitions') {
                    foreach ($changedTypeFieldValue as $changedTypeAction => $changedTypeActionFields) {
                        if (!empty($changedTypeActionFields)) {
                            foreach ($changedTypeActionFields as $changedTypeFieldDefinitionKey => $changedTypeFieldDefinitionValue) {
                                if ($changedTypeAction === 'update') {
                                    foreach ($changedTypeFieldDefinitionValue as $changedItemKey => $changedItemValue) {
                                        if (isset(self::TYPE_ACTIONS[$changedTypeFieldKey][$changedTypeAction][$changedItemKey])) {
                                            $action = call_user_func([
                                                $this, self::TYPE_ACTIONS[$changedTypeFieldKey][$changedTypeAction][$changedItemKey][1]
                                            ], $changedTypeFieldDefinitionKey, $changedItemValue);

                                            $allActions[] = $action;
                                        }
                                    }
                                } else {
                                    $class = self::TYPE_ACTIONS[$changedTypeFieldKey][$changedTypeAction][0];
                                    $actionName = self::TYPE_ACTIONS[$changedTypeFieldKey][$changedTypeAction][1];
                                    $allActions[] = $class::of()->$actionName($changedTypeFieldDefinitionValue);
                                }
                            }
                        }
                    }
                } else if (isset(self::TYPE_ACTIONS[$changedTypeFieldKey])) {
                    $class = self::TYPE_ACTIONS[$changedTypeFieldKey][0];
                    $actionName = self::TYPE_ACTIONS[$changedTypeFieldKey][1];

                    if (in_array($changedTypeFieldKey, self::LOCALIZED_TYPES)) {
                        $allActions[] = $class::of()->$actionName(LocalizedString::fromArray($changedTypeFieldValue));
                    } else {
                        $allActions[] = $class::of()->$actionName($changedTypeFieldValue);
                    }
                }

                if (!empty($allActions)) {
                    $version = is_array($changedTypeFields['version']) ? current($changedTypeFields['version']) : $changedTypeFields['version'];
                    $requests[] = TypeUpdateByKeyRequest::ofKeyAndVersion($changedTypeKey, $version)
                        ->setActions($allActions);
                }

            }
        }

        return $requests;
    }

    private function changeLabelAction($fieldName, $label)
    {
        return TypeChangeLabelAction::of()->setFieldName($fieldName)->setLabel(LocalizedString::fromArray($label));
    }

    private function removeCommon(array $from, array $toRemove)
    {
        foreach ($toRemove as $key => $value) {
            unset($from[$key]);
        }
        return $from;
    }

    public function arrayDiffFirstLevel(array $arr1, array $arr2)
    {
        $outputDiff = [
            'create' => [],
            'delete' => [],
            'update' => []
        ];

        foreach ($arr1 as $key => $value) {
            if (isset($arr2[$key]) || array_key_exists($key, $arr2)) {
                if (is_array($value) && is_array($arr2[$key])) {
                    $arr2Value = $arr2[$key];
                    $internalDiff = $this->arrayDiffRecursiveInternal($value, $arr2Value);


                    if (!empty($internalDiff)) {
                        $outputDiff['update'][$key] = $internalDiff;
                    }
                } elseif ($value != $arr2[$key]) {
                    $outputDiff['update'][$key] = $value;
                }
            } else {
                $outputDiff['create'][$key] = $value;
            }
        }

        foreach ($arr2 as $key => $value) {
            if (!isset($arr1[$key]) && !array_key_exists($key, $arr1)) {
                $outputDiff['delete'][$key] = $value;
            } else {
                if (is_array($value) && is_array($arr1[$key])) {
                    $arr1Value = $arr1[$key];
                    $internalDiff = $this->arrayDiffRecursiveInternalReversed($value, $arr1Value);

                    if (!empty($internalDiff)) {
                        $outputDiff['update'][$key] = $outputDiff['update'][$key] ?? [];
                        $outputDiff['update'][$key] = array_merge($outputDiff['update'][$key], $internalDiff);
                    }
                }
            }
        }

        return $outputDiff;
    }

    public function arrayDiffRecursiveInternal(array $arr1, array $arr2)
    {
        $outputDiff = [];

        foreach ($arr1 as $key => $value) {
            if ($key === 'fieldDefinitions') {
                $arr2Value = isset($arr2[$key]) ? $arr2[$key] : [];
                $recursiveDiff = $this->arrayDiffFirstLevel($value, $arr2Value);

                if (!empty($recursiveDiff)) {
                    $outputDiff[$key] = $recursiveDiff;
                }

            } else if (isset($arr2[$key]) || array_key_exists($key, $arr2)) {
                if (is_array($value) && is_array($arr2[$key])) {
                    $arr2Value = $arr2[$key];
                    $recursiveDiff = $this->arrayDiffRecursiveInternal($value, $arr2Value);

                    if (!empty($recursiveDiff)) {
                        dump($key);

                        $outputDiff[$key] = $recursiveDiff;
                    }
                } else if ($value != $arr2[$key]) {
                    dump($key);

                    $outputDiff[$key] = $value;
                }
            } else {

                $outputDiff[$key] = $value;
            }
        }

        return $outputDiff;
    }

    public function arrayDiffExternalReversed(array $arr1, array $arr2)
    {
        $outputDiff = [
            'create' => [],
            'delete' => [],
            'update' => []
        ];

        foreach ($arr1 as $key => $value) {
            if (!isset($arr2[$key]) && !array_key_exists($key, $arr2)) {
                $outputDiff['delete'][] = $key;
            }
        }

        return $outputDiff;
    }

    public function arrayDiffRecursiveInternalReversed(array $arr1, array $arr2)
    {
        $outputDiff = [];

        foreach ($arr1 as $key => $value) {

            if ($key === 'fieldDefinitions') {
                $arr2Value = isset($arr2[$key]) ? $arr2[$key] : [];
                $recursiveDiff = $this->arrayDiffExternalReversed($value, $arr2Value);

                if (!empty($recursiveDiff)) {
                    $outputDiff[$key] = $recursiveDiff;
                }

            } elseif (!isset($arr2[$key]) && !array_key_exists($key, $arr2)) {
                if (in_array($key, self::VALID_TYPE_FIELDS)) {
                    $outputDiff[$key] = $value;
                }
            } elseif (isset($arr2[$key]) || array_key_exists($key, $arr2)) {
                if (is_array($value) && is_array($arr2[$key])) {
                    $arr2Value = $arr2[$key];
                    $internalDiff = $this->arrayDiffRecursiveInternal($value, $arr2Value);

                    if (!empty($recursiveDiff)) {
                        $outputDiff[$key] = $internalDiff;
                    }
                }
            }
        }

        return $outputDiff;
    }

    public function convertFieldDefinitionsToObject(array $diffArray)
    {
        foreach ($diffArray as $actionKey => $items) {
            foreach ($items as $itemKey => $itemValues) {
                foreach ($itemValues as $itemValueKey => $itemValue) {
                    if ($itemValueKey === 'fieldDefinitions') {
                        foreach ($itemValue['create'] as $fdKey => $fdValue) {
                            $diffArray[$actionKey][$itemKey]['fieldDefinitions']['create'][$fdKey] = FieldDefinition::fromArray($fdValue);
                        }
                    }
                }
            }
        }

        return $diffArray;
    }


    public static function of()
    {
        return new static();
    }
}
