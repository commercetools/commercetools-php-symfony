<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 07/02/17
 * Time: 15:58
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Model\Import;

use Commercetools\Core\Request\States\StateCreateRequest;
use Commercetools\Core\Request\States\StateQueryRequest;
use Commercetools\Core\Request\States\StateUpdateRequest;
use Commercetools\Symfony\CtpBundle\Model\Import\CsvToJson;
use Commercetools\Core\Client;
use Commercetools\Core\Config;
use Commercetools\Core\Response\PagedQueryResponse;
use Commercetools\Symfony\CtpBundle\Model\Import\StatesRequestBuilder;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

class StateRequestBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function getAddTestData()
    {
        return [
            [
                [],
                '{
                    "id":"1",
                    "key":"key1",
                    "type":"type1"
                 }',
                [
                    'id'=>'1',
                    'key'=>'key1',
                    'type'=>'type1'
                ]
            ],
            [
                [],
                '{
                    "id":"1",
                    "key":"key1",
                    "type":"type1"
                 }',
                [
                    'id'=>'1',
                    'key'=>'key1',
                    'type'=>'type1',
                    'name.en'=>'',
                    'name.de'=>'',
                    'description.en'=>'',
                    'description.de'=>'',
                    'initial'=>'',
                ]
            ],
            [
                [],
                '{
                    "id":"1",
                    "key":"key1",
                    "type":"type1",
                    "name":{
                        "en":"name en",
                        "de":"name de"
                    },
                    "description":{
                        "en":"desc en",
                        "de":"desc de"
                    },
                    "initial":true
                 }',
                [
                    'id'=>'1',
                    'key'=>'key1',
                    'type'=>'type1',
                    'name.en'=>'name en',
                    'name.de'=>'name de',
                    'description.en'=>'desc en',
                    'description.de'=>'desc de',
                    'initial'=>'1',
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
        $client->execute(Argument::type(StateQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{}'), $args[0]);

            return $response;
        });

        $requestBuilder = new StatesRequestBuilder($client->reveal());

        $returnedRequests = $requestBuilder->createRequest([$data], "id");
        $returnedRequest = current($returnedRequests);
        $this->assertInstanceOf(StateCreateRequest::class, $returnedRequest);
        $this->assertJsonStringEqualsJsonString(
            $expected,
            (string)$returnedRequest->httpRequest()->getBody()
        );
    }

    public function getUpdateTestData()
    {
        return [
            [
                [],
                '{"results": [{
                    "version" :"1",
                    "id" :"12345",
                    "key" :"key",
                    "type" :"LineItemState"
                }]}',
                '{"actions":[{"action":"changeKey","key":"key1"},{"action":"changeType","type":"type1"}],
                    "version" :"1"
                }',
                [
                    'id'=>'12345',
                    'key'=>'key1',
                    'type'=>'type1'
                ]
            ],
            [
                [],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "key" :"key",
                    "type" :"LineItemState"
                }]}',
                '{
                    "actions":[],
                    "version":""
                }',
                [
                    'id'=>'12345',
                    'key'=>'key',
                    'type'=>'LineItemState'
                ]
            ],
            //name
            [
                [],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "key" :"key",
                    "type" :"LineItemState"
                }]}',
                '{
                    "actions":[{"action":"setName","name":{"en":"name en","de":"name de"}}],
                    "version":""
                }',
                [
                    'id'=>'12345',
                    'key'=>'key',
                    'type'=>'LineItemState',
                    'name.en'=>'name en',
                    'name.de'=>'name de'
                ]
            ],
            [
                [],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "key" :"key",
                    "type" :"LineItemState",
                    "name":{
                        "en":"name en",
                        "de":"name de"
                    }
                }]}',
                '{
                    "actions":[{"action":"setName"}],
                    "version":""
                }',
                [
                    'id'=>'12345',
                    'key'=>'key',
                    'type'=>'LineItemState'
                ]
            ],
            [
                [],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "key" :"key",
                    "type" :"LineItemState",
                    "name":{
                        "en":"name en",
                        "de":"name de"
                    }
                }]}',
                '{
                    "actions":[],
                    "version":""
                }',
                [
                    'id'=>'12345',
                    'key'=>'key',
                    'type'=>'LineItemState',
                    'name.en'=>'name en',
                    'name.de'=>'name de'
                ]
            ],
            [
                [],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "key" :"key",
                    "type" :"LineItemState",
                    "name":{}
                }]}',
                '{
                    "actions":[],
                    "version":""
                }',
                [
                    'id'=>'12345',
                    'key'=>'key',
                    'type'=>'LineItemState',
                    'name.en'=>'',
                    'name.de'=>''
                ]
            ],
            [
                [],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "key" :"key",
                    "type" :"LineItemState",
                    "name":{
                        "en":"name",
                        "de":"name"
                    }
                }]}',
                '{
                    "actions":[{"action":"setName","name":{"en":"name en","de":"name de"}}],
                    "version":""
                }',
                [
                    'id'=>'12345',
                    'key'=>'key',
                    'type'=>'LineItemState',
                    'name.en'=>'name en',
                    'name.de'=>'name de'
                ]
            ],
            //description
            [
                [],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "key" :"key",
                    "type" :"LineItemState"
                }]}',
                '{
                    "actions":[{"action":"setDescription","description":{"en":"description en","de":"description de"}}],
                    "version":""
                }',
                [
                    'id'=>'12345',
                    'key'=>'key',
                    'type'=>'LineItemState',
                    'description.en'=>'description en',
                    'description.de'=>'description de'
                ]
            ],
            [
                [],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "key" :"key",
                    "type" :"LineItemState",
                    "description":{
                        "en":"description en",
                        "de":"description de"
                    }
                }]}',
                '{
                    "actions":[{"action":"setDescription"}],
                    "version":""
                }',
                [
                    'id'=>'12345',
                    'key'=>'key',
                    'type'=>'LineItemState'
                ]
            ],
            [
                [],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "key" :"key",
                    "type" :"LineItemState",
                    "description":{
                        "en":"description en",
                        "de":"description de"
                    }
                }]}',
                '{
                    "actions":[],
                    "version":""
                }',
                [
                    'id'=>'12345',
                    'key'=>'key',
                    'type'=>'LineItemState',
                    'description.en'=>'description en',
                    'description.de'=>'description de'
                ]
            ],
            [
                [],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "key" :"key",
                    "type" :"LineItemState",
                    "description":{}
                }]}',
                '{
                    "actions":[],
                    "version":""
                }',
                [
                    'id'=>'12345',
                    'key'=>'key',
                    'type'=>'LineItemState',
                    'description.en'=>'',
                    'description.de'=>''
                ]
            ],
            [
                [],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "key" :"key",
                    "type" :"LineItemState",
                    "description":{
                        "en":"description",
                        "de":"description"
                    }
                }]}',
                '{
                    "actions":[{"action":"setDescription","description":{"en":"description en","de":"description de"}}],
                    "version":""
                }',
                [
                    'id'=>'12345',
                    'key'=>'key',
                    'type'=>'LineItemState',
                    'description.en'=>'description en',
                    'description.de'=>'description de'
                ]
            ],
            //initial
            [
                [],
                '{"results": [{
                    "version" :"1",
                    "id" :"12345",
                    "key" :"key",
                    "type" :"LineItemState"
                }]}',
                '{"actions":[{"action":"changeInitial","initial":true}],
                    "version" :"1"
                }',
                [
                    'id'=>'12345',
                    'key'=>'key',
                    'type'=>'LineItemState',
                    'initial'=>'1'
                ]
            ],
            [
                [],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "key" :"key",
                    "type" :"LineItemState",
                    "initial":true
                }]}',
                '{
                    "actions":[],
                    "version":""
                }',
                [
                    'id'=>'12345',
                    'key'=>'key',
                    'type'=>'LineItemState',
                    'initial'=>'1'
                ]
            ],
            [
                [],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "key" :"key",
                    "type" :"LineItemState",
                    "initial":false
                }]}',
                '{
                    "actions":[{"action":"changeInitial","initial":true}],
                    "version":""
                }',
                [
                    'id'=>'12345',
                    'key'=>'key',
                    'type'=>'LineItemState',
                    'initial'=>'1'
                ]
            ],
            [
                [],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "key" :"key",
                    "type" :"LineItemState",
                    "initial":false
                }]}',
                '{
                    "actions":[{"action":"changeInitial","initial":true}],
                    "version":""
                }',
                [
                    'id'=>'12345',
                    'key'=>'key',
                    'type'=>'LineItemState',
                    'initial'=>''
                ]
            ],
            [
                [],
                '{"results": [{
                    "version" :"",
                    "id" :"12345",
                    "key" :"key",
                    "type" :"LineItemState",
                    "initial":true
                }]}',
                '{
                    "actions":[],
                    "version":""
                }',
                [
                    'id'=>'12345',
                    'key'=>'key',
                    'type'=>'LineItemState',
                    'initial'=>''
                ]
            ]
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
        $client->execute(Argument::type(StateQueryRequest::class))->will(function ($args) use ($response) {
            $response = new PagedQueryResponse(new Response(200, [], $response), $args[0]);

            return $response;
        });

        $requestBuilder = new StatesRequestBuilder($client->reveal());

        if (isset($data["id"])) {
            $returnedRequests = $requestBuilder->createRequest([$data], "id");
            $returnedRequest = current($returnedRequests);
        } else {
            $returnedRequests = $requestBuilder->createRequest([$data], "key");
            $returnedRequest = current($returnedRequests);
        }

        if (is_null($returnedRequest)) {
            $this->assertJsonStringEqualsJsonString('{"actions":[], "version" :""}', $expected);
        } else {
            $this->assertInstanceOf(StateUpdateRequest::class, $returnedRequest);
            $this->assertJsonStringEqualsJsonString($expected, (string)$returnedRequest->httpRequest()->getBody());
        }
    }
}
