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
                            0=>['id'=>'2',
                            'productId'=>'3',
                            'name'=>['en'=>'nameEN'],
                            'variant'=>['variantId'=>'123','sku'=>'sku'],
                            'quantity'=>'3',
                            'price'=>'EUR 2500']
                        ]
                ]
            ],
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
