<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Tests\DependencyInjection\Command;


use Commercetools\Core\Model\Common\Collection;
use Commercetools\Core\Model\Message\MessagesConfiguration;
use Commercetools\Core\Model\Project\Project;
use Commercetools\Core\Model\Project\ShippingRateInputType;
use Commercetools\Symfony\CtpBundle\Tests\TestKernel;
use Commercetools\Symfony\SetupBundle\Command\CommercetoolsProjectInfoCommand;
use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CommercetoolsProjectInfoCommandTest extends KernelTestCase
{
    public static function setUpBeforeClass()
    {
        static::$kernel = new TestKernel(function (ContainerBuilder $container) {});
        static::$kernel->boot();
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
                ->setTrialUntil(new \DateTime('2019-10-08'))
                ->setShippingRateInputType(ShippingRateInputType::of()->setType('foo'))
        )->shouldBeCalledOnce();

        $application = new Application(static::$kernel);
        $application->add(new CommercetoolsProjectInfoCommand($setupRepository->reveal()));

        $command = $application->find('commercetools:project-info');
        $command->setApplication($application);
        $commandTester = new CommandTester($command);

        $commandTester->execute(['command' => $command->getName()]);

        $this->assertRegExp('/Project\'s key: project-key-1/', $commandTester->getDisplay());
        $this->assertRegExp('/Project\'s name: project-name-1/', $commandTester->getDisplay());
        $this->assertRegExp('/Countries: DE/', $commandTester->getDisplay());
        $this->assertRegExp('/Currencies: EUR/', $commandTester->getDisplay());
        $this->assertRegExp('/Languages: en/', $commandTester->getDisplay());
        $this->assertRegExp('/Created at: 2018-11-/', $commandTester->getDisplay());
        $this->assertRegExp('/Messages: enabled/', $commandTester->getDisplay());
        $this->assertRegExp('/Trial until: 2019-10-/', $commandTester->getDisplay());
        $this->assertRegExp('/Shipping rate input type: {"type":"foo"}/', $commandTester->getDisplay());
    }
}
