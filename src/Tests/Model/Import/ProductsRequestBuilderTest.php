<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 05/12/16
 * Time: 10:30
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Model\Import;

use Commercetools\Core\Client;
use Commercetools\Core\Config;
use Commercetools\Core\Request\Categories\CategoryQueryRequest;
use Commercetools\Core\Request\CustomerGroups\CustomerGroupQueryRequest;
use Commercetools\Core\Request\Products\ProductProjectionQueryRequest;
use Commercetools\Core\Request\ProductTypes\ProductTypeQueryRequest;
use Commercetools\Core\Request\TaxCategories\TaxCategoryQueryRequest;
use Commercetools\Core\Response\PagedQueryResponse;
use Commercetools\Symfony\CtpBundle\Model\Import\CsvToJson;
use Commercetools\Symfony\CtpBundle\Model\Import\ProductsRequestBuilder;
use Commercetools\Core\Request\Products\ProductCreateRequest;
use Commercetools\Core\Request\Products\ProductUpdateRequest;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

class ProductsRequestBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateRequest()
    {
        $client = $this->prophesize(Client::class);

        $config = new Config();
        $client->getConfig()->willReturn($config);
        $client->execute(Argument::type(ProductProjectionQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{}'), $args[0]);

            return $response;
        });
        $client->execute(Argument::type(CategoryQueryRequest::class))->will(function ($args) {
                $response = new PagedQueryResponse(new Response(200, [], '{ "results": []}'), $args[0]);

                return $response;
        });
        $client->execute(Argument::type(TaxCategoryQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{ "results": []}'), $args[0]);

            return $response;
        });
        $client->execute(Argument::type(ProductTypeQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{ "results": []}'), $args[0]);

            return $response;
        });

        $client->execute(Argument::type(CustomerGroupQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{ "results": []}'), $args[0]);

            return $response;
        });

        $requestBuilder = new ProductsRequestBuilder($client->reveal());

        $data = ['productType'=> 'main','description' => [], 'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'], 'name' => ['de'=>'product name de', 'en' => 'product name en'], 'key' => "productkey", "details" => []];

        $returnedRequest= $requestBuilder->createRequest($data, "key");

        $this->assertInstanceOf(ProductCreateRequest::class, $returnedRequest);
        $this->assertEquals(
            '{"productType":{"typeId":"product-type","key":"main"},"slug":{"de":"product-slug-de","en":"product-slug-en"},"name":{"de":"product name de","en":"product name en"},"key":"productkey"}',
            (string)$returnedRequest->httpRequest()->getBody()
        );
    }

    public function getTestData()
    {
        return [
            [
                [
                    "sku"=>"1234",
                    'name' => ['de'=>'product name de', 'en' => 'product name en'],
                    'description' => [],
                    'key' => "productkey",
                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234", "name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[],
                    "version" :""
                }',
                ['sku' => '1234', 'name.de' => 'product name de', 'name.en' => 'product name en', 'description.de' => '', 'key' => 'productkey'],
            ],
            [
                [
                    "sku"=>"1234",
                    'name' => ['de'=>'product name de', 'en' => 'product name en'],
                    'description' => [],
                    'key' => "productkey",
                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","description":{"de":"product name de","en" : "product name en"},"name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[
                    {"action":"setDescription"}
                    ],
                    "version" :""
                }'
            ],
            [
                [
                    "sku"=>"1234",
                    'name' => ['de'=>'product name de', 'en' => 'product name en'],
                    'metaTitle' => [],
                    'key' => "productkey",
                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[],
                    "version" :""
                }',
                ['sku' => '1234', 'name.de' => 'product name de', 'name.en' => 'product name en', 'metaTitle.de' => '', 'key' => 'productkey'],
            ],
            [
                [
                    "sku"=>"1234",
                    'name' => ['de'=>'product name de', 'en' => 'product name en'],
                    'metaTitle' => [],
                    'key' => "productkey",
                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","metaTitle":{"de":"product metaTitle de","en" : "product metaTitle en"},"name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[
                    {"action":"setMetaTitle"}
                    ],
                    "version" :""
                }',
                ['sku' => '1234', 'name.de' => 'product name de', 'name.en' => 'product name en', 'metaTitle.de' => '', 'key' => 'productkey'],
            ],
            //change Name
            [
                [
                    "sku"=>"1234",
                    'name' => ['de'=>'new product name de', 'en' => 'new product name en'],
                    'key' => "productkey",
                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[
                    {"action":"changeName","name":{"de":"new product name de","en" : "new product name en"}}
                    ],
                    "version" :""
                }'
            ],
            [
                [
                    "sku"=>"1234",
                    'name' => ['de'=>'new product name de', 'en' => 'product name en'],
                    'key' => "productkey",
                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","name":{"en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[
                    {"action":"changeName","name":{"de":"new product name de","en" : "product name en"}}
                    ],
                    "version" :""
                }'
            ],
            [
                [
                    "sku"=>"1234",
                    'name' => ['de'=>'product name de', 'en' => 'product name en'],
                    'key' => "productkey",
                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[],
                    "version" :""
                }'
            ],
            //change slug
            [
                [
                    "sku"=>"1234",
                    'slug' => ['de'=>'new-product-slug-de', 'en' => 'new-product-slug-en'],
                    'key' => "productkey",
                ],
                '{"results": [{"productType":{"typeId":"product-type","key":"main"},"version" :"","id" :"12345","sku":"1234","slug":{"de":"product-slug-de","en" : "product-slug-en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[
                    {"action":"changeSlug","slug":{"de":"new-product-slug-de","en" : "new-product-slug-en"}}
                    ],
                    "version" :""
                }'
            ],
            [
                [
                    "sku"=>"1234",
                    'slug' => ['de'=>'new-product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",
                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","slug":{"en" : "product-slug-en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[
                    {"action":"changeSlug","slug":{"de":"new-product-slug-de","en" : "product-slug-en"}}
                    ],
                    "version" :""
                }'
            ],
            [
                [
                    "sku"=>"1234",
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",
                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","slug":{"de":"product-slug-de","en" : "product-slug-en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[],
                    "version" :""
                }'
            ],
            //variants
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",
                    'variants'=> [["sku"=>"1234","key"=>"product1","variantId"=>1]]

                ],
                '{"results": [{"version" :"","id" :"12345","slug":{"de":"product-slug-de","en" : "product-slug-en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[
                    {"action":"addVariant","sku":"1234"}
                    ],
                    "version" :""
                }'
            ],
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",

                    'variants'=> [["sku"=>"1234","variantId"=>2,"images"=>"","attributes"=>[]]]

                ],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "productType": {"typeId":"product-type","id":"1"},
                    "slug":{"de":"product-slug-de","en" : "product-slug-en"},
                    "key": "productkey","categories": {},

                    "masterVariant": {},
                    "variants": [
                        {
                            "prices":[],
                            "sku":"1234",
                            "images":[],
                            "attributes":{}
                        }
                    ]
                    }]
                 }',
                '{"actions":[],
                    "version" :""
                }'
            ],
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",

                    'variants'=> [["sku"=>"1234","variantId"=>2,"images"=>"","attributes"=>[]], ["sku"=>"1244","variantId"=>3,"images"=>"","attributes"=>[]]]

                ],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "productType": {"typeId":"product-type","id":"1"},
                    "slug":{"de":"product-slug-de","en" : "product-slug-en"},
                    "key": "productkey","categories": {},

                    "masterVariant": {},
                    "variants": [
                        {
                            "sku":"1234",
                            "images":{},
                            "attributes":{},
                            "prices":[]
                        }
                    ]
                    }]
                 }',
                '{"actions":[{"action":"addVariant","sku":"1244","images":[],"attributes":[]}],
                    "version" :""
                }'
            ],
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",

                    'variants'=> [["sku"=>"1234","variantId"=>2,"images"=>"","attributes"=>[]], ]

                ],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "productType": {"typeId":"product-type","id":"1"},
                    "slug":{"de":"product-slug-de","en" : "product-slug-en"},
                    "key": "productkey","categories": {},

                    "masterVariant": {},
                    "variants": [
                        {
                            "sku":"1234",
                            "images":{},
                            "prices":[],
                            "attributes":{}
                        },
                        {
                            "sku":"1244",
                            "images":{},
                            "attributes":{},
                            "prices":[]
                        }
                    ]
                    }]
                 }',
                '{"actions":[{"action":"removeVariant","sku":"1244"}],
                    "version" :""
                }'
            ],
            //Attributes
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",

                    'variants'=> [["sku"=>"1234","variantId"=>2,"images"=>"","size"=>"35"] ]

                ],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "productType": {"typeId":"product-type","id":"1"},
                    "slug":{"de":"product-slug-de","en" : "product-slug-en"},
                    "key": "productkey","categories": {},

                    "masterVariant": {},
                    "variants": [
                        {
                            "id": 2,
                            "sku":"1234",
                            "images":{},
                            "prices":[],
                            "attributes":
                            [
                                {
                                    "name":"size",
                                    "value": { "key": "34", "label": "34" }
                                }
                            ]
                        }
                    ]
                    }]
                 }',
                '{"actions":[{"action":"setAttribute","variantId":2,"name":"size","value":"35"}],
                    "version" :""
                }'
            ],
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",

                    'variants'=> [["sku"=>"1234","variantId"=>2,"images"=>"","size"=>"35"] ]

                ],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "productType": {"typeId":"product-type","id":"1"},
                    "slug":{"de":"product-slug-de","en" : "product-slug-en"},
                    "key": "productkey","categories": {},

                    "masterVariant": {},
                    "variants": [
                        {
                            "id": 2,
                            "sku":"1234",
                            "images":{},
                            "prices":[],
                            "attributes":
                            [
                                {
                                    "name":"size",
                                    "value": { "key": "35", "label": "35" }
                                }
                            ]
                        }
                    ]
                    }]
                 }',
                '{"actions":[],
                    "version" :""
                }'
            ],
            //master variant
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",
                    'masterVariant'=> ["sku"=>"1234","key"=>"product1","variantId"=>1]

                ],
                '{"results": [
                    {"version" :"","id" :"12345",
                    "slug":{"de":"product-slug-de","en" : "product-slug-en"},
                    "key": "productkey",
                    "categories": {},
                    "masterVariant": {
                        "sku":"1234",
                        "id":1
                     },
                     "variants": {}
                     }
                   ]
                }',
                '{"actions":[],
                    "version" :""
                }'
            ],
            // prices
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",

                    'variants'=> [["sku"=>"1243","variantId"=>2,"prices"=>"EUR 9750","images"=>"","attributes"=>[]], ]

                ],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "productType": {"typeId":"product-type","id":"1"},
                    "slug":{"de":"product-slug-de","en" : "product-slug-en"},
                    "key": "productkey","categories": {},

                    "masterVariant": {},
                    "variants": [
                        {
                            "sku":"1243",
                            "images":{},
                            "prices":[],
                            "attributes":{}
                        }
                    ]
                    }]
                 }',
                '{"actions":[{"action":"addPrice","sku":"1243","price":{"value":{"currencyCode":"EUR","centAmount":9750}}}],
                    "version" :""
                }'
            ],
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",

                    'variants'=> [["sku"=>"1243","variantId"=>2,"prices"=>"EUR 9750","images"=>"","attributes"=>[]], ]

                ],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "productType": {"typeId":"product-type","id":"1"},
                    "slug":{"de":"product-slug-de","en" : "product-slug-en"},
                    "key": "productkey","categories": {},

                    "masterVariant": {},
                    "variants": [
                        {
                            "sku":"1243",
                            "images":{},
                            "prices":[
                            {
                                "value": {
                                    "currencyCode":"EUR",
                                    "centAmount":9750
                                },
                                "id": "1"
                            }
                            ],
                            "attributes":{}
                        }
                    ]
                    }]
                 }',
                '{"actions":[],
                    "version" :""
                }'
            ],
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",

                    'variants'=> [["sku"=>"1243","variantId"=>2,"prices"=>"EUR 9750","images"=>"","attributes"=>[]], ]

                ],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "productType": {"typeId":"product-type","id":"1"},
                    "slug":{"de":"product-slug-de","en" : "product-slug-en"},
                    "key": "productkey","categories": {},

                    "masterVariant": {},
                    "variants": [
                        {
                            "sku":"1243",
                            "images":{},
                            "prices":[
                                {
                                    "value": {
                                        "currencyCode":"EUR",
                                        "centAmount":9700
                                    },
                                    "id": "abs"
                                }
                            ],
                            "attributes":{}
                        }
                    ]
                    }]
                 }',
                '{"actions":[
                    {
                        "action":"changePrice",
                        "price":
                            {
                                "value":
                                {
                                    "currencyCode":"EUR",
                                    "centAmount":9750
                                 }
                            },
                        "priceId":"abs"
                    }
                ],
                    "version" :""
                }'
            ],
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",

                    'variants'=> [["sku"=>"1243","variantId"=>2,"prices"=>"","images"=>"","attributes"=>[]], ]

                ],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "productType": {"typeId":"product-type","id":"1"},
                    "slug":{"de":"product-slug-de","en" : "product-slug-en"},
                    "key": "productkey","categories": {},

                    "masterVariant": {},
                    "variants": [
                        {
                            "sku":"1243",
                            "images":{},
                            "prices":[
                                {
                                    "value": {
                                        "currencyCode":"EUR",
                                        "centAmount":9750
                                    },
                                    "id": "abs"
                                }
                            ],
                            "attributes":{}
                        }
                    ]
                    }]
                 }',
                '{"actions":[
                    {
                        "action":"removePrice",
                        "priceId":"abs"
                    }
                ],
                    "version" :""
                }'
            ],
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",

                    'variants'=> [["sku"=>"1243","variantId"=>2,"prices"=>"EUR 9750;DE-EUR 7800|5460","images"=>"","attributes"=>[]], ]

                ],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "productType": {"typeId":"product-type","id":"1"},
                    "slug":{"de":"product-slug-de","en" : "product-slug-en"},
                    "key": "productkey","categories": {},

                    "masterVariant": {},
                    "variants": [
                        {
                            "sku":"1243",
                            "images":{},
                            "prices":[
                                {
                                    "value": {
                                        "currencyCode":"EUR",
                                        "centAmount":9750
                                    },
                                    "id": "abs"
                                }
                            ],
                            "attributes":{}
                        }
                    ]
                    }]
                 }',
                '{"actions":[
                    {
                        "action":"addPrice",
                        "price":
                            {
                                "value":
                                {
                                    "currencyCode":"EUR",
                                    "centAmount":7800
                                },
                                "country":"DE"
                            },
                        "sku":"1243"
                    }
                ],
                    "version" :""
                }'
            ],
            //images
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",

                    'variants'=> [["sku"=>"1243","variantId"=>2,"prices"=>"","images"=>"imageUrl","attributes"=>[]], ]

                ],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "productType": {"typeId":"product-type","id":"1"},
                    "slug":{"de":"product-slug-de","en" : "product-slug-en"},
                    "key": "productkey","categories": {},

                    "masterVariant": {},
                    "variants": [
                        {
                            "sku":"1243",
                            "images":[],
                            "prices":[],
                            "attributes":{}
                        }
                    ]
                    }]
                 }',
                '{"actions":[
                    {
                        "action":"addExternalImage",
                        "sku":"1243",
                        "image":{
                            "url":"imageUrl",
                            "dimensions":{"w":0,"h":0}
                        }
                    }
                ],
                    "version" :""
                }'
            ],
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",

                    'variants'=> [["sku"=>"1243","variantId"=>2,"prices"=>"","images"=>"imageUrl","attributes"=>[]], ]

                ],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "productType": {"typeId":"product-type","id":"1"},
                    "slug":{"de":"product-slug-de","en" : "product-slug-en"},
                    "key": "productkey","categories": {},

                    "masterVariant": {},
                    "variants": [
                        {
                            "sku":"1243",
                            "images":[{"url":"oldImageUrl","dimensions":{"w":0,"h":0}}],
                            "prices":[],
                            "attributes":{}
                        }
                    ]
                    }]
                 }',
                '{"actions":[
                    {
                        "action":"removeImage",
                        "sku":"1243",
                        "imageUrl":"oldImageUrl"
                    },
                    {
                        "action":"addExternalImage",
                        "sku":"1243",
                        "image":{
                            "url":"imageUrl",
                            "dimensions":{"w":0,"h":0}
                        }
                    }
                ],
                    "version" :""
                }'
            ],
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",

                    'variants'=> [["sku"=>"1243","variantId"=>2,"prices"=>"","images"=>"imageUrl","attributes"=>[]], ]

                ],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "productType": {"typeId":"product-type","id":"1"},
                    "slug":{"de":"product-slug-de","en" : "product-slug-en"},
                    "key": "productkey","categories": {},

                    "masterVariant": {},
                    "variants": [
                        {
                            "sku":"1243",
                            "images":[{"url":"imageUrl","dimensions":{"w":0,"h":0}}],
                            "prices":[],
                            "attributes":{}
                        }
                    ]
                    }]
                 }',
                '{"actions":[],
                    "version" :""
                }'
            ]
        ];
    }

    /**
     * @dataProvider getTestData
     */
    public function testUpdateRequest($data, $response, $expected, $csvLine = null)
    {
        $client = $this->prophesize(Client::class);

        if (!is_null($csvLine)) {
            $csvToJson = new CsvToJson();
            $data = $csvToJson->transform(array_values($csvLine), array_flip(array_keys($csvLine)));
        }
        $config = new Config();
        $client->getConfig()->willReturn($config);
        $client->execute(Argument::type(ProductProjectionQueryRequest::class))->will(function ($args) use ($response) {
            $response = new PagedQueryResponse(new Response(200, [], $response), $args[0]);

            return $response;
        });
        $client->execute(Argument::type(CategoryQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{ "results": []}'), $args[0]);

            return $response;
        });
        $client->execute(Argument::type(TaxCategoryQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{ "results": []}'), $args[0]);

            return $response;
        });
        $client->execute(Argument::type(CustomerGroupQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{ "results": []}'), $args[0]);

            return $response;
        });
        $client->execute(Argument::type(ProductTypeQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(
                new Response(
                    200,
                    [],
                    '{
                        "results": [{
                            "id":"1",
                            "name":"product",
                            "key":"main",
                            "description":"product desc",
                            "attributes":[{
                                "name":"size",
                                "type": {
                                    "name": "enum",
                                    "values": [
                                        {
                                            "key": "35",
                                            "label": "35"
                                        },
                                        {
                                            "key": "34",
                                            "label": "34"
                                        }
                                    ]
                                }
                            }]
                        }]
                    }'
                ),
                $args[0]
            );

            return $response;
        });

        $requestBuilder = new ProductsRequestBuilder($client->reveal());

        $returnedRequest= $requestBuilder->createRequest($data, "key");

        $this->assertInstanceOf(ProductUpdateRequest::class, $returnedRequest);

        $this->assertJsonStringEqualsJsonString($expected, (string)$returnedRequest->httpRequest()->getBody());
    }

}
