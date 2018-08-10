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
            ->setName('commercetools:states-info')
            ->setDescription('Get states information')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $states = $this->repository->getStates();
        $helper = ProcessStates::of();
        $stateTypes = $helper->parse($states);
//        dump($stateTypes);

        $yamlFile = $helper->formatYamlFile($stateTypes);
        file_put_contents('/tmp/workflow.yaml', $yamlFile);
        dump($yamlFile);
    }
}
