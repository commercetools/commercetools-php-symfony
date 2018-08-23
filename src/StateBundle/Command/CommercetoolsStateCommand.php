<?php

namespace Commercetools\Symfony\StateBundle\Command;

use Commercetools\Symfony\StateBundle\Model\ProcessStates;
use Commercetools\Symfony\StateBundle\Model\Repository\StateRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class CommercetoolsStateCommand extends ContainerAwareCommand
{
    private $repository;

    public function __construct(StateRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    protected function configure()
    {
        $this
            ->setName('commercetools:get-state-machine-config')
            ->setDescription('Get CTP states and create a YAML file with Symfony "state_machine" config')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $states = $this->repository->getStates();
        $helper = ProcessStates::of();
        $stateTypes = $helper->parse($states, 'state_machine');

        $output->write(Yaml::dump($stateTypes, 100, 4));
    }
}
