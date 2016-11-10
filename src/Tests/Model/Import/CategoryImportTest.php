<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 07/11/16
 * Time: 17:49
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Model\Import;


use Commercetools\Core\Request\Categories\CategoryCreateRequest;
use Commercetools\Core\Request\Categories\CategoryQueryRequest;
use Commercetools\Core\Request\Categories\CategoryUpdateRequest;
use Commercetools\Symfony\CtpBundle\Logger\Logger;
use GuzzleHttp\Psr7;
use Commercetools\Core\Client;
use Commercetools\Core\Response\PagedQueryResponse;
use Commercetools\Symfony\CtpBundle\Model\Import\CategoryImport;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

class CategoryImportTest extends \PHPUnit_Framework_TestCase
{
    public function testImportCreate()
    {
        $client = $this->prophesize(Client::class);

        $client->execute(Argument::type(CategoryQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{}'), $args[0]);

            return $response;
        })->shouldBeCalledTimes(1);
        $client->addBatchRequest(Argument::type(CategoryCreateRequest::class))->shouldBeCalled(2);

        $client->executeBatch()->shouldBeCalledTimes(1);


        $importer = new CategoryImport($client->reveal());
        $importer->setOptions('externalId');
        $importer->import([
            ['externalId' => '1', 'name' => ['en' => 'test'], 'slug' => ['en' => 'test'], 'parentId' => null]
        ]);
    }

    public function testImportUpdate()
    {
        $client = $this->prophesize(Client::class);

        $client->execute(Argument::type(CategoryQueryRequest::class))->will(function ($args) {
            $response = new PagedQueryResponse(new Response(200, [], '{"results": [{"externalId": "1", "name": {"en": "Test"}, "slug": {"en": "Test"}}]}'), $args[0]);

            return $response;
        })->shouldBeCalledTimes(1);


        $client->addBatchRequest(Argument::type(CategoryUpdateRequest::class))->shouldBeCalled(1);

        $client->executeBatch()->shouldBeCalledTimes(1);


        $importer = new CategoryImport($client->reveal());
        $importer->setOptions('externalId');
        $importer->import([
            ['externalId' => '1', 'name' => ['en' => 'new name'], 'slug' => ['en' => 'test'], 'parentId' => '']
        ]);
    }
}
