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
//
//
//  "order2pay" =>  [
//        "id" => "f15be1f5-07e7-4748-8c61-23cb83f1e49d"
//    "version" => 1
//    "createdAt" => "2018-11-23T15:04:28.586Z"
//    "lastModifiedAt" => "2018-11-23T15:04:28.586Z"
//    "key" => "order2pay"
//    "name" =>  [
//        "en" => "order2pay"
//    ]
//    "description" =>  [
//        "en" => "order2pay"
//    ]
//    "resourceTypeIds" =>  [
//        0 => "payment"
//    ]
//    "fieldDefinitions" =>  [
//        "orderReference" =>  [
//        "name" => "orderReference"
//        "label" =>  [
//        "en" => "order-id"
//    ]
//        "required" => true
//        "type" =>  [
//        "name" => "String"
//    ]
//        "inputHint" => "SingleLine"
//      ]
//    ]
//  ]
//  "vita" =>  [
//        "id" => "8e3e08d2-0d86-42ef-866e-2e0d372156b1"
//    "version" => 2
//    "createdAt" => "2018-11-23T15:30:22.370Z"
//    "lastModifiedAt" => "2019-01-14T10:28:24.068Z"
//    "key" => "vita"
//    "name" =>  [
//        "en" => "vita"
//    ]
//    "description" =>  [
//        "en" => "vita"
//    ]
//    "resourceTypeIds" =>  [
//        0 => "line-item"
//      1 => "channel"
//      2 => "discount-code"
//      3 => "product-price"
//    ]
//    "fieldDefinitions" => []
//  ]
//  "anotherType" =>  [
//        "id" => "169ff392-3b8f-4a15-97b7-31bc648d8716"
//    "version" => 1
//    "createdAt" => "2018-11-28T10:47:57.194Z"
//    "lastModifiedAt" => "2018-11-28T10:47:57.194Z"
//    "key" => "anotherType"
//    "name" =>  [
//        "en" => "anotherType"
//    ]
//    "description" =>  [
//        "en" => "anotherType"
//    ]
//    "resourceTypeIds" =>  [
//        0 => "shopping-list"
//    ]
//    "fieldDefinitions" =>  [
//        "whomsdt" =>  [
//        "name" => "whomsdt"
//        "label" =>  [
//        "en" => "whomsdt"
//    ]
//        "required" => true
//        "type" =>  [
//        "name" => "String"
//    ]
//        "inputHint" => "SingleLine"
//      ]
//    ]
//  ]
//
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
            'update' => []
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
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetChangesWithDeletedField()
    {
        $this->markTestIncomplete();
        $processor = ProcessCustomTypes::of();

        $typeCollectionForLocal = TypeCollection::fromArray([
            'key-1' => [
                'foo' => 'bar',
                'fieldDefinitions' => []
            ],
            'key-2' => [
                'baz' => 'foobar',
            ]
        ]);

        $typeCollectionForServer = TypeCollection::fromArray([
            'key-1' => [
                'foo' => 'bar',
                'version' => 2,
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
            ]
        ]);

        $result = $processor->getChangesForServerSync($typeCollectionForLocal, $typeCollectionForServer);

        $expected = [
            'create' => [],
            'delete' => [],
            'update' => [
                'key-1' => [
                    'version' => 2,
                    'fieldDefinitions' => [
                        'create' => [],
                        'update' => [],
                        'delete' => ['field2']
                    ]
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
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

}
