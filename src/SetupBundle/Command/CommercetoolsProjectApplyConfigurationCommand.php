<?php

namespace Commercetools\Symfony\SetupBundle\Command;

use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;

class CommercetoolsProjectApplyConfigurationCommand extends Command
{
    private $repository;

    private $container;

    public function __construct(SetupRepository $repository, Container $container)
    {
        parent::__construct();
        $this->repository = $repository;
        $this->container = $container;
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
        $config = $this->container->getParameter('commercetools.all');
        $projectOnline = $this->repository->getProject();

        $result = $this->repository->applyConfiguration($config['project_settings'], $projectOnline);

        if (is_null($result)) {
            $output->writeln('No changes found between conf and server');
            return;
        }

        $output->writeln(sprintf('CTP response: %s', json_encode($result)));

    }
}
