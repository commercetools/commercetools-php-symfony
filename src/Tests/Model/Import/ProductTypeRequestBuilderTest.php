<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 10/11/16
 * Time: 17:19
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Model\Import;

use Commercetools\Core\Client;
use Commercetools\Core\Model\ProductType\ProductType;
use Commercetools\Core\Request\Products\ProductCreateRequest;
use Commercetools\Core\Request\Products\ProductQueryRequest;
use Commercetools\Core\Request\ProductTypes\ProductTypeCreateRequest;
use Commercetools\Core\Request\ProductTypes\ProductTypeQueryRequest;
use Commercetools\Core\Request\ProductTypes\ProductTypeUpdateByKeyRequest;
use Commercetools\Core\Request\ProductTypes\ProductTypeUpdateRequest;
use Commercetools\Core\Response\PagedQueryResponse;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Commercetools\Symfony\CtpBundle\Model\Import\ProductTypeRequestBuilder;

class ProductTypeRequestBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateRequest()
    {
        $client = $this->prophesize(Client::class);

        $client->execute(Argument::type(ProductTypeQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{}'), $args[0]);

            return $response;
        });

        $requestBuilder = new ProductTypeRequestBuilder($client->reveal());

        $data = ['name' => 'product', 'key' => "productkey", 'description' => "product desc"];

        $returnedRequest= $requestBuilder->createRequest($data, "key");

        $this->assertInstanceOf(ProductTypeCreateRequest::class, $returnedRequest);
        $this->assertEquals(
            '{"name":"product","key":"productkey","description":"product desc"}',
            (string)$returnedRequest->httpRequest()->getBody()
        );
    }

    public function getTestData()
    {
        return [
            //general test
            [
                [
                    'id' => "12345",
                    'name' => "new product",
                    'key' => "productkey",
                    'description' => "new product desc"
                ],
                '{"results": [{"id" :"12345","name": "product", "key": "productkey", "description": "product desc"}]}',
                '{"version":null,"actions":[
                    {"action":"changeName","name":"new product"},
                    {"action":"changeDescription","description":"new product desc"}
                ]}'
            ],
            //change name test cases
            [
                [
                    'id' => "12345",
                    'name' => "new product",
                    'key' => "productkey",
                    'description' => "product desc"
                ],
                '{"results": [{"id" : "12345","name": "product", "key": "productkey", "description": "product desc"}]}',
                '{"version":null,"actions":[
                    {"action":"changeName","name":"new product"}
                ]}'
            ],
            [
                [
                    'id' => "12345",
                    'name' => "product",
                    'key' => "productkey",
                    'description' => "product desc"
                ],
                '{"results": [{"id" : "12345","name": "product", "key": "productkey", "description": "product desc"}]}',
                '{"version":null,"actions":[]}'
            ],
            [
                [
                    'id' => "12345",
                    'name' => "product",
                    'key' => "productkey",
                    'description' => "product desc"
                ],
                '{"results": [{"id" : "12345","key": "productkey", "description": "product desc"}]}',
                '{"version":null,"actions":[ {"action":"changeName","name":"product"}]}'
            ],
            //change description test cases
            [
                [
                    'id' => "12345",
                    'name' => "product",
                    'key' => "productkey",
                    'description' => "new product desc"
                ],
                '{"results": [{"id" :"12345","name": "product", "key": "productkey", "description": "product desc"}]}',
                '{"version":null,"actions":[
                    {"action":"changeDescription","description":"new product desc"}
                ]}'
            ],
            [
                [
                    'id' => "12345",
                    'name' => "product",
                    'key' => "productkey",
                    'description' => "product desc"
                ],
                '{"results": [{"id" : "12345","name": "product", "key": "productkey", "description": "product desc"}]}',
                '{"version":null,"actions":[]}'
            ],
            [
                [
                    'id' => "12345",
                    'name' => "product",
                    'key' => "productkey",
                    'description' => "product desc"
                ],
                '{"results": [{"id" : "12345","name": "product","key": "productkey"}]}',
                '{"version":null,"actions":[ {"action":"changeDescription","description":"product desc"}]}'
            ],
            // change Key test cases
            [
                [
                    'id' => "12345",
                    'name' => "product",
                    'key' => "newproductkey",
                    'description' => "product desc"
                ],
                '{"results": [{"id" :"12345","name": "product", "key": "productkey", "description": "product desc"}]}',
                '{"version":null,"actions":[
                    {"action":"setKey","key":"newproductkey"}
                ]}'
            ],
            [
                [
                    'id' => "12345",
                    'name' => "product",
                    'key' => "productkey",
                    'description' => "product desc"
                ],
                '{"results": [{"id" : "12345","name": "product", "key": "productkey", "description": "product desc"}]}',
                '{"version":null,"actions":[]}'
            ],
            [
                [
                    'id' => "12345",
                    'name' => "product",
                    'key' => "productkey",
                    'description' => "product desc"
                ],
                '{"results": [{"id" : "12345","name": "product", "description": "product desc"}]}',
                '{"version":null,"actions":[ {"action":"setKey","key":"productkey"}]}'
            ],
            // add attribute
            [
                [
                    'id' => "12345",
                    'name' => "product",
                    'attributes' => [
                        ['name' => 'test-attribute']
                    ]
                ],
                '{"results": [{"id" :"12345","name": "product", "attributes": []}]}',
                '{"version":null,"actions":[
                    {"action":"addAttributeDefinition","attribute":{"name":"test-attribute"}}
                ]}'
            ],
            //remove attribute
            [
                [
                    'id' => "12345",
                    'name' => "product",
                    "attributes"=>[]
                ],
                '{"results": [{"id" :"12345","name": "product", "attributes": [{"name":"test-attribute"}]}]}',
                '{"version":null,"actions":[
                    {"action":"removeAttributeDefinition","name":"test-attribute"}
                ]}'
            ],
            //update attribute-label
            [
                [
                'id' => "12345",
                'name' => "product",
                "attributes"=>
                    [
                        [
                            'name' => 'test-attribute',
                            "label"=>
                                [
                                    "en"=> "Date of Creation",
                                    "it"=>"Date of Creation",
                                    "de"=> "Erstelldatum neu"
                                ],

                        ]
                    ]
                ],
                '{"results": 
                [{
                    "id" :"12345",
                    "name": "product", 
                    "attributes":
                     [{
                        "name":"test-attribute",
                        "label": {
                            "en": "Date of Creation",
                            "it":"Date of Creation",
                            "de": "Erstelldatum"
                         }
                     }]
                 }]
                 }',
                '{"version":null,
                    "actions":[
                        {
                            "action":"changeLabel",
                            "attributeName":"test-attribute",
                            "label": {
                                "en": "Date of Creation",
                                "it":"Date of Creation",
                                "de": "Erstelldatum neu"
                            }
                        }
                ]}'
            ],
            //update attribute-inputTip
            [
                [
                    'id' => "12345",
                    'name' => "product",
                    "attributes"=>
                        [
                            [
                                'name' => 'test-attribute',
                                "label"=>
                                    [
                                        "en"=> "Date of Creation",
                                        "it"=>"Date of Creation",
                                        "de"=> "Erstelldatum"
                                    ],
                                'inputTip'=>
                                [
                                    "en"=> "tip_en",
                                    "it"=>"tip_it",
                                    "de"=> "tip_de"
                                ]
                            ]
                        ]
                ],
                '{"results": 
                [{
                    "id" :"12345",
                    "name": "product", 
                    "attributes":
                     [{
                        "name":"test-attribute",
                        "label": {
                            "en": "Date of Creation",
                            "it":"Date of Creation",
                            "de": "Erstelldatum"
                        },
                        "inputTip": {
                                "en": "tip_en",
                                "it": "tip_it"                                
                        }
                     }]
                 }]
                 }',
                '{"version":null,
                    "actions":[                       
                        {
                           "action":"setInputTip",
                            "attributeName":"test-attribute",                           
                            "inputTip": {
                                "en": "tip_en",
                                "it": "tip_it",
                                "de": "tip_de"
                            } 
                        }
                ]}'
            ],
            //update attribute-IsSearchable
            [
                [
                    'id' => "12345",
                    'name' => "product",
                    "attributes"=>
                        [
                            [
                                'name' => 'test-attribute',
                                "isSearchable" =>true
                            ]
                        ]
                ],
                '{"results": 
                [{
                    "id" :"12345",
                    "name": "product", 
                    "attributes":
                     [{
                        "name":"test-attribute",
                        "isSearchable": "false"
                     }]
                 }]
                 }',
                '{"version":null,
                    "actions":[
                        {
                            "action":"changeIsSearchable",
                            "attributeName":"test-attribute",
                            "isSearchable" : true
                        }
                ]}'
            ],
            //add attribute-PlainValueEnum / change PlainEnumLabel
            [
                [
                    'id' => "12345",
                    'name' => "product",
                    "attributes"=>
                        [
                            [
                                'name' => 'test-attribute',
                                'type' => [
                                    'name' => 'enum',
                                    'values' => [
                                        ['key' => 'test-enum', 'label' => 'test-enum-label']
                                    ]
                                ]
                            ]
                        ]
                ],
                '{"results": 
                [{
                    "id" :"12345",
                    "name": "product", 
                    "attributes":
                     [{
                        "name":"test-attribute",
                        "type":{
                            "name": "enum",
                            "values": [{
                                "key" : "test-enum",
                                "label" : "test-enum-label"
                            }]
                        }
                     }]
                 }]
                 }',
                '{"version":null,
                    "actions":[
                        
                ]}'
            ],
            [
                [
                    'id' => "12345",
                    'name' => "product",
                    "attributes"=>
                        [
                            [
                                'name' => 'test-attribute',
                                'type' => [
                                    'name' => 'enum',
                                    'values' => [
                                        ['key' => 'test-enum', 'label' => 'test-enum-label edit'],
                                        ['key' => 'test-enum-new', 'label' => 'test-enum-label-new']
                                    ]
                                ]
                            ]
                        ]
                ],
                '{"results": 
                [{
                    "id" :"12345",
                    "name": "product", 
                    "attributes":
                     [{
                        "name":"test-attribute",
                        "type":{
                            "name": "enum",
                            "values": [{
                               "key" : "test-enum",
                               "label" : "test-enum-label"
                            }]
                        }
                     }]
                 }]
                 }',
                '{"version":null,
                    "actions":[
                        {
                            "action":"addPlainEnumValue",
                            "attributeName":"test-attribute",
                            "value" : {
                                "key" : "test-enum-new",
                                "label" : "test-enum-label-new"
                            }
                        },
                        {
                           "action":"changePlainEnumValueLabel",
                            "attributeName":"test-attribute",
                            "newValue" : {
                                "key" : "test-enum",
                                "label" : "test-enum-label edit"
                            } 
                        }
                    ]
                }'
            ],
            //add attribute-LocalizedEnumValue / change LocalizedEnumLabel
            [
                [
                    'id' => "12345",
                    'name' => "product",
                    "attributes"=>
                        [
                            [
                                'name' => 'test-attribute',
                                'type' => [
                                    'name' => 'lenum',
                                    'values' => [
                                         ['key' => 'test-lenum', 'label' => ["en"=>"en label edit"]],
                                         ['key' => 'test-lenum-new', 'label' => ["en"=>"en label","de"=>"de label"]]
                                    ]
                                ]
                            ]
                        ]
                ],
                '{"results": 
                [{
                    "id" :"12345",
                    "name": "product", 
                    "attributes":
                     [{
                        "name":"test-attribute",
                        "type":{
                            "name": "lenum",
                            "values": [{
                               "key" : "test-lenum",
                               "label" : {
                                    "en" : "en label"
                               }
                            }]
                        }
                     }]
                 }]
                 }',
                '{"version":null,
                    "actions":
                    [
                        {
                            "action":"addLocalizedEnumValue",
                            "attributeName":"test-attribute",
                            "value": {
                               "key" : "test-lenum-new",
                               "label" : {
                                    "en" : "en label",
                                    "de" : "de label"
                               }
                            }
                        },
                        {
                           "action":"changeLocalizedEnumValueLabel",
                            "attributeName":"test-attribute",
                            "newValue" :
                             {
                                "key" : "test-lenum",
                                "label" :
                                 {
                                    "en" : "en label edit"
                                 }
                            } 
                        }
                    ]
                }'
            ]
        ];
    }

    /**
     * @dataProvider getTestData
     */
    public function testUpdateRequest($data, $response, $expected)
    {
        $client = $this->prophesize(Client::class);

        $client->execute(Argument::type(ProductTypeQueryRequest::class))->will(function ($args) use ($response) {
            $response = new PagedQueryResponse(new Response(200, [], $response), $args[0]);

            return $response;
        });

        $requestBuilder = new ProductTypeRequestBuilder($client->reveal());

        $returnedRequest= $requestBuilder->createRequest($data, "id");

        $this->assertInstanceOf(ProductTypeUpdateRequest::class, $returnedRequest);

        $this->assertJsonStringEqualsJsonString($expected, (string)$returnedRequest->httpRequest()->getBody());
    }
}
