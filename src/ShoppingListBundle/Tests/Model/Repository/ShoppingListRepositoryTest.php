<?php
/**
 *
 */

namespace Commercetools\Symfony\ShoppingListBundle\Tests\Model\Repository;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Model\ShoppingList\ShoppingListDraft;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListChangeNameAction;
use Commercetools\Core\Request\ShoppingLists\ShoppingListByIdGetRequest;
use Commercetools\Core\Request\ShoppingLists\ShoppingListCreateRequest;
use Commercetools\Core\Request\ShoppingLists\ShoppingListDeleteRequest;
use Commercetools\Core\Request\ShoppingLists\ShoppingListQueryRequest;
use Commercetools\Core\Request\ShoppingLists\ShoppingListUpdateRequest;
use Commercetools\Core\Response\ResourceResponse;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Commercetools\Symfony\ShoppingListBundle\Model\Repository\ShoppingListRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Cache\Tests\Fixtures\ExternalAdapter;

class ShoppingListRepositoryTest extends TestCase
{
    private $cache;
    private $mapperFactory;
    private $response;
    private $client;

    protected function setUp()
    {
        $this->cache = new ExternalAdapter();
        $this->mapperFactory = $this->prophesize(MapperFactory::class);

        $this->response = $this->prophesize(ResourceResponse::class);
        $this->response->toArray()->willReturn([]);
        $this->response->getContext()->willReturn(null);
        $this->response->isError()->willReturn(false);

        $this->client = $this->prophesize(Client::class);
    }

    private function getShoppingListRepository()
    {
        return new ShoppingListRepository(
            false,
            $this->cache,
            $this->client->reveal(),
            $this->mapperFactory->reveal()
        );
    }

    public function testGetShoppingListById()
    {
        $this->client->execute(
            Argument::that(function (ShoppingListByIdGetRequest $request) {
                static::assertSame('list-1', $request->getId());
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getShoppingListRepository();
        $repository->getShoppingListById('en', 'list-1');
    }

    public function testGetShoppingListForAnonymous()
    {
        $this->client->execute(
            Argument::that(function (ShoppingListQueryRequest $request) {
                static::assertContains('id+%3D+%22list-1%22', (string)$request->httpRequest()->getUri());
                static::assertContains('anonymousId+%3D+%22anon-1%22', (string)$request->httpRequest()->getUri());
                static::assertNotContains('customer', (string)$request->httpRequest()->getUri());
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getShoppingListRepository();
        $repository->getShoppingList('en', 'list-1', null, 'anon-1');
    }

    public function testGetShoppingListForCustomer()
    {
        $this->client->execute(
            Argument::that(function (ShoppingListQueryRequest $request) {
                static::assertContains('id+%3D+%22list-1%22', (string)$request->httpRequest()->getUri());
                static::assertContains('customer%28id+%3D+%22user-1%22%29', (string)$request->httpRequest()->getUri());
                static::assertNotContains('anonymous', (string)$request->httpRequest()->getUri());
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getShoppingListRepository();
        $customer = CustomerReference::ofId('user-1');
        $repository->getShoppingList('en', 'list-1', $customer);
    }

    public function testGetShoppingList()
    {
        $this->client->execute(
            Argument::that(function (ShoppingListQueryRequest $request) {
                static::assertContains('id+%3D+%22list-1%22', (string)$request->httpRequest()->getUri());
                static::assertNotContains('customer', (string)$request->httpRequest()->getUri());
                static::assertNotContains('anonymous', (string)$request->httpRequest()->getUri());
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getShoppingListRepository();
        $repository->getShoppingList('en', 'list-1');
    }

    public function testGetAllShoppingListsByCustomer()
    {
        $this->client->execute(
            Argument::that(function (ShoppingListQueryRequest $request) {
                static::assertContains('customer%28id+%3D+%22user-1%22%29', (string)$request->httpRequest()->getUri());
                static::assertNotContains('anonymous', (string)$request->httpRequest()->getUri());
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getShoppingListRepository();
        $customer = CustomerReference::ofId('user-1');
        $repository->getAllShoppingListsByCustomer('en', $customer);
    }

    public function testGetAllShoppingListsByAnonymous()
    {
        $this->client->execute(
            Argument::that(function (ShoppingListQueryRequest $request) {
                static::assertContains('anonymousId+%3D+%22anon-1%22', (string)$request->httpRequest()->getUri());
                static::assertNotContains('customer', (string)$request->httpRequest()->getUri());
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getShoppingListRepository();
        $repository->getAllShoppingListsByAnonymousId('en', 'anon-1');
    }

    public function testCreateShoppingListForAnonymous()
    {
        $this->client->execute(
            Argument::that(function (ShoppingListCreateRequest $request) {
                static::assertInstanceOf(ShoppingListDraft::class, $request->getObject());
                static::assertInstanceOf(LocalizedString::class, $request->getObject()->getName());
                static::assertSame('anon-1', $request->getObject()->getAnonymousId());
                static::assertNull($request->getObject()->getCustomer());
                static::assertNotNull($request->getObject()->getKey());
                static::assertSame(['en' => 'list-name-1'], $request->getObject()->getName()->toArray());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getShoppingListRepository();
        $repository->create('en', 'list-name-1', null, 'anon-1');
    }

    public function testCreateShoppingListForCustomer()
    {
        $this->client->execute(
            Argument::that(function (ShoppingListCreateRequest $request) {
                static::assertInstanceOf(ShoppingListDraft::class, $request->getObject());
                static::assertInstanceOf(LocalizedString::class, $request->getObject()->getName());
                static::assertInstanceOf(CustomerReference::class, $request->getObject()->getCustomer());
                static::assertSame('user-1', $request->getObject()->getCustomer()->getId());
                static::assertNull($request->getObject()->getAnonymousId());
                static::assertNotNull($request->getObject()->getKey());
                static::assertSame(['en' => 'list-name-1'], $request->getObject()->getName()->toArray());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getShoppingListRepository();
        $customer = CustomerReference::ofId('user-1');
        $repository->create('en', 'list-name-1', $customer);
    }

    public function testUpdate()
    {
        $this->client->execute(
            Argument::that(function (ShoppingListUpdateRequest $request) {
                $action = current($request->getActions());

                static::assertSame(ShoppingList::class, $request->getResultClass());
                static::assertInstanceOf(ShoppingListChangeNameAction::class, $action);
                static::assertInstanceOf(LocalizedString::class, $action->getName());
                static::assertSame(['en' => 'foobar'], $action->getName()->toArray());
                static::assertSame('list-1', $request->getId());
                static::assertSame(1, $request->getVersion());
                static::assertSame(
                    'shopping-lists/list-1?foo=bar',
                    (string)$request->httpRequest()->getUri()
                );

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $params = new QueryParams();
        $params->add('foo', 'bar');

        $repository = $this->getShoppingListRepository();
        $repository->update(ShoppingList::of()->setId('list-1')->setVersion(1), [
            ShoppingListChangeNameAction::of()->setName(LocalizedString::ofLangAndText('en', 'foobar'))
        ], $params);
    }

    public function testDelete()
    {
        $this->client->execute(
            Argument::that(function (ShoppingListDeleteRequest $request) {
                static::assertSame(ShoppingList::class, $request->getResultClass());
                static::assertSame('list-1', $request->getId());
                static::assertSame(1, $request->getVersion());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getShoppingListRepository();
        $repository->delete('en', ShoppingList::of()->setId('list-1')->setVersion(1));
    }
}
