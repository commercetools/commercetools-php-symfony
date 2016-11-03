<?php

namespace Commercetools\Symfony\CtpBundle\Command;

use Commercetools\Core\Request\Project\ProjectGetRequest;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommercetoolsProjectCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('commercetools:project')
            ->setDescription('Get project information')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client= $this->getContainer()->get('commercetools.client');

        $request= ProjectGetRequest::of();
        $response=$request->executeWithClient($client);
        $project=$request->mapFromResponse($response);

        $output->writeln(sprintf('Project\'s key: %s', $project->getKey()));
        $output->writeln(sprintf('Project\'s name: %s', $project->getName()));
        $output->writeln(sprintf('Created at: %s', $project->getCreatedAt()->format('c')));
        $output->writeln(sprintf('Trial until: %s', $project->getTrialUntil()->format('c')));

        $output->writeln(sprintf('Countries: %s', implode(', ', $project->getCountries()->toArray())));
        $output->writeln(sprintf('Currencies: %s', implode(', ', $project->getCurrencies()->toArray())));

        $output->writeln(sprintf('Countries: %s', implode(', ', $project->getCountries()->toArray())));
        $output->writeln(sprintf('Messages: %s', ($project->getMessages()->getEnabled()) ? 'enabled': 'disabled'));
    }
}
