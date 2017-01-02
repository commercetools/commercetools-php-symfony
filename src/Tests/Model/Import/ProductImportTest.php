<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 29/12/16
 * Time: 11:58
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Model\Import;

use Commercetools\Core\Client;
use Commercetools\Core\Config;
use Commercetools\Core\Request\Products\ProductCreateRequest;
use Commercetools\Core\Request\Products\ProductUpdateRequest;
use Commercetools\Core\Response\PagedQueryResponse;
use Commercetools\Core\Request\Categories\CategoryQueryRequest;
use Commercetools\Core\Request\CustomerGroups\CustomerGroupQueryRequest;
use Commercetools\Core\Request\Products\ProductProjectionQueryRequest;
use Commercetools\Core\Request\ProductTypes\ProductTypeQueryRequest;
use Commercetools\Core\Request\TaxCategories\TaxCategoryQueryRequest;
use Commercetools\Symfony\CtpBundle\Model\Import\ProductsImport;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

class ProductImportTest extends \PHPUnit_Framework_TestCase
{
    public function testImportCreate()
    {
        $client = $this->prophesize(Client::class);

        $config = new Config();
        $client->getConfig()->willReturn($config);

        $client->execute(Argument::type(ProductProjectionQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{}'), $args[0]);

            return $response;
        })->shouldBeCalledTimes(1);
        $client->execute(Argument::type(CategoryQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{ "results": []}'), $args[0]);

            return $response;
        })->shouldBeCalledTimes(1);
        $client->execute(Argument::type(TaxCategoryQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{ "results": []}'), $args[0]);

            return $response;
        })->shouldBeCalledTimes(1);
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
        })->shouldBeCalledTimes(1);
        $client->execute(Argument::type(CustomerGroupQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{ "results": []}'), $args[0]);

            return $response;
        })->shouldBeCalledTimes(1);

        $client->addBatchRequest(Argument::type(ProductCreateRequest::class))->shouldBeCalled(2);
        $client->executeBatch()->shouldBeCalledTimes(1);

        $importer = new ProductsImport($client->reveal());
        $importer->setOptions('key');
        $importer->import([
            [
                'variantId'=>'1','productType'=> 'main',
                'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                'name' => ['de'=>'product name de', 'en' => 'product name en'],
                'key' => "productkey", "details" => []
            ]
        ]);
    }
    public function testImportUpdate()
    {
        $client = $this->prophesize(Client::class);

        $config = new Config();
        $client->getConfig()->willReturn($config);

        $client->execute(Argument::type(ProductProjectionQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{"results": [{"version" :"","id" :"1", "productType":{"id":"1","key":"main"},"name":{"de":"product name de","en" : "product name en"}, "key": "productkey","categories": {}, "masterVariant": {"id" :"1"}, "variants": []}]}'), $args[0]);

            return $response;
        })->shouldBeCalledTimes(1);
        $client->execute(Argument::type(CategoryQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{ "results": []}'), $args[0]);

            return $response;
        })->shouldBeCalledTimes(1);
        $client->execute(Argument::type(TaxCategoryQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{ "results": []}'), $args[0]);

            return $response;
        })->shouldBeCalledTimes(1);
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
        })->shouldBeCalledTimes(1);
        $client->execute(Argument::type(CustomerGroupQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{ "results": []}'), $args[0]);

            return $response;
        })->shouldBeCalledTimes(1);

        $client->addBatchRequest(Argument::type(ProductUpdateRequest::class))->shouldBeCalled(1);
        $client->executeBatch()->shouldBeCalledTimes(1);

        $importer = new ProductsImport($client->reveal());
        $importer->setOptions('key');
        $importer->import([
            [
                'variantId'=>'1','productType'=> 'main',
                'slug' => ['de'=>'product-slug-de', 'en' => 'product-slug-en'],
                'name' => ['de'=>'product name de', 'en' => 'product name en'],
                'key' => "productkey", "details" => []
            ]
        ]);
    }
}
