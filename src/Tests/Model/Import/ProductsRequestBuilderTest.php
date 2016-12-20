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
use Commercetools\Symfony\CtpBundle\Model\Import\ProductsImport;
use Commercetools\Symfony\CtpBundle\Model\Import\ProductsRequestBuilder;
use Commercetools\Core\Request\Products\ProductCreateRequest;
use Commercetools\Core\Request\Products\ProductUpdateRequest;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

class ProductsRequestBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function getAddTestData()
    {
        return [
            [
                [

                ],
                '{"productType":{"typeId":"product-type","key":"main"},"slug":{"de":"product-slug-de","en":"product-slug-en"},"name":{"de":"product name de","en":"product name en"},"key":"productkey"}',
                ['productType'=> 'main', 'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'], 'name' => ['de'=>'product name de', 'en' => 'product name en'], 'key' => "productkey", "details" => []]
            ],
            //description
            [
                [

                ],
                '{"productType":{"typeId":"product-type","key":"main"},"slug":{"de":"product-slug-de","en":"product-slug-en"},"name":{"de":"product name de","en":"product name en"},"key":"productkey"}',
                ['productType'=> 'main','description.de' => '', 'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'], 'name' => ['de'=>'product name de', 'en' => 'product name en'], 'key' => "productkey", "details" => []]
            ],
            [
                [

                ],
                '{"productType":{"typeId":"product-type","key":"main"},"description":{"de":"de desc","en":"en desc"},"slug":{"de":"product-slug-de","en":"product-slug-en"},"name":{"de":"product name de","en":"product name en"},"key":"productkey"}',
                ['productType'=> 'main','description.de' => 'de desc', 'description.en' => 'en desc','slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'], 'name' => ['de'=>'product name de', 'en' => 'product name en'], 'key' => "productkey", "details" => []]
            ],
            //metaTitle
            [
                [

                ],
                '{"productType":{"typeId":"product-type","key":"main"},"slug":{"de":"product-slug-de","en":"product-slug-en"},"name":{"de":"product name de","en":"product name en"},"key":"productkey"}',
                ['productType'=> 'main','metaTitle.de' => '', 'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'], 'name' => ['de'=>'product name de', 'en' => 'product name en'], 'key' => "productkey", "details" => []]
            ],
            [
                [

                ],
                '{"productType":{"typeId":"product-type","key":"main"},"metaTitle":{"de":"de metaTitle","en":"en metaTitle"},"slug":{"de":"product-slug-de","en":"product-slug-en"},"name":{"de":"product name de","en":"product name en"},"key":"productkey"}',
                ['productType'=> 'main','metaTitle.de' => 'de metaTitle', 'metaTitle.en' => 'en metaTitle','slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'], 'name' => ['de'=>'product name de', 'en' => 'product name en'], 'key' => "productkey", "details" => []]
            ],
            //metaKeywords
            [
                [

                ],
                '{"productType":{"typeId":"product-type","key":"main"},"slug":{"de":"product-slug-de","en":"product-slug-en"},"name":{"de":"product name de","en":"product name en"},"key":"productkey"}',
                ['productType'=> 'main','metaKeywords.de' => '','metaKeywords.en' => '', 'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'], 'name' => ['de'=>'product name de', 'en' => 'product name en'], 'key' => "productkey", "details" => []]
            ],
            [
                [

                ],
                '{"productType":{"typeId":"product-type","key":"main"},"metaKeywords":{"de":"de metaKeywords","en":"en metaKeywords"},"slug":{"de":"product-slug-de","en":"product-slug-en"},"name":{"de":"product name de","en":"product name en"},"key":"productkey"}',
                ['productType'=> 'main','metaKeywords.de' => 'de metaKeywords', 'metaKeywords.en' => 'en metaKeywords','slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'], 'name' => ['de'=>'product name de', 'en' => 'product name en'], 'key' => "productkey", "details" => []]
            ],
            //metaDescription
            [
                [

                ],
                '{"productType":{"typeId":"product-type","key":"main"},"slug":{"de":"product-slug-de","en":"product-slug-en"},"name":{"de":"product name de","en":"product name en"},"key":"productkey"}',
                ['productType'=> 'main','metaDescription' => '', 'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'], 'name' => ['de'=>'product name de', 'en' => 'product name en'], 'key' => "productkey", "details" => []]
            ],
            [
                [

                ],
                '{"productType":{"typeId":"product-type","key":"main"},"metaDescription":{"de":"de metaDescription","en":"en metaDescription"},"slug":{"de":"product-slug-de","en":"product-slug-en"},"name":{"de":"product name de","en":"product name en"},"key":"productkey"}',
                ['productType'=> 'main','metaDescription.de' => 'de metaDescription', 'metaDescription.en' => 'en metaDescription','slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'], 'name' => ['de'=>'product name de', 'en' => 'product name en'], 'key' => "productkey", "details" => []]
            ],
            // Master Variant
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",
                    'variants'=> [["sku"=>"1234","key"=>"productkey","variantId"=>'1']]
                ],
                '{
                    "productType":{"typeId":"product-type","key":"main"}, 
                    "slug":{"de":"product-slug-de","en":"product-slug-en"},
                    "key":"productkey","masterVariant":{"sku":"1234","variantId":"1","prices":[]},
                    "variants":[]
                 }',
            ],
            // variants
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",
                    'variants'=> [
                        ["sku"=>"1234","key"=>"productkey","variantId"=>'1'],
                        ["key"=>"productkey","variantId"=>'2'],
                        ["sku"=>"","key"=>"productkey","variantId"=>'3']
                    ]
                ],
                '{
                    "productType":{"typeId":"product-type","key":"main"},
                    "slug":{"de":"product-slug-de","en":"product-slug-en"},
                    "key":"productkey",
                    "masterVariant":{"sku":"1234","variantId":"1","prices":[]},
                    "variants":[
                                    {"variantId":"2","prices":[]},
                                    {"sku":"","variantId":"3","prices":[]}
                               ]
                 }',
            ],
            [
                [

                ],
                '{
                    "productType":{"typeId":"product-type","key":"main"},
                    "slug":{"de":"product-slug-de","en":"product-slug-en"},
                    "key":"productkey",
                    "masterVariant":{"sku":"1234","variantId":"1","prices":[]},
                    "variants":[
                                    {"variantId":"2","prices":[]},
                                    {"sku":"","variantId":"3","prices":[]}
                               ]
                 }',
                [
                    'productType'=> 'main',
                    'slug.de'=>'product-slug-de', 'slug.en' => 'product-slug-en',
                    'key' => "productkey",
                    'variants'=> [
                        ["sku"=>"1234","key"=>"productkey","variantId"=>'1'],
                        ["key"=>"productkey","variantId"=>'2'],
                        ["sku"=>"","key"=>"productkey","variantId"=>'3']
                    ]
                ]
            ],
            //Prices
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",
                    'variants'=> [["sku"=>"1234","variantId"=>'1',"prices"=>"EUR 9750;DE-EUR 7800|5460"]]
                ],
                '{
                    "productType":{"typeId":"product-type","key":"main"}, 
                    "slug":{"de":"product-slug-de","en":"product-slug-en"},
                    "key":"productkey",
                    "masterVariant":{
                                        "sku":"1234","variantId":"1",
                                        "prices":[
                                                    {
                                                        "value": {
                                                            "currencyCode":"EUR",
                                                            "centAmount":9750
                                                        }
                                                    },
                                                    {
                                                        "value": {
                                                            "currencyCode":"EUR",
                                                            "centAmount":7800
                                                        },
                                                        "country":"DE"
                                                    }
                                                 ]
                                    },
                    "variants":[]
                 }',
            ],
            [
                [

                ],
                '{
                    "productType":{"typeId":"product-type","key":"main"}, 
                    "slug":{"de":"product-slug-de","en":"product-slug-en"},
                    "key":"productkey",
                    "masterVariant":{
                                        "sku":"1234","variantId":"1",
                                        "prices":[
                                                    {
                                                        "value": {
                                                            "currencyCode":"EUR",
                                                            "centAmount":9750
                                                        }
                                                    },
                                                    {
                                                        "value": {
                                                            "currencyCode":"EUR",
                                                            "centAmount":7800
                                                        },
                                                        "country":"DE"
                                                    }
                                                 ]
                                    },
                    "variants":[]
                 }',
                [
                    'productType'=> 'main',
                    'slug.de'=>'product-slug-de', 'slug.en' => 'product-slug-en',
                    'key' => "productkey",
                    'variants'=> [["sku"=>"1234","variantId"=>'1',"prices"=>"EUR 9750;DE-EUR 7800|5460"]]
                ]
            ],
            [
                [

                ],
                '{
                    "productType":{"typeId":"product-type","key":"main"}, 
                    "slug":{"de":"product-slug-de","en":"product-slug-en"},
                    "key":"productkey",
                    "masterVariant":{
                                        "sku":"1234","variantId":"1",
                                        "prices":[]
                                    },
                    "variants":[]
                 }',
                [
                    'productType'=> 'main',
                    'slug.de'=>'product-slug-de', 'slug.en' => 'product-slug-en',
                    'key' => "productkey",
                    'variants'=> [["sku"=>"1234","variantId"=>'1',"prices"=>""]]
                ]
            ],

            //attributes
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",
                    'variants'=> [["sku"=>"1234","variantId"=>'1',"prices"=>"","size"=>"35","designer"=>"designerOne"]]
                ],
                '{
                    "productType":{"typeId":"product-type","key":"main"}, 
                    "slug":{"de":"product-slug-de","en":"product-slug-en"},
                    "key":"productkey",
                    "masterVariant":{
                                        "sku":"1234","variantId":"1",
                                        "prices":[],
                                        "attributes":[
                                                        {
                                                            "name":"size",
                                                            "value":"35"
                                                        },
                                                        {
                                                            "name":"designer",
                                                            "value":"designerOne"
                                                        }                                                                                                                
                                                     ]
                                    },
                    "variants":[]
                 }',
            ],
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",
                    'variants'=> [["sku"=>"1234","variantId"=>'1',"prices"=>"","size"=>"","designer"=>""]]
                ],
                '{
                    "productType":{"typeId":"product-type","key":"main"}, 
                    "slug":{"de":"product-slug-de","en":"product-slug-en"},
                    "key":"productkey",
                    "masterVariant":{
                                        "sku":"1234","variantId":"1",
                                        "prices":[],
                                        "attributes":[]
                                    },
                    "variants":[]
                 }',
            ],
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",
                    'variants'=> [
                        ["sku"=>"1234","key"=>"productkey","variantId"=>'1'],
                        ["key"=>"productkey","variantId"=>'2',"size"=>"","designer"=>""],
                        ["sku"=>"123","key"=>"productkey","variantId"=>'3',"size"=>"35","designer"=>"designerOne"]
                    ]
                ],
                '{
                    "productType":{"typeId":"product-type","key":"main"},
                    "slug":{"de":"product-slug-de","en":"product-slug-en"},
                    "key":"productkey",
                    "masterVariant":{"sku":"1234","variantId":"1","prices":[]},
                    "variants":[
                                    {"variantId":"2","prices":[],"attributes":[]},
                                    {
                                        "sku":"123","variantId":"3","prices":[],
                                        "attributes":[
                                                        {
                                                            "name":"size",
                                                            "value":"35"
                                                        },
                                                        {
                                                            "name":"designer",
                                                            "value":"designerOne"
                                                        } 
                                                     ]
                                    }
                               ]
                 }',
            ],
            //images
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",
                    'variants'=> [
                        ["sku"=>"1234","key"=>"productkey","variantId"=>'1',"images"=>"imageUrl",],
                        ["key"=>"productkey","variantId"=>'2',"size"=>"","designer"=>"","images"=>"imageUrl",],
                        ["sku"=>"123","key"=>"productkey","variantId"=>'3',"images"=>""]
                    ]
                ],
                '{
                    "productType":{"typeId":"product-type","key":"main"},
                    "slug":{"de":"product-slug-de","en":"product-slug-en"},
                    "key":"productkey",
                    "masterVariant":{
                                        "sku":"1234","variantId":"1","prices":[],
                                        "images":[
                                        {
                                            "url":"imageUrl",
                                            "dimensions":{"w":0,"h":0}
                                        }]
                                    },
                    "variants":[
                                    {
                                        "variantId":"2","prices":[],"attributes":[],
                                        "images":[
                                            {
                                                "url":"imageUrl",
                                                "dimensions":{"w":0,"h":0}
                                            }
                                        ]
                                    },
                                    {
                                        "sku":"123","variantId":"3","prices":[],"images":[]
                                    }
                               ]
                 }',
            ],
            [
                [

                ],
                '{
                    "productType":{"typeId":"product-type","key":"main"},
                    "slug":{"de":"product-slug-de","en":"product-slug-en"},
                    "key":"productkey",
                    "masterVariant":{
                                        "sku":"1234","variantId":"1","prices":[],
                                        "images":[
                                        {
                                            "url":"imageUrl",
                                            "dimensions":{"w":0,"h":0}
                                        }]
                                    },
                    "variants":[
                                    {
                                        "variantId":"2","prices":[],"attributes":[],
                                        "images":[
                                            {
                                                "url":"imageUrl",
                                                "dimensions":{"w":0,"h":0}
                                            }
                                        ]
                                    },
                                    {
                                        "sku":"123","variantId":"3","prices":[],"images":[]
                                    }
                               ]
                 }',
                [
                    'productType'=> 'main',
                    'slug.de'=>'product-slug-de', 'slug.en' => 'product-slug-en',
                    'key' => "productkey",
                    'variants'=> [
                        ["sku"=>"1234","key"=>"productkey","variantId"=>'1',"images"=>"imageUrl",],
                        ["key"=>"productkey","variantId"=>'2',"size"=>"","designer"=>"","images"=>"imageUrl",],
                        ["sku"=>"123","key"=>"productkey","variantId"=>'3',"images"=>""]
                    ]
                ]
            ],

        ];
    }
    //'{"productType":{"typeId":"product-type","key":"main"},"slug":{"de":"product-slug-de","en":"product-slug-en"},"name":{"de":"product name de","en":"product name en"},"key":"productkey"}'
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
                            "attributes":[
                                {
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
                                },
                                {
                                    "name":"designer",
                                    "type": {
                                        "name": "enum",
                                        "values": [
                                            {
                                                "key": "designerOne",
                                                "label": "designerOne"
                                            },
                                            {
                                                "key": "designerTwo",
                                                "label": "designerTwo"
                                            }
                                        ]
                                    }
                                }
                            ]
                        }]
                    }'
                ),
                $args[0]
            );

            return $response;
        });

        $client->execute(Argument::type(CustomerGroupQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{ "results": []}'), $args[0]);

            return $response;
        });

        $requestBuilder = new ProductsRequestBuilder($client->reveal());

        $returnedRequest = $requestBuilder->createRequest($data, "key");

        $this->assertInstanceOf(ProductCreateRequest::class, $returnedRequest);
        $this->assertJsonStringEqualsJsonString(
            $expected,
            (string)$returnedRequest->httpRequest()->getBody()
        );
    }

    public function getUpdateTestData()
    {
        return [
            //description
            [
                [

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

                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","description":{"de":"product name de","en" : "product name en"},"name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[
                    {"action":"setDescription",
                     "description":{"de":"neu desc"}
                    }
                    ],
                    "version" :""
                }',
                ['sku' => '1234', 'name.de' => 'product name de', 'name.en' => 'product name en', 'description.de' => 'neu desc', 'key' => 'productkey']
            ],
            [
                [

                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","description":{"de":"desc"},"name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[],
                    "version" :""
                }',
                ['sku' => '1234', 'name.de' => 'product name de', 'name.en' => 'product name en', 'description.de' => 'desc', 'key' => 'productkey']
            ],
            //metaTitle
            [
                [

                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[],
                    "version" :""
                }',
                ['sku' => '1234', 'name.de' => 'product name de', 'name.en' => 'product name en', 'metaTitle.de' => '', 'key' => 'productkey'],
            ],
            [
                [

                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","metaTitle":{"de":"product metaTitle de","en" : "product metaTitle en"},"name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[
                    {"action":"setMetaTitle"}
                    ],
                    "version" :""
                }',
                ['sku' => '1234', 'name.de' => 'product name de', 'name.en' => 'product name en', 'metaTitle.de' => '', 'key' => 'productkey'],
            ],
            [
                [

                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","metaTitle":{"de":"meta title","en" : "meta title"},"name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[
                    {"action":"setMetaTitle",
                     "metaTitle":{"de":"neu meta title"}
                    }
                    ],
                    "version" :""
                }',
                ['sku' => '1234', 'name.de' => 'product name de', 'name.en' => 'product name en', 'metaTitle.de' => 'neu meta title', 'key' => 'productkey']
            ],
            [
                [

                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","metaTitle":{"de":"meta title"},"name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[],
                    "version" :""
                }',
                ['sku' => '1234', 'name.de' => 'product name de', 'name.en' => 'product name en', 'metaTitle.de' => 'meta title', 'key' => 'productkey']
            ],
            //metaDescription
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
                ['sku' => '1234', 'name.de' => 'product name de', 'name.en' => 'product name en', 'metaDescription.de' => '', 'key' => 'productkey'],
            ],
            [
                [
                    "sku"=>"1234",
                    'name' => ['de'=>'product name de', 'en' => 'product name en'],
                    'metaTitle' => [],
                    'key' => "productkey",
                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","metaDescription":{"de":"product metaTitle de","en" : "product metaTitle en"},"name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[
                    {"action":"setMetaDescription"}
                    ],
                    "version" :""
                }',
                ['sku' => '1234', 'name.de' => 'product name de', 'name.en' => 'product name en', 'metaDescription.de' => '', 'key' => 'productkey'],
            ],
            [
                [
                    "sku"=>"1234",
                    'name' => ['de'=>'product name de', 'en' => 'product name en'],
                    'description' => [],
                    'key' => "productkey",
                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","metaDescription":{"de":"meta desc","en" : "meta title"},"name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[
                    {"action":"setMetaDescription",
                     "metaDescription":{"de":"neu meta desc"}
                    }
                    ],
                    "version" :""
                }',
                ['sku' => '1234', 'name.de' => 'product name de', 'name.en' => 'product name en', 'metaDescription.de' => 'neu meta desc', 'key' => 'productkey']
            ],
            [
                [
                    "sku"=>"1234",
                    'name' => ['de'=>'product name de', 'en' => 'product name en'],
                    'description' => [],
                    'key' => "productkey",
                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","metaDescription":{"de":"meta desc"},"name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[],
                    "version" :""
                }',
                ['sku' => '1234', 'name.de' => 'product name de', 'name.en' => 'product name en', 'metaDescription.de' => 'meta desc', 'key' => 'productkey']
            ],
            //metaKeywords
            [
                [

                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[],
                    "version" :""
                }',
                ['sku' => '1234', 'name.de' => 'product name de', 'name.en' => 'product name en', 'metaKeywords.de' => '', 'key' => 'productkey'],
            ],
            [
                [

                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","metaKeywords":{"de":"product metaKeywords de","en" : "product metaTitle en"},"name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[
                    {"action":"setMetaKeywords"}
                    ],
                    "version" :""
                }',
                ['sku' => '1234', 'name.de' => 'product name de', 'name.en' => 'product name en', 'metaKeywords.de' => '', 'key' => 'productkey'],
            ],
            [
                [

                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","metaKeywords":{"de":"metaKeywords","en" : "meta title"},"name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[
                    {"action":"setMetaKeywords",
                     "metaKeywords":{"de":"neu metaKeywords"}
                    }
                    ],
                    "version" :""
                }',
                ['sku' => '1234', 'name.de' => 'product name de', 'name.en' => 'product name en', 'metaKeywords.de' => 'neu metaKeywords', 'key' => 'productkey']
            ],
            [
                [

                ],
                '{"results": [{"version" :"","id" :"12345","sku":"1234","metaKeywords":{"de":"metaKeywords"},"name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {}, "variants": []}]}',
                '{"actions":[],
                    "version" :""
                }',
                ['sku' => '1234', 'name.de' => 'product name de', 'name.en' => 'product name en', 'metaKeywords.de' => 'metaKeywords', 'key' => 'productkey']
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
                    {"action":"addVariant","sku":"1234","variantId": 1}
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
                            "id":2,
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
                            "id":"2",
                            "sku":"1234",
                            "images":{},
                            "attributes":{},
                            "prices":[]
                        }
                    ]
                    }]
                 }',
                '{"actions":[{"action":"addVariant","variantId":3,"sku":"1244","images":[],"attributes":[]}],
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
                            "id":2,
                            "images":{},
                            "prices":[],
                            "attributes":{}
                        },
                        {
                            "sku":"1244",
                            "id":3,
                            "images":{},
                            "attributes":{},
                            "prices":[]
                        }
                    ]
                    }]
                 }',
                '{"actions":[{"action":"removeVariant","id":3}],
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
                            "id":2,
                            "sku":"1243",
                            "images":{},
                            "prices":[],
                            "attributes":{}
                        }
                    ]
                    }]
                 }',
                '{"actions":[{"action":"addPrice","variantId":"2","price":{"value":{"currencyCode":"EUR","centAmount":9750}}}],
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
                            "id":"2",
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
                            "id":"2",
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
                            "id":"2",
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
                            "id":2,
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
                        "variantId":"2"
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
                            "id":2,
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
                        "variantId":2,
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
                            "id":2,
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
                        "variantId":"2",
                        "imageUrl":"oldImageUrl"
                    },
                    {
                        "action":"addExternalImage",
                        "variantId":"2",
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
                            "id":2,
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
            ],
            //Sku
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",
                    'variants'=> [["sku"=>"1234","variantId"=>'1',"prices"=>""]]

                ],
                '{"results": [{
                                "productType":{"typeId":"product-type","key":"main","id":"1"},
                                "version" :"",
                                "id" :"12345",
                                "slug":{"de":"product-slug-de","en" : "product-slug-en"},
                                "key": "productkey","categories": {},
                                "masterVariant": {"id":1,"sku":"123","prices":[]}, "variants": []}
                             ]}'
                ,
                '{"actions":[
                    {"action":"setSku","sku":"1234","variantId": 1}
                    ],
                    "version" :""
                }'
            ],
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",
                    'variants'=> [["sku"=>"1234","variantId"=>'1',"prices"=>""],["sku"=>"12345","variantId"=>'2',"prices"=>""]]

                ],
                '{"results": [{
                                "productType":{"typeId":"product-type","key":"main","id":"1"},
                                "version" :"",
                                "id" :"12345",
                                "slug":{"de":"product-slug-de","en" : "product-slug-en"},
                                "key": "productkey","categories": {},
                                "masterVariant": {"id":1,"sku":"1234","prices":[]},
                                "variants": [
                                                {
                                                    "id":2,
                                                    "prices":[],
                                                    "sku":"1233",
                                                    "images":[],
                                                    "attributes":{}
                                                }
                                            ]}
                             ]}'
                ,
                '{"actions":[
                    {"action":"setSku","sku":"12345","variantId": 2}
                    ],
                    "version" :""
                }'
            ],
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",
                    'variants'=> [["sku"=>"1234","variantId"=>'1',"prices"=>""],["sku"=>"12345","variantId"=>'2',"prices"=>""]]

                ],
                '{"results": [{
                                "productType":{"typeId":"product-type","key":"main","id":"1"},
                                "version" :"",
                                "id" :"12345",
                                "slug":{"de":"product-slug-de","en" : "product-slug-en"},
                                "key": "productkey","categories": {},
                                "masterVariant": {"id":1,"sku":"1234","prices":[]},
                                "variants": [
                                                {
                                                    "id":2,
                                                    "prices":[],
                                                    "sku":"12345",
                                                    "images":[],
                                                    "attributes":{}
                                                }
                                            ]}
                             ]}'
                ,
                '{"actions":[],
                    "version" :""
                }'
            ],
            [
                [
                    'productType'=> 'main',
                    'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                    'key' => "productkey",
                    'variants'=> [["sku"=>"","variantId"=>'1',"prices"=>""],["sku"=>"","variantId"=>'2',"prices"=>""]]

                ],
                '{"results": [{
                                "productType":{"typeId":"product-type","key":"main","id":"1"},
                                "version" :"",
                                "id" :"12345",
                                "slug":{"de":"product-slug-de","en" : "product-slug-en"},
                                "key": "productkey","categories": {},
                                "masterVariant": {"id":1,"sku":"1234","prices":[]},
                                "variants": [
                                                {
                                                    "id":2,
                                                    "prices":[],
                                                    "sku":"12345",
                                                    "images":[],
                                                    "attributes":{}
                                                }
                                            ]}
                             ]}'
                ,
                '{"actions":[{"action":"setSku","variantId": 1}],
                    "version" :""
                }'
            ],

        ];
    }

    /**
     * @dataProvider getUpdateTestData
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
//        var_dump((string)$returnedRequest->httpRequest()->getBody());
        $this->assertJsonStringEqualsJsonString($expected, (string)$returnedRequest->httpRequest()->getBody());
    }

}
