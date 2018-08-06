<?php

namespace Commercetools\Symfony\StateBundle\Command;

use Commercetools\Symfony\StateBundle\Model\Repository\StateRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

//        foreach ($channels as $channel) {
//            $output->writeln(sprintf('Channel\'s key: %s', $channel->getKey()));
//            $output->writeln(sprintf('Channel\'s name: %s', $channel->getName()));
//            $output->writeln(sprintf('Channel\'s description: %s', $channel->getDescription()));
//            $output->writeln(sprintf('Roles: %s', implode(', ', $channel->getRoles())));
//            $output->writeln(sprintf('Address: %s', json_encode($channel->getAddress())));
//            $output->writeln(sprintf('Geolocation: %s', json_encode($channel->getGeoLocation())));
//            $output->writeln(sprintf('Created at: %s', $channel->getCreatedAt()->format('c')));
//            $output->writeln(sprintf('Last modified at: %s', $channel->lastModifiedAt()->format('c')));
//            $output->writeln('=========================================================');
//        }
        $output->writeln(json_encode($states));


    }
}
