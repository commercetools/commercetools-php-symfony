<?php

namespace Commercetools\Symfony\SetupBundle\Command;

use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommercetoolsProjectApplyConfigurationCommand extends ContainerAwareCommand
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
            ->setName('commercetools:project-apply-configuration')
            ->setDescription('Apply the configuration of the conf file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getContainer()->getParameter('commercetools.all');
        $projectOnline = $this->repository->getProject();

        $result = $this->repository->applyConfiguration($config['project_settings'], $projectOnline);

        if (is_null($result)) {
            $output->writeln('No changes found between conf and server');
            return;
        }

        $output->writeln(sprintf('CTP response: %s', json_encode($result)));

    }
}
