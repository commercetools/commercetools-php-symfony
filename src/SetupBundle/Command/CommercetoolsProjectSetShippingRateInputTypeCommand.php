<?php

namespace Commercetools\Symfony\SetupBundle\Command;

use Commercetools\Core\Model\Common\LocalizedEnum;
use Commercetools\Core\Model\Common\LocalizedEnumCollection;
use Commercetools\Core\Model\Project\CartClassificationType;
use Commercetools\Core\Model\Project\ShippingRateInputType;
use Commercetools\Core\Request\Project\Command\ProjectSetShippingRateInputTypeAction;
use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommercetoolsProjectSetShippingRateInputTypeCommand extends ContainerAwareCommand
{
    private $repository;

    public function __construct(SetupRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    protected function configure()
    {
        $this
            ->setName('commercetools:project-set-shipping-rate-input-type')
            ->setDescription('Set the ShippingRateInputType of the project via the conf file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $shippingRateInputType = $this->getContainer()->getParameter('commercetools.project_settings.shipping_rate_input_type');

        $type = ShippingRateInputType::of()->setType($shippingRateInputType['type']);

        if($type->getType() == CartClassificationType::of()->getType()){
            $values = LocalizedEnumCollection::of();

            foreach ($shippingRateInputType['values'] as $value) {
                $localizedEnum = LocalizedEnum::of()->setRawData($value);
                $values->add($localizedEnum);
            }

            // TODO $type->setValues() ?
            $type = CartClassificationType::of()->setValues($values);
        }

        $actions[] = ProjectSetShippingRateInputTypeAction::of()->setShippingRateInputType($type);
        $project = $this->repository->updateProject($this->repository->getProject(), $actions);

        $output->writeln(sprintf('CTP response: %s', json_encode($project)));

        $output->writeln(sprintf('Conf file ShippingRateInputType %s', json_encode($shippingRateInputType)));
//        $output->writeln(sprintf('actions:  %s', json_encode($actions)));
    }
}
