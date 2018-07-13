<?php

namespace Commercetools\Symfony\SetupBundle\Command;

use Commercetools\Symfony\SetupBundle\Model\ArrayHelper;
use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommercetoolsProjectApplyConfiguration extends ContainerAwareCommand
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
        $helper = new ArrayHelper();
        $config = $this->getContainer()->getParameter('commercetools.all');
        $projectSettings = $helper->arrayCamelizeKeys($config['project_settings']);
        $projectOnline = $this->repository->getProject();

        $changedKeys = $helper->crossDiffRecursive($projectSettings, $projectOnline->toArray());
        $actionBuilder = $this->repository->getActionBuilder($projectOnline);
        $mapped = $actionBuilder->mapChangesToActions($changedKeys, $projectSettings);

        if (!empty($mapped)){
            $actionBuilder->addActions($mapped);
            $project = $actionBuilder->flush();
            $output->writeln(sprintf('CTP response: %s', json_encode($project)));
        } else {
            $output->writeln('No changes found between conf and server');
        }

    }
}
