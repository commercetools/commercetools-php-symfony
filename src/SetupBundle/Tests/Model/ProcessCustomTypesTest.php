<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Tests\Model;


use Commercetools\Core\Model\Type\FieldDefinition;
use Commercetools\Core\Model\Type\TypeCollection;
use Commercetools\Symfony\SetupBundle\Model\ProcessCustomTypes;
use PHPUnit\Framework\TestCase;

class ProcessCustomTypesTest extends TestCase
{
    public function getCollection1()
    {
        return [
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
                ]
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
                        "inputHint" => "SingleLine"
                    ]
                ]
            ]
        ];
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

}
