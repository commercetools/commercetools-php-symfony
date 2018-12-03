<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Tests\Command;


use Commercetools\Core\Model\Common\Collection;
use Commercetools\Core\Model\Message\MessagesConfiguration;
use Commercetools\Core\Model\Project\Project;
use Commercetools\Symfony\CtpBundle\Tests\TestKernel;
use Commercetools\Symfony\SetupBundle\Command\CommercetoolsProjectApplyConfigurationCommand;
use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CommercetoolsProjectApplyConfigurationCommandTest extends KernelTestCase
{
    public function setUp()
    {
        $this->rebuildContainer(function (ContainerBuilder $container) {
            $container->setParameter('commercetools.all', [
                'project_settings' => []
            ]);
        });
    }

    public function rebuildContainer(\Closure $containerConfigurator)
    {
        if (static::$kernel) {
            static::$kernel->shutdown();
            static::$kernel = null;
            static::$container = null;
        }

        static::$kernel = new TestKernel($containerConfigurator);
        static::$kernel->boot();
        static::$container = static::$kernel->getContainer();
    }

    public function testExecute()
    {
        $setupRepository = $this->prophesize(SetupRepository::class);
        $setupRepository->getProject()->willReturn(
            Project::of()
                ->setKey('project-key-1')
                ->setName('project-name-1')
                ->setCountries(Collection::of()->add('DE'))
                ->setCurrencies(Collection::of()->add('EUR'))
                ->setLanguages(Collection::of()->add('en'))
                ->setCreatedAt(new \DateTime('2018-11-17'))
                ->setMessages(MessagesConfiguration::of()->setEnabled(true))
        )->shouldBeCalledOnce();

        $setupRepository->applyConfiguration(Argument::type('array'), Argument::type(Project::class))
            ->willReturn(null)->shouldBeCalled();

        $application = new Application(static::$kernel);
        $application->add(new CommercetoolsProjectApplyConfigurationCommand($setupRepository->reveal(), static::$container));

        $command = $application->find('commercetools:project-apply-configuration');
        $command->setApplication($application);
        $commandTester = new CommandTester($command);

        $commandTester->execute(['command' => $command->getName()]);

        $this->assertRegExp('/No changes found between conf and server/', $commandTester->getDisplay());
    }

    public function testExecuteWithChanges()
    {
        $setupRepository = $this->prophesize(SetupRepository::class);
        $setupRepository->getProject()->willReturn(
            Project::of()
                ->setKey('project-key-1')
                ->setName('project-name-1')
                ->setCountries(Collection::of()->add('DE'))
                ->setCurrencies(Collection::of()->add('EUR'))
                ->setLanguages(Collection::of()->add('en'))
                ->setCreatedAt(new \DateTime('2018-11-17'))
                ->setMessages(MessagesConfiguration::of()->setEnabled(true))
        )->shouldBeCalledOnce();

        $setupRepository->applyConfiguration(Argument::type('array'), Argument::type(Project::class))
            ->willReturn(['foo' => 'bar'])->shouldBeCalled();

        $application = new Application(static::$kernel);
        $application->add(new CommercetoolsProjectApplyConfigurationCommand($setupRepository->reveal(), static::$container));

        $command = $application->find('commercetools:project-apply-configuration');
        $command->setApplication($application);
        $commandTester = new CommandTester($command);

        $commandTester->execute(['command' => $command->getName()]);

        $this->assertRegExp('/CTP response/', $commandTester->getDisplay());
        $this->assertRegExp('/{\"foo\":\"bar\"}/', $commandTester->getDisplay());
    }
}
