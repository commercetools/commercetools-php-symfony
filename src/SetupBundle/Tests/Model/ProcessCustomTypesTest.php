<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Tests\Model;


use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\Type\FieldDefinition;
use Commercetools\Core\Model\Type\TypeCollection;
use Commercetools\Core\Model\Type\TypeDraft;
use Commercetools\Core\Request\Types\Command\TypeAddFieldDefinitionAction;
use Commercetools\Core\Request\Types\Command\TypeChangeLabelAction;
use Commercetools\Core\Request\Types\Command\TypeChangeNameAction;
use Commercetools\Core\Request\Types\Command\TypeRemoveFieldDefinitionAction;
use Commercetools\Core\Request\Types\TypeCreateRequest;
use Commercetools\Core\Request\Types\TypeDeleteByKeyRequest;
use Commercetools\Core\Request\Types\TypeUpdateByKeyRequest;
use Commercetools\Symfony\SetupBundle\Model\ProcessCustomTypes;
use PHPUnit\Framework\TestCase;

class ProcessCustomTypesTest extends TestCase
{
    public function getCollection1()
    {
        return TypeCollection::fromArray([
            "payment2order" => [
                "id" => "8d383d2a-cc86-4a18-ad5c-31d6281a6ca5",
                "version" => 3,
                "createdAt" => "2018-11-23T14:26:50.629Z",
                "lastModifiedAt" => "2019-01-14T10:28:51.560Z",
                "key" => "payment2order",
                "name" => [
                    "en" => "order2payment"
                ],
                "description" => [
                    "en" => "payment2order"
                ],
                "resourceTypeIds" => [
                    0 => "payment"
                ],
                "fieldDefinitions" => [
                    "OrderReference" => [
                        "name" => "OrderReference",
                        "label" => [
                            "en" => "order-id-custom"
                        ],
                        "required" => true,
                        "type" => [
                            "name" => "String"
                        ],
                        "inputHint" => "SingleLine"
                    ]
                ],
                'random-field' => 'foo'
            ],
            "asfgad" => [
                "id" => "bba3324a-f7c8-45dd-81ad-b4865d2db924",
                "version" => 1,
                "createdAt" => "2018-11-23T15:00:02.365Z",
                "lastModifiedAt" => "2018-11-23T15:00:02.365Z",
                "key" => "asfgad",
                "name" => [
                    "en" => "asdga"
                ],
                "description" => [
                    "en" => "asdga"
                ],
                "resourceTypeIds" => [
                    0 => "payment"
                ],
                "fieldDefinitions" => [
                    "OrderReference" => [
                        "name" => "OrderReference",
                        "label" => [
                            "en" => "order-id"
                        ],
                        "required" => true,
                        "type" => [
                            "name" => "String"
                        ],
                        "inputHint" => "SingleLine",
                        'field-to-be-skipped' => 'bar'
                    ]
                ]
            ]
        ]);
    }

    public function getCollection2()
    {
        return  [
            "payment2order" =>  [
                "id" => "8d383d2a-cc86-4a18-ad5c-31d6281a6ca5",
                "version" => 3,
                "createdAt" => "2018-11-23T14:26:50.629Z",
                "lastModifiedAt" => "2019-01-14T10:28:51.560Z",
                "key" => "payment2order",
                "name" =>  [
                    "en" => "order2payment"
                ],
                "description" =>  [
                    "en" => "payment2order2payment"
                ],
                "resourceTypeIds" =>  [
                    0 => "payment"
                ],
                "fieldDefinitions" =>  [
                    "OrderReference" =>  [
                        "name" => "OrderReference",
                        "label" =>  [
                            "en" => "order-id-custom"
                        ],
                        "required" => true,
                        "type" =>  [
                            "name" => "String"
                        ],
                        "inputHint" => "SingleLine"
                    ]
                ]
            ],
            "asfgad" =>  [
                "id" => "bba3324a-f7c8-45dd-81ad-b4865d2db924",
                "version" => 1,
                "createdAt" => "2018-11-23T15:00:02.365Z",
                "lastModifiedAt" => "2018-11-23T15:00:02.365Z",
                "key" => "asfgad",
                "name" =>  [
                    "en" => "asdga"
                ],
                "description" =>  [
                    "en" => "asdga"
                ],
                "resourceTypeIds" =>  [
                    0 => "payment"
                ],
                "fieldDefinitions" =>  [
                    "OrderReference" =>  [
                        "name" => "OrderReference",
                        "label" =>  [
                            "en" => "order-id-custom"
                        ],
                        "required" => true,
                        "type" =>  [
                            "name" => "String"
                        ],
                        "inputHint" => "SingleLine"
                    ]
                ]
            ]
        ];
    }


