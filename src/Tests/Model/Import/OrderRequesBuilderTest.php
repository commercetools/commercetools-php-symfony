<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 31/01/17
 * Time: 15:21
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Model\Import;

use Commercetools\Core\Client;
use Commercetools\Core\Config;
use Commercetools\Core\Request\Channels\ChannelQueryRequest;
use Commercetools\Core\Request\Orders\OrderImportRequest;
use Commercetools\Core\Request\Orders\OrderQueryRequest;
use Commercetools\Core\Request\CustomerGroups\CustomerGroupQueryRequest;
use Commercetools\Core\Request\TaxCategories\TaxCategoryQueryRequest;
use Commercetools\Core\Response\PagedQueryResponse;
use Commercetools\Symfony\CtpBundle\Model\Import\CsvToJson;
use Commercetools\Symfony\CtpBundle\Model\Import\OrdersRequestBuilder;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

class OrderRequesBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function getAddTestData()
    {
        return [
            [
                [],
                '{
                    "id":"1",
                    "lineItems":
                        [
                            { 
                                "productId" : "3",
                                "name":{"en":"nameEN"},
                                "variant":{"variantId":"123","sku":"sku"},
                                "quantity" : 3,
                                "price" : {"value":{"currencyCode":"EUR","centAmount":2500}}
                            }
                        ]
                 }',
                [
                    'id'=>'1',
                    'lineItems'=>
                        [
                            0=>
                                [
                                    'id'=>'2',
                                    'productId'=>'3',
                                    'name'=>['en'=>'nameEN'],
                                    'variant'=>['variantId'=>'123','sku'=>'sku'],
                                    'quantity'=>'3',
                                    'price'=>'EUR 2500'
                                ]
                        ]
                ]
            ],
            [
                [],
                '{
                    "id":"1",
                    "lineItems":
                        [
                            { 
                                "name":{"en":"nameEN"},
                                "variant":
                                    {
                                        "variantId":"123","sku":"sku",
                                        "prices":[
                                            {
                                               "country":"DE",
                                                "value":{"currencyCode":"EUR","centAmount":9750}
                                            },
                                            {
                                                "value":{"currencyCode":"EUR","centAmount":7800}
                                            }
                                        ],
                                        "images":[
                                            {  
                                               "url" : "url1",
                                               "dimensions":{"w":0,"h":0}
                                            },
                                            {  
                                               "url" : "url2",
                                               "dimensions":{"w":0,"h":0}
                                            }
                                        ]
                                    },
                                "quantity" : 3,
                                "price" : {"value":{"currencyCode":"EUR","centAmount":2500}}
                            }
                        ]
                 }',
                [
                    'id'=>'1',
                    'lineItems'=>
                        [
                            0=>
                                [
                                    'name'=>['en'=>'nameEN'],
                                    'variant'=>['variantId'=>'123','sku'=>'sku',"prices"=>"DE-EUR 9750;EUR 7800","images"=>"url1;url2"],
                                    'quantity'=>'3',
                                    'price'=>'EUR 2500'
                                ]
                        ]
                ]
            ],
            [
                [],
                '{
                    "id":"1",
                    "lineItems":
                        [
                            { 
                                "productId" : "3",
                                "name":{"en":"nameEN"},
                                "variant":{"variantId":"123","sku":"sku"},
                                "quantity" : 3,
                                "price" : {"value":{"currencyCode":"EUR","centAmount":2500}}
                            },
                            { 
                                "productId" : "4",
                                "name":{"en":"nameEN"},
                                "variant":{"variantId":"123","sku":"sku"},
                                "quantity" : 3,
                                "price" : {"value":{"currencyCode":"EUR","centAmount":2500}}
                            }
                        ]
                 }',
                [
                    'id'=>'1',
                    'lineItems'=>
                        [
                            0=>
                                [
                                    'id'=>'2',
                                    'productId'=>'3',
                                    'name'=>['en'=>'nameEN'],
                                    'variant'=>['variantId'=>'123','sku'=>'sku',"prices"=>"","images"=>""],
                                    'quantity'=>'3',
                                    'price'=>'EUR 2500'
                                ],
                            1=>
                                [
                                    'id'=>'3',
                                    'productId'=>'4',
                                    'name'=>['en'=>'nameEN'],
                                    'variant'=>['variantId'=>'123','sku'=>'sku'],
                                    'quantity'=>'3',
                                    'price'=>'EUR 2500'
                                ]
                        ]
                ]
            ],
            [
                [],
                '{
                    "id":"1",
                    "totalPrice":{"currencyCode":"EUR","centAmount":2500},
                    "orderNumber":"order number",
                    "customerId":"1",
                    "customerEmail":"email",
                    "country":"DE",
                    "orderState":"Open",
                    "shipmentState":"Shipped",
                    "paymentState":"BalanceDue",
                    "completedAt":"2001-09-11T14:00:00.000Z",
                    "inventoryMode":"TrackOnly",
                    "taxRoundingMode":"HalfEven",
                    "shippingAddress":{
                        "streetName":"street",
                        "city":"berlin"
                    },
                    "billingAddress":{
                        "streetName":"street",
                        "city":"berlin"
                    },
                    "taxedPrice":{
                        "totalNet":{"currencyCode":"EUR","centAmount":2500},
                        "totalGross":{"currencyCode":"EUR","centAmount":2500},
                        "taxPortions":[{"name":"taxName","rate":1,"amount":{"currencyCode":"EUR","centAmount":200}}]
                    },
                    "lineItems":
                        [
                            { 
                                "productId" : "3",
                                "name":{"en":"nameEN"},
                                "variant":{"variantId":"123","sku":"sku"},
                                "quantity" : 3,
                                "price" : {"value":{"currencyCode":"EUR","centAmount":2500}},
                                "custom":{
                                    "type":{"key":"my-category"},
                                    "fields":{"description":"my description"}
                                }
                            }
                        ],
                     "custom":{
                        "type":{"key":"my-category"},
                        "fields":{"description":"my description"}
                    }
                 }',
                [
                    'id'=>'1',
                    'totalPrice'=>'EUR 2500',
                    'orderNumber'=>'order number',
                    'customerId'=>'1',
                    'customerEmail'=>'email',
                    'country'=>'DE',
                    'orderState'=>'Open',
                    'shipmentState'=>'Shipped',
                    'paymentState'=>'BalanceDue',
                    'completedAt'=>'2001-09-11T14:00:00.000Z',
                    'inventoryMode'=>'TrackOnly',
                    'taxRoundingMode'=>'HalfEven',
                    'totalNet'=>"EUR 2500",
                    'totalGross'=>"EUR 2500",
                    'taxPortions'=>['name'=>'taxName','rate'=>1,'amount'=>'EUR 200'],
                    'shippingAddress'=>['streetName'=>'street','city'=>'berlin'],
                    'billingAddress'=>['streetName'=>'street','city'=>'berlin'],
                    'lineItems'=>
                        [
                            0=>
                                [
                                    'id'=>'2',
                                    'productId'=>'3',
                                    'name'=>['en'=>'nameEN'],
                                    'variant'=>['variantId'=>'123','sku'=>'sku'],
                                    'quantity'=>'3',
                                    'price'=>'EUR 2500',
                                    "custom"=>
                                        [
                                            "type"=> [
                                                "key"=> "my-category"
                                            ],
                                            "fields"=> [
                                                "description"=> "my description"
                                            ]
                                        ]
                                ]
                        ],
                    "custom"=>
                        [
                                "type"=> [
                                    "key"=> "my-category"
                                ],
                                "fields"=> [
                                    "description"=> "my description"
                                ]
                        ]
                ]
            ],
            [
                [],
                '{
                    "id":"1",
                    "totalPrice":{"currencyCode":"EUR","centAmount":2500},
                    "lineItems":
                        [
                            { 
                                "productId" : "3",
                                "name":{"en":"nameEN"},
                                "variant":{"variantId":"123","sku":"sku"},
                                "quantity" : 3,
                                "price" : {"value":{"currencyCode":"EUR","centAmount":2500}}
                            }
                        ]
                 }',
                [
                    'id'=>'1',
                    'totalPrice'=>'EUR 2500',
                    'orderNumber'=>'',
                    'customerId'=>'',
                    'customerEmail'=>'',
                    'country'=>'',
                    'orderState'=>'',
                    'shipmentState'=>'',
                    'paymentState'=>'',
                    'completedAt'=>'',
                    'inventoryMode'=>'',
                    'taxRoundingMode'=>'',
                    'shippingAddress'=>[],
                    'billingAddress'=>[],
                    'lineItems'=>
                        [
                            0=>
                                [
                                    'id'=>'2',
                                    'productId'=>'3',
                                    'name'=>['en'=>'nameEN'],
                                    'variant'=>['variantId'=>'123','sku'=>'sku'],
                                    'quantity'=>'3',
                                    'price'=>'EUR 2500',
                                    "custom"=>""
                                ]
                        ],
                    "custom"=>""
                ]
            ],
            //customLineItems
            [
                [],
                '{
                    "id":"1",
                    "customLineItems":
                        [
                            { 
                                "name":{"en":"nameEN"},
                                "slug":"slug",
                                "quantity" : 3,
                                "money":{"currencyCode":"EUR","centAmount":2500},
                                "externalTaxRate":{"name":"name","amount":0,"country":"DE","state":"Berlin"},
                                "custom":{
                                    "type":{"key":"my-category"},
                                    "fields":{"description":"my description"}
                                }
                            }
                        ]
                 }',
                [
                    'id'=>'1',
                    'customLineItems'=>
                        [
                            0=>
                                [
                                    'name'=>['en'=>'nameEN'],
                                    'slug'=>'slug',
                                    'variant'=>['variantId'=>'123','sku'=>'sku'],
                                    'quantity'=>'3',
                                    'money'=>'EUR 2500',
                                    'externalTaxRate'=>['name'=>'name','amount'=>0,'country'=>'DE','state'=>'Berlin'],
                                    "custom"=>
                                        [
                                            "type"=> [
                                                "key"=> "my-category"
                                            ],
                                            "fields"=> [
                                                "description"=> "my description"
                                            ]
                                        ]
                                ]
                        ]
                ]
            ],
            [
                 [],
                '{
                    "id":"1",
                    "customLineItems":
                        [
                            { 
                                "name":{"en":"nameEN"},
                                "slug":"slug",
                                "quantity" : 3,
                                "money":{"currencyCode":"EUR","centAmount":2500},
                                "externalTaxRate":{"name":"name","country":"DE"}
                            },
                            { 
                                "name":{"en":"nameEN"},
                                "slug":"slug",
                                "quantity" : 3,
                                "money":{"currencyCode":"EUR","centAmount":2500}
                            }
                        ]
                 }',
                [
                    'id'=>'1',
                    'customLineItems'=>
                        [
                            0=>
                                [
                                    'name'=>['en'=>'nameEN'],
                                    'slug'=>'slug',
                                    'variant'=>['variantId'=>'123','sku'=>'sku'],
                                    'quantity'=>'3',
                                    'money'=>'EUR 2500',
                                    'externalTaxRate'=>['name'=>'name','country'=>'DE']
                                ],
                            1=>
                                [
                                    'name'=>['en'=>'nameEN'],
                                    'slug'=>'slug',
                                    'variant'=>['variantId'=>'123','sku'=>'sku'],
                                    'quantity'=>'3',
                                    'money'=>'EUR 2500',
                                    'externalTaxRate'=>[]
                                ]
                        ]
                ]
            ]
        ];
    }
    /**
     * @dataProvider getAddTestData
     */
    public function testCreateRequest($data, $expected, $csvLine = null)
    {
        $client = $this->prophesize(Client::class);
        if (!is_null($csvLine)) {
            $csvToJson = new CsvToJson();
            $data = $csvToJson->transform(array_values($csvLine), array_flip(array_keys($csvLine)));
        }
        $config = new Config();
        $client->getConfig()->willReturn($config);
        $client->execute(Argument::type(OrderQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{}'), $args[0]);

            return $response;
        });

        $client->execute(Argument::type(CustomerGroupQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{ "results": []}'), $args[0]);

            return $response;
        });

        $client->execute(Argument::type(ChannelQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{ "results": []}'), $args[0]);

            return $response;
        });

        $client->execute(Argument::type(TaxCategoryQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{ "results": []}'), $args[0]);
            return $response;
        });

        $requestBuilder = new OrdersRequestBuilder($client->reveal());

        $returnedRequests = $requestBuilder->createRequest([$data], "id");
        $returnedRequest = current($returnedRequests);
        $this->assertInstanceOf(OrderImportRequest::class, $returnedRequest);
        $this->assertJsonStringEqualsJsonString(
            $expected,
            (string)$returnedRequest->httpRequest()->getBody()
        );
    }
}
