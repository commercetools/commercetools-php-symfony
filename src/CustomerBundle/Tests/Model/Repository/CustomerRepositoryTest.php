<?php
/**
 *
 */

namespace Commercetools\Symfony\CustomerBundle\Tests\Model\Repository;


use Commercetools\Core\Builder\Request\CustomerRequestBuilder;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Common\JsonDeserializeInterface;
use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Model\Customer\CustomerDraft;
use Commercetools\Core\Model\MapperInterface;
use Commercetools\Core\Model\Type\StringType;
use Commercetools\Core\Request\AbstractApiRequest;
use Commercetools\Core\Request\ClientRequestInterface;
use Commercetools\Core\Request\Customers\Command\CustomerSetKeyAction;
use Commercetools\Core\Request\Customers\CustomerByIdGetRequest;
use Commercetools\Core\Request\Customers\CustomerCreateRequest;
use Commercetools\Core\Request\Customers\CustomerDeleteRequest;
use Commercetools\Core\Request\Customers\CustomerPasswordChangeRequest;
use Commercetools\Core\Request\Customers\CustomerUpdateRequest;
use Commercetools\Core\Response\ApiResponseInterface;
use Commercetools\Core\Response\ResourceResponse;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Commercetools\Symfony\CustomerBundle\Model\Repository\CustomerRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Tests\Fixtures\ExternalAdapter;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CustomerRepositoryTest extends TestCase
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

    private function getCustomerRepository()
    {
        return new CustomerRepository(
            false,
            $this->cache,
            $this->client->reveal(),
            $this->mapperFactory->reveal()
        );
    }

    public function testGetCustomer()
    {
        $this->client->execute(
            Argument::that(function(CustomerByIdGetRequest $request){
                static::assertSame(Customer::class, $request->getResultClass());
                static::assertSame('customer-1', $request->getId());
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();


        $repository = $this->getCustomerRepository();
        $repository->getCustomerById('en', 'customer-1');
    }

    public function testUpdateCustomer()
    {
        $customer = Customer::of()->setId('customer-1')->setVersion(1);

        $this->client->execute(
            Argument::that(function(CustomerUpdateRequest $request){
                static::assertSame(Customer::class, $request->getResultClass());
                static::assertSame('customer-1', $request->getId());
                static::assertSame(1, $request->getVersion());
                $action = current($request->getActions());
                static::assertInstanceOf(CustomerSetKeyAction::class, $action);
                static::assertSame('foobar', $action->getKey());
                static::assertContains('foo=bar', (string)$request->httpRequest()->getUri());
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $actions = [CustomerSetKeyAction::of()->setKey('foobar')];

        $params = new QueryParams();
        $params->add('foo', 'bar');

        $repository = $this->getCustomerRepository();
        $repository->update($customer, $actions, $params);
    }

    public function testChangePassword()
    {
        $customer = Customer::of()->setId('customer-1')->setVersion(1);
        $this->client->execute(
            Argument::that(function(CustomerPasswordChangeRequest $request){
                static::assertSame(Customer::class, $request->getResultClass());
                static::assertSame('customer-1', $request->getId());
                static::assertSame(1, $request->getVersion());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getCustomerRepository();
        $repository->changePassword($customer, 'foo', 'bar');
    }

    public function testCreateCustomer()
    {
        $session = $this->prophesize(SessionInterface::class);
        $session->getId()->willReturn('bar')->shouldBeCalledOnce();

        $this->client->execute(
            Argument::that(function(CustomerCreateRequest $request){
                static::assertInstanceOf(CustomerDraft::class, $request->getObject());
                static::assertSame('email@localhost', $request->getObject()->getEmail());
                static::assertSame('foo', $request->getObject()->getPassword());
                static::assertSame('bar', $request->getObject()->getAnonymousId());
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getCustomerRepository();
        $repository->createCustomer('en', 'email@localhost', 'foo', $session->reveal());
    }

    public function testDeleteCustomer()
    {
        $customer = Customer::of()->setId('customer-1')->setVersion(1);
        $this->client->execute(
            Argument::that(function(CustomerDeleteRequest $request){
                static::assertSame(Customer::class, $request->getResultClass());
                static::assertSame('customer-1', $request->getId());
                static::assertSame(1, $request->getVersion());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getCustomerRepository();
        $repository->delete($customer);
    }

}
