<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Model;

use Commercetools\Core\Model\Common\LocalizedEnum;
use Commercetools\Core\Model\Common\LocalizedEnumCollection;
use Commercetools\Core\Model\Project\CartClassificationType;
use Commercetools\Core\Model\Project\Project;
use Commercetools\Core\Model\Project\ShippingRateInputType;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Core\Request\Project\Command\ProjectChangeCountriesAction;
use Commercetools\Core\Request\Project\Command\ProjectChangeCurrenciesAction;
use Commercetools\Core\Request\Project\Command\ProjectChangeLanguagesAction;
use Commercetools\Core\Request\Project\Command\ProjectChangeMessagesEnabledAction;
use Commercetools\Core\Request\Project\Command\ProjectChangeNameAction;
use Commercetools\Core\Request\Project\Command\ProjectSetShippingRateInputTypeAction;

class ConfigureProject
{
    const VALID_ACTIONS = [
        'countries' => ProjectChangeCountriesAction::class,
        'currencies' => ProjectChangeCurrenciesAction::class,
        'languages' => ProjectChangeLanguagesAction::class,
        'name' => ProjectChangeNameAction::class,
    ];

    const VALUE_TRANSFORMATION = [
        'messages' => 'transformMessageValue',
        'shippingRateInputType' => 'transformShippingRateInputTypeValue',
    ];


    /**
     * @param array $value
     * @return ProjectChangeMessagesEnabledAction
     */
    private function transformMessageValue(array $value)
    {
        return ProjectChangeMessagesEnabledAction::ofMessagesEnabled($value['enabled']);
    }

    /**
     * @param array $configValues
     * @return ProjectSetShippingRateInputTypeAction
     */
    private function transformShippingRateInputTypeValue(array $configValues)
    {
        $type = ShippingRateInputType::of()->setType($configValues['type']);

        if($type->getType() == CartClassificationType::of()->getType()){
            $values = LocalizedEnumCollection::of();

            foreach ($configValues['values'] as $value) {
                $localizedEnum = LocalizedEnum::fromArray($value);
                $values->add($localizedEnum);
            }

            $type = CartClassificationType::of()->setValues($values);
        }

        return ProjectSetShippingRateInputTypeAction::of()->setShippingRateInputType($type);
    }

    /**
     * @param array $allChanges
     * @param array $config
     * @return array
     */
    public function mapChangesToActions(array $allChanges, array $config)
    {
        return array_filter(array_map(function($change) use ($config){
            if (isset(self::VALUE_TRANSFORMATION[$change])) {
                return call_user_func(array($this, self::VALUE_TRANSFORMATION[$change]), $config[$change]);
            } else if (isset(self::VALID_ACTIONS[$change])) {
                $actionClass = self::VALID_ACTIONS[$change];
                $action = $actionClass::of();
                $setValue = 'set' . ucfirst($change);
                $action->$setValue($config[$change]);

                return $action;
            }
        }, $allChanges));
    }

    /**
     * @param array $config
     * @param Project $online
     * @param ProjectUpdateBuilder $actionBuilder
     * @return Project|null
     */
    public function update(array $config, Project $online, ProjectUpdateBuilder $actionBuilder)
    {
        $helper = new ArrayHelper();

        $projectSettings = $helper->arrayCamelizeKeys($config);
        $changedKeys = $helper->crossDiffRecursive($projectSettings, $online->toArray());
        $mapped = $this->mapChangesToActions($changedKeys, $projectSettings);

        if (empty($mapped)) {
            return null;
        }


        $this->addActions($actionBuilder, $mapped);
        $project = $actionBuilder->flush();

        return $project;
    }

    /**
     * @param ProjectUpdateBuilder $builder
     * @param AbstractAction[] $mappedActions
     */
    private function addActions(ProjectUpdateBuilder $builder, array $mappedActions)
    {
        array_walk($mappedActions, function(AbstractAction $action) use($builder) {
            $fn = $action->getAction();
            $builder->$fn($action);
        });
    }

    /**
     * @return ConfigureProject
     */
    public static function of()
    {
        return new static();
    }
}
