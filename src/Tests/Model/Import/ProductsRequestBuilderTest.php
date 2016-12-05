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
use Commercetools\Core\Request\Products\ProductProjectionQueryRequest;
use Commercetools\Core\Request\ProductTypes\ProductTypeQueryRequest;
use Commercetools\Core\Request\TaxCategories\TaxCategoryQueryRequest;
use Commercetools\Core\Response\PagedQueryResponse;
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

        $requestBuilder = new ProductsRequestBuilder($client->reveal());

        $data = ['productType'=> 'main','slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'], 'name' => ['de'=>'product name de', 'en' => 'product name en'], 'key' => "productkey"];

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
                            "sku":"1234",
                            "images":{},
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
                            "attributes":{}
                        }
                    ]
                    }]
                 }',
                '{"actions":[{"action":"addVariant","sku":"1244","images":[[]],"attributes":[]}],
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
                            "attributes":{}
                        },
                        {
                            "sku":"1244",
                            "images":{},
                            "attributes":{}
                        }
                    ]
                    }]
                 }',
                '{"actions":[{"action":"removeVariant","sku":"1244"}],
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
            ]
        ];
    }

    /**
     * @dataProvider getTestData
     */
    public function testUpdateRequest($data, $response, $expected)
    {
        $client = $this->prophesize(Client::class);

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
        $client->execute(Argument::type(ProductTypeQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{ "results": [{"id":"1","name":"product","key":"main","description":"product desc"}]}'), $args[0]);

            return $response;
        });

        $requestBuilder = new ProductsRequestBuilder($client->reveal());

        $returnedRequest= $requestBuilder->createRequest($data, "key");

        $this->assertInstanceOf(ProductUpdateRequest::class, $returnedRequest);
//        var_dump((string)$returnedRequest->httpRequest()->getBody());

        $this->assertJsonStringEqualsJsonString($expected, (string)$returnedRequest->httpRequest()->getBody());
    }

}
