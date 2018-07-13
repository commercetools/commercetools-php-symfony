<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Model;


use Commercetools\Core\Builder\Update\ProjectActionBuilder;
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
use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;

class ProjectUpdateBuilder extends ProjectActionBuilder
{
    const VALID_ACTIONS = [
        'countries' => ProjectChangeCountriesAction::class,
        'currencies' => ProjectChangeCurrenciesAction::class,
        'languages' => ProjectChangeLanguagesAction::class,
        'name' => ProjectChangeNameAction::class,
        'messages' => ProjectChangeMessagesEnabledAction::class,
        'shippingRateInputType' => ProjectSetShippingRateInputTypeAction::class
    ];

    const ACTION_TRANSFORMATION = [
        'setMessages' => 'setMessagesEnabled'
    ];

    const VALUE_TRANSFORMATION = [
        'setMessages' => 'transformMessageValue',
        'setShippingRateInputType' => 'transformShippingRateInputTypeValue'
    ];


    private $project;
    private $repository;

    public function __construct(Project $project, SetupRepository $repository)
    {
        $this->project = $project;
        $this->repository =$repository;
    }

    public function addAction(AbstractAction $action, $eventName = null)
    {
        $this->setActions(array_merge($this->getActions(), [$action]));

        return $this;
    }

    /**
     * @return Project
     */
    public function flush()
    {
        return $this->repository->updateProject($this->project, $this->getActions());
    }

    public function mapChangesToActions(array $allChanges, array $config)
    {
        return array_filter(array_map(function($change) use ($config){
            if (isset(self::VALID_ACTIONS[$change])) {
                $action = 'set'.ucfirst($change);
                $value = $config[$change];

                if (isset(self::VALUE_TRANSFORMATION[$action])) {
                    $value = call_user_func(array($this, self::VALUE_TRANSFORMATION[$action]), $value);
                }

                return [
                    'action' => self::ACTION_TRANSFORMATION[$action] ?? $action,
                    'class' => self::VALID_ACTIONS[$change],
                    'value' => $value
                ];
            }
        }, $allChanges));
    }

    public function addActions($mapped)
    {
        array_walk($mapped, function($action){
            $fn = $action['action'];
            $this->addAction($action['class']::of()->$fn($action['value']));
        });
    }

    public function transformMessageValue($value)
    {
        return $value['enabled'];
    }

    public function transformShippingRateInputTypeValue($configValues)
    {
        $type = ShippingRateInputType::of()->setType($configValues['type']);

        if($type->getType() == CartClassificationType::of()->getType()){
            $values = LocalizedEnumCollection::of();

            foreach ($configValues['values'] as $value) {
                $localizedEnum = LocalizedEnum::of()->setRawData($value);
                $values->add($localizedEnum);
            }

            $type = CartClassificationType::of()->setValues($values);
        }

        return $type;
    }
}
