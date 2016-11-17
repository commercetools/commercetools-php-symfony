<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 10/11/16
 * Time: 11:21
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Model\Import;

use Commercetools\Core\Client;
use Commercetools\Core\Request\Categories\CategoryUpdateRequest;
use Commercetools\Symfony\CtpBundle\Model\Import\CategoryRequestBuilder;
use Commercetools\Core\Request\Categories\CategoryCreateRequest;
use Commercetools\Core\Request\Categories\CategoryQueryRequest;
use Commercetools\Core\Response\PagedQueryResponse;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

class CategoryRequestBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateRequest()
    {
        $client = $this->prophesize(Client::class);

        $client->execute(Argument::type(CategoryQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{}'), $args[0]);

            return $response;
        });

        $requestBuilder = new CategoryRequestBuilder($client->reveal());

        $data = ['externalId' => '1', 'name' => ['en' => 'test'], 'slug' => ['en' => 'test'], 'parentId' => ''];

        $returnedRequest= $requestBuilder->createRequest($data, "externalId", 1);

        $this->assertInstanceOf(CategoryCreateRequest::class, $returnedRequest);
        $this->assertEquals(
            '{"externalId":"1","name":{"en":"test"},"slug":{"en":"test"},"parentId":""}',
            (string)$returnedRequest->httpRequest()->getBody()
        );
    }

    public function getTestData()
    {
        return [
            [
                [
                    'id' => "12345",
                    'externalId' => '1',
                    'name' => ['en' => 'new name'],
                    'slug' => ['en' => 'new-slug'],
                    'parentId' => ''
                ],
                '{"results": [{"externalId": "1", "name": {"en": "Test"}, "slug": {"en": "Test"}}]}',
                '{"version":null,"actions":[
                    {"action":"changeName","name":{"en":"new name"}},
                    {"action":"changeSlug","slug":{"en":"new-slug"}}
                ]}'
            ],
            //change description test cases
            [
                ['id' => "12345", 'description' => ['en' => 'Test']],
                '{"results": [{"id": "12345"}]}',
                '{"version":null,"actions":[{"action":"setDescription","description":{"en":"Test"}}]}'
            ],
            [
                ['id' => "12345", 'description' => ['en' => 'new description']],
                '{"results": [{"id": "12345", "description": {"en": "Test"}}]}',
                '{"version":null,"actions":[{"action":"setDescription","description":{"en":"new description"}}]}'
            ],
            [
                ['id' => "12345", 'description' => ['en' => 'Test']],
                '{"results": [{"id": "12345", "description": {"en": "Test"}}]}',
                '{"version":null,"actions":[]}'
            ],
            //change name test cases
            [
                ['id' => "12345", 'name' => ['en' => 'Test']],
                '{"results": [{"id": "12345"}]}',
                '{"version":null,"actions":[{"action":"changeName","name":{"en":"Test"}}]}'
            ],
            [
                ['id' => "12345", 'name' => ['en' => 'new name']],
                '{"results": [{"id": "12345", "name": {"en": "Test"}}]}',
                '{"version":null,"actions":[{"action":"changeName","name":{"en":"new name"}}]}'
            ],
            [
                ['id' => "12345", 'name' => ['en' => 'Test']],
                '{"results": [{"id": "12345", "name": {"en": "Test"}}]}',
                '{"version":null,"actions":[]}'
            ],
            //change Slug test cases
            [
                ['id' => "12345", 'slug' => ['en' => 'Test']],
                '{"results": [{"id": "12345"}]}',
                '{"version":null,"actions":[{"action":"changeSlug","slug":{"en":"Test"}}]}'
            ],
            [
                ['id' => "12345", 'slug' => ['en' => 'new-slug']],
                '{"results": [{"id": "12345", "slug": {"en": "Test"}}]}',
                '{"version":null,"actions":[{"action":"changeSlug","slug":{"en":"new-slug"}}]}'
            ],
            [
                ['id' => "12345", 'slug' => ['en' => 'Test']],
                '{"results": [{"id": "12345", "slug": {"en": "Test"}}]}',
                '{"version":null,"actions":[]}'
            ],
            //change externalId test cases
            [
                ['id' => "12345", 'externalId' => "1"],
                '{"results": [{"id": "12345"}]}',
                '{"version":null,"actions":[{"action":"setExternalId","externalId":"1"}]}'
            ],
            [
                ['id' => "12345", 'externalId' => "1"],
                '{"results": [{"id": "12345", "externalId": "2"}]}',
                '{"version":null,"actions":[{"action":"setExternalId","externalId":"1"}]}'
            ],
            [
                ['id' => "12345", 'externalId' => "1"],
                '{"results": [{"id": "12345", "externalId": "1"}]}',
                '{"version":null,"actions":[]}'
            ],
            //change orderHint test cases
            [
                ['id' => "12345", 'orderHint' => "0.1"],
                '{"results": [{"id": "12345"}]}',
                '{"version":null,"actions":[{"action":"changeOrderHint","orderHint":"0.1"}]}'
            ],
            [
                ['id' => "12345", 'orderHint' => "0.2"],
                '{"results": [{"id": "12345", "orderHint": "0.1"}]}',
                '{"version":null,"actions":[{"action":"changeOrderHint","orderHint":"0.2"}]}'
            ],
            [
                ['id' => "12345", 'orderHint' => "0.2"],
                '{"results": [{"id": "12345", "orderHint": "0.2"}]}',
                '{"version":null,"actions":[]}'
            ],
            //set Meta Title test cases
            [
                ['id' => "12345", 'metaTitle' => ['en' => 'Test']],
                '{"results": [{"id": "12345"}]}',
                '{"version":null,"actions":[{"action":"setMetaTitle","metaTitle":{"en": "Test"}}]}'
            ],
            [
                ['id' => "12345", 'metaTitle' => ['en'=>'new']],
                '{"results": [{"id": "12345", "metaTitle":{"en":"old"}}]}',
                '{"version":null,"actions":[{"action":"setMetaTitle","metaTitle":{"en":"new"}}]}'
            ],
            [
                ['id' => "12345", 'metaTitle' => ['en' => 'new']],
                '{"results": [{"id": "12345", "metaTitle":{"en": "new"}}]}',
                '{"version":null,"actions":[]}'
            ],
            //set Meta Description test cases
            [
                ['id' => "12345", 'metaDescription' => ['en' => 'Test']],
                '{"results": [{"id": "12345"}]}',
                '{"version":null,"actions":[{"action":"setMetaDescription","metaDescription":{"en": "Test"}}]}'
            ],
            [
                ['id' => "12345", 'metaDescription' => ['en'=>'new']],
                '{"results": [{"id": "12345", "metaDescription":{"en":"old"}}]}',
                '{"version":null,"actions":[{"action":"setMetaDescription","metaDescription":{"en":"new"}}]}'
            ],
            [
                ['id' => "12345", 'metaDescription' => ['en' => 'new']],
                '{"results": [{"id": "12345", "metaDescription":{"en": "new"}}]}',
                '{"version":null,"actions":[]}'
            ],
            //set Meta Keywords test cases
            [
                ['id' => "12345", 'metaKeywords' => ['en' => 'Test']],
                '{"results": [{"id": "12345"}]}',
                '{"version":null,"actions":[{"action":"setMetaKeywords","metaKeywords":{"en": "Test"}}]}'
            ],
            [
                ['id' => "12345", 'metaKeywords' => ['en'=>'new']],
                '{"results": [{"id": "12345", "metaKeywords":{"en":"old"}}]}',
                '{"version":null,"actions":[{"action":"setMetaKeywords","metaKeywords":{"en":"new"}}]}'
            ],
            [
                ['id' => "12345", 'metaKeywords' => ['en' => 'new']],
                '{"results": [{"id": "12345", "metaKeywords":{"en": "new"}}]}',
                '{"version":null,"actions":[]}'
            ],
            //set Custom Field test cases
            [
                ['id' => "12345",
                 'custom' =>
                    [
                        'type' => [ "key"=> "my-category" ],
                        'fields' => [ "description"=> "my description"]
                    ]
                ],
                '{"results": [{
                        "id": "12345",
                        "custom" : {
                            "type" : [{ "key" : "my-category" }]
                        }
                    }]
                 }',
                '{"version":null,
                  "actions":
                  [{
                    "action":"setCustomType",
                    "type" : {
                        "typeId": "type",
                        "key": "my-category"
                    }
                  },{
                    "action":"setCustomField",
                    "name" : "description",
                    "value": "my description"
                  }]
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

        $client->execute(Argument::type(CategoryQueryRequest::class))->will(function ($args) use ($response) {
            $response = new PagedQueryResponse(new Response(200, [], $response), $args[0]);

            return $response;
        });

        $requestBuilder = new CategoryRequestBuilder($client->reveal());

        $returnedRequest= $requestBuilder->createRequest($data, "id");

        $this->assertInstanceOf(CategoryUpdateRequest::class, $returnedRequest);
        $this->assertJsonStringEqualsJsonString($expected, (string)$returnedRequest->httpRequest()->getBody());
    }
}
