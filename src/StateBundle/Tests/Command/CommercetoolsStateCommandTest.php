<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests\Command;


use Commercetools\Core\Model\State\State;
use Commercetools\Core\Model\State\StateCollection;
use Commercetools\Symfony\CtpBundle\Tests\TestKernel;
use Commercetools\Symfony\StateBundle\Command\CommercetoolsStateCommand;
use Commercetools\Symfony\StateBundle\Model\Repository\StateRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Bundle\FrameworkBundle\Console\Application;


class CommercetoolsStateCommandTest extends KernelTestCase
{
    public static function setUpBeforeClass()
    {
        static::$kernel = new TestKernel(function (ContainerBuilder $container) {});
        static::$kernel->boot();
    }

    public function testExecute()
    {
        $stateRepository = $this->prophesize(StateRepository::class);
        $stateRepository->getStates()->willReturn(
            StateCollection::of()->add(State::of()->setType('OrderState')->setKey('bar'))
        )->shouldBeCalledOnce();

        $application = new Application(static::$kernel);
        $application->add(new CommercetoolsStateCommand($stateRepository->reveal()));

        $command = $application->find('commercetools:set-state-machine-config');
        $command->setApplication($application);
        $commandTester = new CommandTester($command);

        $commandTester->execute(['command' => $command->getName()]);

        $this->assertRegExp('/Configuration file saved successfully at/', $commandTester->getDisplay());
    }

}
