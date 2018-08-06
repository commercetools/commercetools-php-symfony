<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Tests\Model;


use Commercetools\Core\Model\Project\Project;
use Commercetools\Core\Request\Project\Command\ProjectChangeCountriesAction;
use Commercetools\Core\Request\Project\Command\ProjectChangeCurrenciesAction;
use Commercetools\Core\Request\Project\Command\ProjectChangeLanguagesAction;
use Commercetools\Core\Request\Project\Command\ProjectChangeMessagesEnabledAction;
use Commercetools\Core\Request\Project\Command\ProjectChangeNameAction;
use Commercetools\Core\Request\Project\Command\ProjectSetShippingRateInputTypeAction;
use Commercetools\Symfony\SetupBundle\Model\ProjectUpdateBuilder;
use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ProjectUpdateBuilderTest extends TestCase
{
    public function getActionProvider()
    {
        return [
            ['changeCountries', ProjectChangeCountriesAction::class],
            ['changeCurrencies', ProjectChangeCurrenciesAction::class],
            ['changeLanguages', ProjectChangeLanguagesAction::class],
            ['changeMessagesEnabled', ProjectChangeMessagesEnabledAction::class],
            ['changeName', ProjectChangeNameAction::class],
            ['setShippingRateInputType', ProjectSetShippingRateInputTypeAction::class]
        ];
    }


    /**
     * @dataProvider getActionProvider
     */
    public function testUpdateMethods($updateMethod, $actionClass)
    {
        $project = $this->prophesize(Project::class);

        $repository = $this->prophesize(SetupRepository::class);

        $repository->updateProject(
            $project,
            Argument::allOf(
                Argument::containing(Argument::type($actionClass))
            )
        )->shouldBeCalledTimes(1);

        $update = new ProjectUpdateBuilder($project->reveal(), $repository->reveal());

        $action = $actionClass::of();
        $update->$updateMethod($action);

        $update->flush();
    }
}

