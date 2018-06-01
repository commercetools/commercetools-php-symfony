<?php
/**
 *
 */

namespace Commercetools\Symfony\CustomerBundle\Tests\Model\Repository;


use Commercetools\Core\Builder\Request\CustomerRequestBuilder;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Common\JsonDeserializeInterface;
use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Model\MapperInterface;
use Commercetools\Core\Model\Type\StringType;
use Commercetools\Core\Request\AbstractApiRequest;
use Commercetools\Core\Request\ClientRequestInterface;
use Commercetools\Core\Request\Customers\CustomerByIdGetRequest;
use Commercetools\Core\Response\ApiResponseInterface;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Commercetools\Symfony\CustomerBundle\Model\Repository\CustomerRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Cache\CacheItemPoolInterface;

class CustomerRepositoryTest extends TestCase
{
    private $cache;
    private $client;
    private $mapperFactory;

    protected function setUp()
    {
        $this->cache = $this->prophesize(CacheItemPoolInterface::class);
        $this->client = $this->prophesize(Client::class);
        $this->mapperFactory = $this->prophesize(MapperFactory::class);
    }

    public function testExecuteRequest()
    {
        $customerRepository = new CustomerRepository(0, $this->cache->reveal(), $this->client->reveal(), $this->mapperFactory->reveal());
        $request = $this->prophesize(AbstractApiRequest::class);
        $response = $this->prophesize(ApiResponseInterface::class);
        $customerResponse = $this->prophesize(JsonDeserializeInterface::class);

        $request->executeWithClient(Argument::type(Client::class))
            ->willReturn($response->reveal())
            ->shouldBeCalled();

        $request->mapFromResponse(
                Argument::type(ApiResponseInterface::class),
                null)
            ->willReturn($customerResponse->reveal())
            ->shouldBeCalled();

        $customers = $customerRepository->executeRequest($request->reveal(), 'en');
        $this->assertInstanceOf(JsonDeserializeInterface::class, $customers);
    }

}
