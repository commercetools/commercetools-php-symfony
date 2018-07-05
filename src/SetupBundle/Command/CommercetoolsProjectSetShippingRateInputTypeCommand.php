<?php

namespace Commercetools\Symfony\SetupBundle\Command;

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
        // TODO
        $output->writeln(sprintf('Conf file ShippingRateInputType %s', null));
    }
}
