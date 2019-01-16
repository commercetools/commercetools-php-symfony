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
        $helper = new ArrayHelper();
        $serverTypes = $serverTypes->toArray();

        $localTypes = $localTypes->toArray();
        $changedTypes = $helper->arrayDiffRecursive($localTypes, $serverTypes);

        $onlyOnServerToDelete = array_diff_key($serverTypes, $localTypes);
        $onlyLocalToCreate = array_diff_key($localTypes, $serverTypes);

        foreach ($onlyLocalToCreate as $key => $changed) {
            unset($changedTypes[$key]);
        }

        foreach ($changedTypes as $changedTypeKey => $changedTypeValues) {

            $changedTypes[$changedTypeKey]['version'] = $serverTypes[$changedTypeKey]['version'];

            if (array_key_exists('fieldDefinitions', $changedTypeValues)) {
                $localFieldDefinitions = $changedTypeValues['fieldDefinitions'];

                $serverFieldDefinitions = $serverTypes[$changedTypeKey]['fieldDefinitions'] ?? [];

                if ((empty($localFieldDefinitions) || is_null($localFieldDefinitions))
                    && (!is_null($serverFieldDefinitions) && !empty($serverFieldDefinitions))) {
                    unset($changedTypes[$changedTypeKey]['fieldDefinitions']);
                    $changedTypes[$changedTypeKey]['fieldDefinitions']['delete'] = array_keys($serverFieldDefinitions);

                } else {
                    $diffFields = $helper->arrayDiffRecursive($localFieldDefinitions, $serverFieldDefinitions);

                    $onlyOnServerFieldsToDelete = array_diff_key($serverFieldDefinitions, $localFieldDefinitions);
                    $onlyLocalFieldsToCreate = array_diff_key($localFieldDefinitions, $serverFieldDefinitions);

                    foreach ($onlyLocalFieldsToCreate as $onlyLocalFieldKey => $onlyLocalFieldValue) {
                        unset($diffFields[$onlyLocalFieldKey]);
                        $onlyLocalFieldsToCreate[$onlyLocalFieldKey] = FieldDefinition::fromArray($localTypes[$changedTypeKey]['fieldDefinitions'][$onlyLocalFieldKey]);
                    }

                    unset($changedTypes[$changedTypeKey]['fieldDefinitions']);
                    $changedTypes[$changedTypeKey]['fieldDefinitions']['create'] = $onlyLocalFieldsToCreate;
                    $changedTypes[$changedTypeKey]['fieldDefinitions']['delete'] = array_keys($onlyOnServerFieldsToDelete);
                    $changedTypes[$changedTypeKey]['fieldDefinitions']['update'] = [];

                    foreach ($diffFields as $diffFieldKey => $diffFieldValue) {
                        $changedTypes[$changedTypeKey]['fieldDefinitions']['update'][$diffFieldKey] = $diffFieldValue;
                    }
                }
            }
        }

        return [
            'create' => $onlyLocalToCreate,
            'delete' => $onlyOnServerToDelete,
            'update' => $changedTypes
        ];
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

                                            $requests[] = TypeUpdateByKeyRequest::ofKeyAndVersion(
                                                $changedTypeKey, $changedTypeFields['version']
                                            )->addAction($action);
                                        }
                                    }

                                } else {
                                    $class = self::TYPE_ACTIONS[$changedTypeFieldKey][$changedTypeAction][0];
                                    $actionName = self::TYPE_ACTIONS[$changedTypeFieldKey][$changedTypeAction][1];
                                    $requests[] = TypeUpdateByKeyRequest::ofKeyAndVersion($changedTypeKey, $changedTypeFields['version'])->addAction(
                                        $class::of()->$actionName($changedTypeFieldDefinitionValue)
                                    );
                                }
                            }
                        }
                    }
                } else if (isset(self::TYPE_ACTIONS[$changedTypeFieldKey])) {
                    $class = self::TYPE_ACTIONS[$changedTypeFieldKey][0];
                    $actionName = self::TYPE_ACTIONS[$changedTypeFieldKey][1];

                    if (in_array($changedTypeFieldKey, self::LOCALIZED_TYPES)) {
                        $requests[] = TypeUpdateByKeyRequest::ofKeyAndVersion($changedTypeKey, $changedTypeFields['version'])->addAction(
                            $class::of()->$actionName(LocalizedString::fromArray($changedTypeFieldValue))
                        );
                    } else {
                        $requests[] = TypeUpdateByKeyRequest::ofKeyAndVersion($changedTypeKey, $changedTypeFields['version'])->addAction(
                            $class::of()->$actionName($changedTypeFieldValue)
                        );
                    }
                }
            }
        }

        return $requests;
    }

    private function changeLabelAction($fieldName, $label)
    {
        return TypeChangeLabelAction::of()->setFieldName($fieldName)->setLabel(LocalizedString::fromArray($label));
    }


    public static function of()
    {
        return new static();
    }
}
