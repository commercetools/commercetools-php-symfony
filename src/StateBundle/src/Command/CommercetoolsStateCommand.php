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
            ->setName('commercetools:set-state-machine-config')
            ->setDescription('Get CTP states and create a YAML file with Symfony "state_machine" configuration. File is saved in appropriate directory')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $states = $this->repository->getStates();
        $helper = ProcessStates::of();
        $stateTypes = $helper->parse($states, 'state_machine');

        $yaml = Yaml::dump($stateTypes, 100, 4);
        $kernel = $this->getContainer()->get('kernel');

        $filename = $kernel->getProjectDir() . '/config/packages/' . $kernel->getEnvironment() . '/workflow.yaml';

        if ($kernel->getEnvironment() !== 'test') {
            file_put_contents($filename, $yaml);
        }

        $output->writeln('Configuration file saved successfully at ' . $filename);
    }
}