    public function testGetChangesFromLocal()
    {
        $processor = ProcessCustomTypes::of();

        $typeCollectionForLocal = TypeCollection::fromArray([
            'key-1' => [
                'foo' => 'bar',
            ],
            'key-2' => [
                'baz' => 'foobar',
            ]
        ]);

        $typeCollectionForServer = TypeCollection::fromArray([
            'key-1' => [
                'foo' => 'bazbaz',
                'version' => 1
            ],
            'key-2' => [
                'baz' => 'foobar',
                'version' => 1
            ]
        ]);

        $result = $processor->getChangesForServerSync($typeCollectionForLocal, $typeCollectionForServer);

        $expected = [
            'create' => [],
            'delete' => [],
            'update' => [
                'key-1' => [
                    'foo' => 'bar',
                    'version' => 1
                ],
                'key-2' => [
                    'version' => 1
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetChangesCreate()
    {
        $processor = ProcessCustomTypes::of();

        $typeCollectionForLocal = TypeCollection::fromArray([
            'key-1' => [
                'foo' => 'bar',
            ],
            'key-2' => [
                'baz' => 'foobar',
            ]
        ]);

        $typeCollectionForServer = TypeCollection::fromArray([
            'key-1' => [
                'foo' => 'bar',
                'version' => 1
            ]
        ]);

        $result = $processor->getChangesForServerSync($typeCollectionForLocal, $typeCollectionForServer);

        $expected = [
            'create' => [
                'key-2' => [
                    'baz' => 'foobar',
                ]
            ],
            'delete' => [],
            'update' => [
                'key-1' => [
                    'version' => 1
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetChangesDelete()
    {
        $processor = ProcessCustomTypes::of();

        $typeCollectionForLocal = TypeCollection::fromArray([
            'key-2' => [
                'baz' => 'foobar',
            ]
        ]);

        $typeCollectionForServer = TypeCollection::fromArray([
            'key-1' => [
                'foo' => 'bar',
                'version' => 1
            ],
            'key-2' => [
                'baz' => 'foobar',
            ]
        ]);

        $result = $processor->getChangesForServerSync($typeCollectionForLocal, $typeCollectionForServer);

        $expected = [
            'create' => [],
            'delete' => [
                'key-1' => [
                    'foo' => 'bar',
                    'version' => 1
                ]
            ],
            'update' => []
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetChangesWithNewField()
    {
        $processor = ProcessCustomTypes::of();

        $typeCollectionForLocal = TypeCollection::fromArray([
            'key-1' => [
                'foo' => 'bar',
                "fieldDefinitions" =>  [
                    "field2" =>  [
                        "name" => "field2",
                        "label" =>  [
                            "en" => "field244354453"
                        ],
                        "required" => true,
                        "type" =>  [
                            "name" => "String"
                        ],
                        "inputHint" => "SingleLine"
                    ]
                ]
            ],
            'key-2' => [
                'baz' => 'foobar',
            ]
        ]);

        $typeCollectionForServer = TypeCollection::fromArray([
            'key-1' => [
                'foo' => 'bar',
                'version' => 2
            ],
            'key-2' => [
                'baz' => 'foobar',
                'version' => 1
            ]
        ]);

        $result = $processor->getChangesForServerSync($typeCollectionForLocal, $typeCollectionForServer);
        $result = $processor->convertFieldDefinitionsToObject($result);

        $expected = [
            'create' => [],
            'delete' => [],
            'update' => [
                'key-1' => [
                    'version' => 2,
                    'fieldDefinitions' => [
                        'create' => [
                            "field2" =>  FieldDefinition::fromArray([
                                "name" => "field2",
                                "label" =>  [
                                    "en" => "field244354453"
                                ],
                                "required" => true,
                                "type" =>  [
                                    "name" => "String"
                                ],
                                "inputHint" => "SingleLine"
                            ])
                        ],
                        'update' => [],
                        'delete' => []
                    ]
                ],
                'key-2' => [
                    'version' => 1
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetChangesWithMixedFields()
    {
        $processor = ProcessCustomTypes::of();

        $typeCollectionForLocal = TypeCollection::fromArray([
            'key-1' => [
                'foo' => 'bar',
                'name' => [
                    'en' => 'new-name'
                ]
            ],
            'key-2' => [
                'baz' => 'foobar',
            ],
            'key-4' => [
                'barbar' => 'foofoo',
            ]
        ]);

        $typeCollectionForServer = TypeCollection::fromArray([
            'key-1' => [
                'foo' => 'bar',
                'version' => 2,
                'name' => [
                    'en' => 'old-name'
                ],
                "fieldDefinitions" =>  [
                    "field2" =>  [
                        "name" => "field2",
                        "label" =>  [
                            "en" => "field244354453"
                        ],
                        "required" => true,
                        "type" =>  [
                            "name" => "String"
                        ],
                        "inputHint" => "SingleLine"
                    ]
                ]
            ],
            'key-2' => [
                'baz' => 'foobar',
                'version' => 1
            ],
            'key-3' => [
                'bar' => 'barfoo',
                'version' => 3
            ]
        ]);

        $result = $processor->getChangesForServerSync($typeCollectionForLocal, $typeCollectionForServer);

//        $processor->createChangesArray($typeCollectionForLocal, $typeCollectionForServer);

        $expected = [
            'create' => [
                'key-4' => [
                    'barbar' => 'foofoo',
                ]
            ],
            'delete' => [
                'key-3' => [
                    'bar' => 'barfoo',
                    'version' => 3
                ]
            ],
            'update' => [
                'key-1' => [
                    'version' => 2,
                    'fieldDefinitions' => [
                        'create' => [],
                        'update' => [],
                        'delete' => ['field2']
                    ],
                    'name' => [
                        'en' => 'new-name'
                    ]
                ],
                'key-2' => [
                    'version' => 1
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }


    public function testGetChangesWithUpdatedField()
    {
        $processor = ProcessCustomTypes::of();

        $typeCollectionForLocal = TypeCollection::fromArray([
            'key-1' => [
                'foo' => 'bar',
                "fieldDefinitions" =>  [
                    "field2" =>  [
                        "name" => "field2",
                        "label" =>  [
                            "en" => "field244354453"
                        ],
                        "required" => true,
                        "type" =>  [
                            "name" => "String"
                        ],
                        "inputHint" => "SingleLine"
                    ]
                ]
            ],
            'key-2' => [
                'baz' => 'foobar',
                'key' => 'wrong-key'
            ]
        ]);

        $typeCollectionForServer = TypeCollection::fromArray([
            'key-1' => [
                'foo' => 'bar',
                'version' => 2
            ],
            'key-2' => [
                'baz' => 'foobar',
                'key' => 'key-2',
                'version' => 1
            ]
        ]);

        $result = $processor->getChangesForServerSync($typeCollectionForLocal, $typeCollectionForServer);
        $result = $processor->convertFieldDefinitionsToObject($result);

        $expected = [
            'create' => [],
            'delete' => [],
            'update' => [
                'key-1' => [
                    'version' => 2,
                    'fieldDefinitions' => [
                        'create' => [
                            "field2" =>  FieldDefinition::fromArray([
                                "name" => "field2",
                                "label" =>  [
                                    "en" => "field244354453"
                                ],
                                "required" => true,
                                "type" =>  [
                                    "name" => "String"
                                ],
                                "inputHint" => "SingleLine"
                            ])
                        ],
                        'update' => [],
                        'delete' => []
                    ]
                ],
                'key-2' => [
                    'version' => 1,
                    'key' => 'wrong-key'
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }


    public function testGetChangesWithNewFieldAndEmptyFieldDefinitionsArray()
    {
        $processor = ProcessCustomTypes::of();

        $typeCollectionForLocal = TypeCollection::fromArray([
            'key-1' => [
                'foo' => 'bar',
                "fieldDefinitions" =>  [
                    "field2" =>  [
                        "name" => "field2",
                        "label" =>  [
                            "en" => "field244354453"
                        ],
                        "required" => true,
                        "type" =>  [
                            "name" => "String"
                        ],
                        "inputHint" => "SingleLine"
                    ]
                ]
            ],
            'key-2' => [
                'baz' => 'foobar',
            ]
        ]);

        $typeCollectionForServer = TypeCollection::fromArray([
            'key-1' => [
                'foo' => 'bar',
                'version' => 2,
                'fieldDefinitions' => []
            ],
            'key-2' => [
                'baz' => 'foobar',
                'version' => 1
            ]
        ]);

        $result = $processor->getChangesForServerSync($typeCollectionForLocal, $typeCollectionForServer);
        $result = $processor->convertFieldDefinitionsToObject($result);

        $expected = [
            'create' => [],
            'delete' => [],
            'update' => [
                'key-1' => [
                    'version' => 2,
                    'fieldDefinitions' => [
                        'create' => [
                            "field2" =>  FieldDefinition::fromArray([
                                "name" => "field2",
                                "label" =>  [
                                    "en" => "field244354453"
                                ],
                                "required" => true,
                                "type" =>  [
                                    "name" => "String"
                                ],
                                "inputHint" => "SingleLine"
                            ])
                        ],
                        'update' => [],
                        'delete' => []
                    ]
                ],
                'key-2' => [
                    'version' => 1
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testMapChangesToRequests()
    {
        $fieldDefinition = [
            "name" => "field2",
            "label" =>  [
                "en" => "field244354453"
            ],
            "required" => true,
            "type" =>  [
                "name" => "String"
            ],
            "inputHint" => "SingleLine"
        ];

        $changes = [
            'create' => [],
            'delete' => [],
            'update' => [
                'key-1' => [
                    'version' => 2,
                    'fieldDefinitions' => [
                        'create' => [
                            "field2" =>  FieldDefinition::fromArray($fieldDefinition)
                        ],
                        'update' => [],
                        'delete' => []
                    ]
                ],
                'key-2' => [
                    'version' => 1
                ]
            ]
        ];

        $processor = ProcessCustomTypes::of();
        $requests = $processor->mapChangesToRequests($changes);

        $expected = [
            TypeUpdateByKeyRequest::ofKeyAndVersion('key-1', 2)
                ->setActions([
                    TypeAddFieldDefinitionAction::of()->setFieldDefinition(FieldDefinition::fromArray($fieldDefinition))
                ])
        ];

        $this->assertEquals($expected, $requests);
    }

    public function testMapChangesWithMixedActions()
    {
        $changes = [
            'create' => [
                'key-4' => [
                    'barbar' => 'foofoo',
                ]
            ],
            'delete' => [
                'key-3' => [
                    'bar' => 'barfoo',
                    'version' => 3
                ]
            ],
            'update' => [
                'key-1' => [
                    'version' => 2,
                    'fieldDefinitions' => [
                        'create' => [],
                        'update' => [],
                        'delete' => [
                            'name' => 'field2'
                        ]
                    ],
                    'name' => [
                        'en' => 'new-name'
                    ]
                ],
                'key-2' => [
                    'version' => 1
                ]
            ]
        ];

        $processor = ProcessCustomTypes::of();
        $requests = $processor->mapChangesToRequests($changes);

        $expected = [
            TypeDeleteByKeyRequest::ofKeyAndVersion('key-3', 3),
            TypeCreateRequest::ofDraft(TypeDraft::fromArray(['barbar' => 'foofoo'])),
            TypeUpdateByKeyRequest::ofKeyAndVersion('key-1', 2)
                ->setActions([
                    TypeRemoveFieldDefinitionAction::of()->setFieldName('field2'),
                    TypeChangeNameAction::ofName(LocalizedString::ofLangAndText('en', 'new-name'))
                ]),
        ];

        $this->assertEquals($expected, $requests);
    }

    public function testMapChangesWithFieldUpdate()
    {
        $fieldDefinition = [
            "name" => "field2",
            "label" =>  [
                "en" => "field244354453"
            ],
            "required" => true,
            "type" =>  [
                "name" => "String"
            ],
            "inputHint" => "SingleLine"
        ];

        $changes = [
            'create' => [],
            'delete' => [],
            'update' => [
                'key-1' => [
                    'version' => 2,
                    'fieldDefinitions' => [
                        'create' => [
                            "field2" =>  FieldDefinition::fromArray($fieldDefinition)
                        ],
                        'update' => [],
                        'delete' => []
                    ]
                ],
                'key-2' => [
                    'version' => 1
                ]
            ]
        ];

        $processor = ProcessCustomTypes::of();
        $requests = $processor->mapChangesToRequests($changes);

        $expected = [
            TypeUpdateByKeyRequest::ofKeyAndVersion('key-1', 2)
                ->setActions([
                    TypeAddFieldDefinitionAction::of()->setFieldDefinition(FieldDefinition::fromArray($fieldDefinition))
                ]),
        ];

        $this->assertEquals($expected, $requests);

    }

    public function testMapChangesWithFieldDefinitionsUpdate()
    {
        $changes = [
            'create' => [],
            'delete' => [],
            'update' => [
                'key-1' => [
                    'version' => 2,
                    'fieldDefinitions' => [
                        'create' => [],
                        'update' => [
                            'field-1' => [
                                'label' => [
                                    'en' => 'new-label'
                                ]
                            ]
                        ],
                        'delete' => []
                    ]
                ],
                'key-2' => [
                    'version' => 1
                ]
            ]
        ];

        $processor = ProcessCustomTypes::of();
        $requests = $processor->mapChangesToRequests($changes);

        $expected = [
            TypeUpdateByKeyRequest::ofKeyAndVersion('key-1', 2)
                ->setActions([
                    TypeChangeLabelAction::ofNameAndLabel('field-1', LocalizedString::ofLangAndText('en', 'new-label'))
                ])
        ];

        $this->assertEquals($expected, $requests);
    }

    public function testMapChangesWithFieldDefinitionsCreate()
    {
        $changes = [
            'create' => [
                'key-1' => [
                    'key' => 'key-1',
                    'version' => 2,
                    'fieldDefinitions' => [
                        'field-1' => [
                            'name' => 'field-1',
                            'label' => [
                                'en' => 'new-label'
                            ]
                        ]
                    ]
                ]
            ],
            'delete' => [],
            'update' => [
                'key-2' => [
                    'version' => 1
                ]
            ]
        ];

        $processor = ProcessCustomTypes::of();
        $requests = $processor->mapChangesToRequests($changes);

        $expected = [
            TypeCreateRequest::ofDraft(TypeDraft::fromArray([
                'key' => 'key-1',
                'version' => 2,
                'fieldDefinitions' => [
                   [
                       'name' => 'field-1',
                       'label' => [
                           'en' => 'new-label'
                       ]
                   ]
                ]
            ]))
        ];

        $this->assertEquals($expected, $requests);
    }

    public function testParseTypes()
    {
        $processor = ProcessCustomTypes::of();
        $result = $processor->parseTypes($this->getCollection1());

        $expected = [
            'payment2order' => [
                'id' => '8d383d2a-cc86-4a18-ad5c-31d6281a6ca5',
                'version' => 3,
                'key' => 'payment2order',
                'name' => ['en' => 'order2payment'],
                'description' => ['en' => 'payment2order'],
                'resourceTypeIds' => ['payment'],
                'fieldDefinitions' => [
                    'OrderReference' => [
                        'name' => 'OrderReference',
                        'label' => ['en' => 'order-id-custom'],
                        'required' => true,
                        'type' => ['name' => 'String'],
                        'inputHint' => 'SingleLine'
                    ]
                ]
            ],
            'asfgad' => [
                'id' => 'bba3324a-f7c8-45dd-81ad-b4865d2db924',
                'version' => 1,
                'key' => 'asfgad',
                'name' => ['en' => 'asdga'],
                'description' => ['en' => 'asdga'],
                'resourceTypeIds' => ['payment'],
                'fieldDefinitions' => [
                    'OrderReference' => [
                        'name' => 'OrderReference',
                        'label' => ['en' => 'order-id'],
                        'required' => true,
                        'type' => ['name' => 'String'],
                        'inputHint' => 'SingleLine'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetConfigArray()
    {
        $typeCollection = TypeCollection::fromArray([
            'key-1' => [
                'id' => '1',
                'key' => 'key-1',
                'name' => ['en' => 'type-name']
            ]
        ]);

        $processor = ProcessCustomTypes::of();
        $config = $processor->getConfigArray($typeCollection);

        $expected = [
            'setup' => [
                'custom_types' => [
                    'key-1' => [
                        'id' => '1',
                        'key' => 'key-1',
                        'name' => ['en' => 'type-name']
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $config);
    }

}
