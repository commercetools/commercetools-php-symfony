<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Tests\Command;


use Commercetools\Core\Model\Type\Type;
use Commercetools\Core\Model\Type\TypeCollection;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Tests\TestKernel;
use Commercetools\Symfony\SetupBundle\Command\CommercetoolsSyncCustomTypesFromServerCommand;
use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CommercetoolsSyncCustomTypesFromServerCommandTest extends KernelTestCase
{
    public static function setUpBeforeClass()
    {
        static::$kernel = new TestKernel(function (ContainerBuilder $container) {});
        static::$kernel->boot();
    }

    public function testExecute()
    {
        /** @var SetupRepository $setupRepository */
        $setupRepository = $this->prophesize(SetupRepository::class);
        $setupRepository->getCustomTypes(
            'en',
            Argument::type(QueryParams::class)
        )->willReturn(
            TypeCollection::of()->add(Type::of()->setKey('bar'))
        )->shouldBeCalledOnce();

        $application = new Application(static::$kernel);
        $application->add(new CommercetoolsSyncCustomTypesFromServerCommand($setupRepository->reveal()));

        $command = $application->find('commercetools:sync-custom-types-from-server');
        $command->setApplication($application);
        $commandTester = new CommandTester($command);

        $commandTester->execute(['command' => $command->getName()]);

        $this->assertRegExp('/CustomTypes map file saved successfully at/', $commandTester->getDisplay());
    }

}
