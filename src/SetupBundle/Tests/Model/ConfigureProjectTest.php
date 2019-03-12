<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Tests\Model;

use Commercetools\Core\Model\Common\LocalizedEnumCollection;
use Commercetools\Core\Model\Project\CartClassificationType;
use Commercetools\Core\Model\Project\Project;
use Commercetools\Core\Model\Project\ShippingRateInputType;
use Commercetools\Core\Request\Project\Command\ProjectChangeCountriesAction;
use Commercetools\Core\Request\Project\Command\ProjectChangeLanguagesAction;
use Commercetools\Core\Request\Project\Command\ProjectChangeMessagesEnabledAction;
use Commercetools\Core\Request\Project\Command\ProjectChangeNameAction;
use Commercetools\Core\Request\Project\Command\ProjectSetShippingRateInputTypeAction;
use Commercetools\Symfony\SetupBundle\Model\ArrayHelper;
use Commercetools\Symfony\SetupBundle\Model\ConfigureProject;
use Commercetools\Symfony\SetupBundle\Model\ProjectUpdateBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ConfigureProjectTest extends TestCase
{
    private $projectArray;
    private $configureProject;

    protected function setUp()
    {
        $this->projectArray = [
            "key" => "foo-bar-project",
            "name" => "bar-foo-project",
            "countries" => [
                0 => "UK",
                1 => "DE",
                2 => "DK",
            ],
            "currencies" => [
                0 => "EUR",
                1 => "AUD",
            ],
            "languages" => [
                0 => "fr",
                1 => "en",
                2 => "de"
            ],
            "created_at" => "2018-04-04T11:28:49.685Z",
            "trial_until" => "2019-04-04T11:28:49.685Z",
            "messages" => [
                "enabled" => false
            ],
            "version" => 115,
            "shipping_rate_input_type" => [
                "type" => "CartClassification",
                "values" => [
                    0 => [
                        "key" => "larger",
                        "label" => [
                            "gr" => "ena",
                            "en" => "ones"
                        ]
                    ],
                    1 => [
                        "key" => "medium",
                        "label" => [
                            "en" => "goto"
                        ]
                    ]
                ]
            ]
        ];

        $this->configureProject = ConfigureProject::of();
    }

    public function testMapChangesToActions()
    {
        $result = $this->configureProject->mapChangesToActions(['languages'], $this->projectArray);
        $expected = [
            ProjectChangeLanguagesAction::ofLanguages(['fr', 'en', 'de'])
        ];
        $this->assertEquals($expected, $result);
    }

    public function testMapTwoChangesToActions()
    {
        $result = $this->configureProject->mapChangesToActions(['name', 'countries'], $this->projectArray);
        $expected = [
            ProjectChangeNameAction::ofName('bar-foo-project'),
            ProjectChangeCountriesAction::ofCountries(['UK', 'DE', 'DK'])
        ];
        $this->assertEquals($expected, $result);
    }

    public function testMapChangesToActionsOnMessages()
    {
        $result = $this->configureProject->mapChangesToActions(['messages'], $this->projectArray);
        $expected = [
            ProjectChangeMessagesEnabledAction::ofMessagesEnabled(false)
        ];
        $this->assertEquals($expected, $result);
    }

    public function testMapChangesToActionsOnShippingRateInputTypeIgnoringExtras()
    {
        $arrayHelper = new ArrayHelper();
        $camelizedArrayConfig = $arrayHelper->arrayCamelizeKeys($this->projectArray);
        $camelizedArrayConfig['shippingRateInputType']['type'] = 'CartValue';
        $result = $this->configureProject->mapChangesToActions(['shippingRateInputType'], $camelizedArrayConfig);

        $expected = [
            ProjectSetShippingRateInputTypeAction::of()->setShippingRateInputType(ShippingRateInputType::of()->setType('CartValue'))
        ];
        $this->assertEquals($expected, $result);
    }

    public function testMapChangesToActionsOnShippingRateInputType()
    {
        $arrayHelper = new ArrayHelper();
        $camelizedArrayConfig = $arrayHelper->arrayCamelizeKeys($this->projectArray);
        $results = $this->configureProject->mapChangesToActions(['shippingRateInputType'], $camelizedArrayConfig);

        $result = $results[0];

        $this->assertInstanceOf(ProjectSetShippingRateInputTypeAction::class, $result);
        $this->assertEquals('setShippingRateInputType', $result->getAction());
        $this->assertInstanceOf(LocalizedEnumCollection::class, $result->getShippingRateInputType()->getValues());
        $this->assertInstanceOf(CartClassificationType::class, $result->getShippingRateInputType());
    }

    public function testUpdateWithNoChanges()
    {
        $arrayHelper = new ArrayHelper();
        $project = Project::fromArray($arrayHelper->arrayCamelizeKeys($this->projectArray));
        $updateBuilder = $this->prophesize(ProjectUpdateBuilder::class);

        $result = $this->configureProject->update($this->projectArray, $project, $updateBuilder->reveal());

        $this->assertNull($result);
    }

    public function testUpdateWithChanges()
    {
        $arrayHelper = new ArrayHelper();
        $changedConfig = $this->projectArray;
        $changedConfig['countries'] = ['UK', 'DE'];
        $project = Project::fromArray($arrayHelper->arrayCamelizeKeys($this->projectArray));
        $updateBuilder = $this->prophesize(ProjectUpdateBuilder::class);

        $updateBuilder
            ->changeCountries(Argument::type(ProjectChangeCountriesAction::class))
            ->willReturn(null)
            ->shouldBeCalledTimes(1);
        $updateBuilder->flush()->willReturn(Project::of())->shouldBeCalled();

        $result = $this->configureProject->update($changedConfig, $project, $updateBuilder->reveal());

        $this->assertInstanceOf(Project::class, $result);
    }
}
