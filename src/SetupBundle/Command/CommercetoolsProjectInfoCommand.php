<?php

namespace Commercetools\Symfony\SetupBundle\Command;

use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommercetoolsProjectInfoCommand extends Command
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
            ->setName('commercetools:project-info')
            ->setDescription('Get project information')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $project = $this->repository->getProject();

        $output->writeln(sprintf('Project\'s key: %s', $project->getKey()));
        $output->writeln(sprintf('Project\'s name: %s', $project->getName()));
        // TODO check following if returning null
        $output->writeln(sprintf('Countries: %s', implode(', ', $project->getCountries()->toArray())));
        $output->writeln(sprintf('Currencies: %s', implode(', ', $project->getCurrencies()->toArray())));
        $output->writeln(sprintf('Languages: %s', implode(', ', $project->getLanguages()->toArray())));
        $output->writeln(sprintf('Created at: %s', $project->getCreatedAt()->format('c')));
        $output->writeln(sprintf('Messages: %s', ($project->getMessages()->getEnabled()) ? 'enabled': 'disabled'));

        if($project->getTrialUntil()){
            $output->writeln(sprintf('Trial until: %s', $project->getTrialUntil()->format('c')));
        }

        if($project->getShippingRateInputType()){
            $output->writeln(sprintf('Shipping rate input type: %s', json_encode($project->getShippingRateInputType())));
        }
    }
}
