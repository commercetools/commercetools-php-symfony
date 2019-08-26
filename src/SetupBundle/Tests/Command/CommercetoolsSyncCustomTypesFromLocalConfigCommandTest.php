<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Tests\Command;

use Commercetools\Core\Client\HttpClient;
use Commercetools\Core\Error\ApiException;
use Commercetools\Core\Model\Type\Type;
use Commercetools\Core\Model\Type\TypeCollection;
use Commercetools\Core\Model\Type\TypeDraft;
use Commercetools\Core\Request\Types\TypeCreateRequest;
use Commercetools\Core\Request\Types\TypeDeleteByKeyRequest;
use Commercetools\Core\Response\AbstractApiResponse;
use Commercetools\Core\Response\ErrorResponse;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Tests\TestKernel;
use Commercetools\Symfony\SetupBundle\Command\CommercetoolsSyncCustomTypesFromLocalConfigCommand;
use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CommercetoolsSyncCustomTypesFromLocalConfigCommandTest extends KernelTestCase
{
    public static function setUpBeforeClass()
    {
        static::$kernel = new TestKernel(function (ContainerBuilder $container) {
        });
        static::$kernel->boot();
    }

    public function testExecuteWithNoChanges()
    {
        /** @var SetupRepository $setupRepository */
        $setupRepository = $this->prophesize(SetupRepository::class);
        $setupRepository->getCustomTypes(
            'en',
            Argument::type(QueryParams::class)
        )->willReturn(
            TypeCollection::of()->add(Type::of()->setKey('bar')->setVersion(1))
        )->shouldBeCalledOnce();

        /** @var Client $client */
        $client = $this->prophesize(HttpClient::class);
        $client->execute(Argument::any())->shouldNotBeCalled();

        $params = [
            'bar' => [
                'key' => 'bar'
            ]
        ];

        $application = new Application(static::$kernel);
        $application->add(new CommercetoolsSyncCustomTypesFromLocalConfigCommand(
            $setupRepository->reveal(),
            $client->reveal(),
            $params
        ));

        $command = $application->find('commercetools:sync-custom-types-from-local');
        $command->setApplication($application);
        $commandTester = new CommandTester($command);

        $commandTester->execute(['command' => $command->getName()]);

        $this->assertRegExp('/No changes found between server and local/', $commandTester->getDisplay());
    }

    public function testExecuteWithCreation()
    {
        /** @var SetupRepository $setupRepository */
        $setupRepository = $this->prophesize(SetupRepository::class);
        $setupRepository->getCustomTypes(
            'en',
            Argument::type(QueryParams::class)
        )->willReturn(
            TypeCollection::of()
        )->shouldBeCalledOnce();

        $client = $this->prophesize(HttpClient::class);
        $client->execute(
            Argument::that(function (TypeCreateRequest $request) {
                static::assertInstanceOf(TypeDraft::class, $request->getObject());
                return true;
            }),
            Argument::is(null),
            Argument::is(["http_errors" => true])
        )->shouldBeCalled();

        $params = [
            'bar' => [
                'key' => 'bar'
            ]
        ];

        $application = new Application(static::$kernel);
        $application->add(new CommercetoolsSyncCustomTypesFromLocalConfigCommand(
            $setupRepository->reveal(),
            $client->reveal(),
            $params
        ));

        $command = $application->find('commercetools:sync-custom-types-from-local');
        $command->setApplication($application);
        $commandTester = new CommandTester($command);

        $commandTester->execute(['command' => $command->getName()]);

        $this->assertRegExp('/CustomTypes synced to server successfully/', $commandTester->getDisplay());
    }

    public function testExecuteWithDeletion()
    {
        /** @var SetupRepository $setupRepository */
        $setupRepository = $this->prophesize(SetupRepository::class);
        $setupRepository->getCustomTypes(
            'en',
            Argument::type(QueryParams::class)
        )->willReturn(
            TypeCollection::of()->add(Type::of()->setKey('bar')->setVersion(1))
        )->shouldBeCalledOnce();

        $client = $this->prophesize(HttpClient::class);
        $client->execute(
            Argument::that(function (TypeDeleteByKeyRequest $request) {
                static::assertSame('bar', $request->getKey());
                return true;
            }),
            Argument::is(null),
            Argument::is(["http_errors" => true])
        )->shouldBeCalled();

        $params = [];

        $application = new Application(static::$kernel);
        $application->add(new CommercetoolsSyncCustomTypesFromLocalConfigCommand(
            $setupRepository->reveal(),
            $client->reveal(),
            $params
        ));

        $command = $application->find('commercetools:sync-custom-types-from-local');
        $command->setApplication($application);
        $commandTester = new CommandTester($command);

        $commandTester->execute(['command' => $command->getName()]);

        $this->assertRegExp('/CustomTypes synced to server successfully/', $commandTester->getDisplay());
    }

    public function testExecuteWithError()
    {
        /** @var SetupRepository $setupRepository */
        $setupRepository = $this->prophesize(SetupRepository::class);
        $setupRepository->getCustomTypes(
            'en',
            Argument::type(QueryParams::class)
        )->willReturn(
            TypeCollection::of()->add(Type::of()->setKey('bar')->setVersion(1))
        )->shouldBeCalledOnce();

        $requestInterface = $this->prophesize(RequestInterface::class);
        $responseInterface = $this->prophesize(ResponseInterface::class);
        $responseInterface->getHeader('X-Correlation-ID')->willReturn(['aaa-111'])->shouldBeCalledOnce();
        $responseInterface->getStatusCode()->willReturn(500)->shouldBeCalled();
        $responseInterface->getBody()->shouldBeCalled();
        $responseInterface->getReasonPhrase()->shouldBeCalled();

        $client = $this->prophesize(HttpClient::class);
        $client->execute(
            Argument::that(function (TypeDeleteByKeyRequest $request) {
                static::assertSame('bar', $request->getKey());
                return true;
            }),
            Argument::is(null),
            Argument::is(["http_errors" => true])
        )->will(function () use ($requestInterface, $responseInterface) {
                throw new ApiException('error', $requestInterface->reveal(), $responseInterface->reveal());
        })->shouldBeCalled();

        $params = [];

        $application = new Application(static::$kernel);
        $application->add(new CommercetoolsSyncCustomTypesFromLocalConfigCommand(
            $setupRepository->reveal(),
            $client->reveal(),
            $params
        ));

        $command = $application->find('commercetools:sync-custom-types-from-local');
        $command->setApplication($application);
        $commandTester = new CommandTester($command);

        $commandTester->execute(['command' => $command->getName()]);

        $this->assertRegExp('/Action failed/', $commandTester->getDisplay());
    }
}
