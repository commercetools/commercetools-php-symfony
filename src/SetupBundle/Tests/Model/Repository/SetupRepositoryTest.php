<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Tests\Model\Repository;

use Commercetools\Core\Builder\Update\ProjectActionBuilder;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Project\Project;
use Commercetools\Core\Request\Project\Command\ProjectChangeNameAction;
use Commercetools\Core\Request\Project\ProjectGetRequest;
use Commercetools\Core\Request\Project\ProjectUpdateRequest;
use Commercetools\Core\Response\ResourceResponse;
use Commercetools\Symfony\CtpBundle\Logger\Logger;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Cache\Tests\Fixtures\ExternalAdapter;

class SetupRepositoryTest extends TestCase
{
    private $cache;
    private $mapperFactory;
    private $response;
    private $client;
    private $logger;

    protected function setUp()
    {
        $this->cache = new ExternalAdapter();
        $this->mapperFactory = $this->prophesize(MapperFactory::class);
        $this->logger = $this->prophesize(Logger::class);

        $this->response = $this->prophesize(ResourceResponse::class);
        $this->response->toArray()->willReturn([]);
        $this->response->getContext()->willReturn(null);
        $this->response->isError()->willReturn(false);

        $this->client = $this->prophesize(Client::class);
    }

    private function getSetupRepository()
    {
        return new SetupRepository(
            false,
            $this->cache,
            $this->client->reveal(),
            $this->mapperFactory->reveal(),
            $this->logger->reveal()
        );
    }

    public function testGetProject()
    {
        $this->client->execute(
            Argument::type(ProjectGetRequest::class),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getSetupRepository();
        $repository->getProject();
    }

    public function testUpdateProject()
    {
        $this->client->execute(
            Argument::that(function (ProjectUpdateRequest $request) {
                $action = current($request->getActions());

                static::assertSame(Project::class, $request->getResultClass());
                static::assertInstanceOf(ProjectChangeNameAction::class, $action);
                static::assertSame('foo', $action->getName());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getSetupRepository();
        $repository->updateProject(Project::of(), [
            ProjectChangeNameAction::of()->setName('foo')
        ]);
    }

    public function testApplyConfiguration()
    {
        $this->client->execute(
            Argument::that(function (ProjectUpdateRequest $request) {
                $action = current($request->getActions());

                static::assertSame(Project::class, $request->getResultClass());
                static::assertInstanceOf(ProjectChangeNameAction::class, $action);
                static::assertSame('foo', $action->getName());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getSetupRepository();
        $repository->applyConfiguration(['name' => 'foo'], Project::of()->setName('bar'));
    }

    public function testApplyEmptyConfiguration()
    {
        $this->client->execute(Argument::any(), Argument::any())->shouldNotBeCalled();

        $repository = $this->getSetupRepository();
        $project = $repository->applyConfiguration([], Project::of());
        $this->assertNull($project);
    }

    public function testGetActionBuilder()
    {
        $repository = $this->getSetupRepository();
        $builder = $repository->getActionBuilder(Project::of());
        $this->assertInstanceOf(ProjectActionBuilder::class, $builder);
    }
}
