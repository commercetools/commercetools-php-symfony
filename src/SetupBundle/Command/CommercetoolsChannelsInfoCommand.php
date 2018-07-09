<?php

namespace Commercetools\Symfony\SetupBundle\Command;

use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommercetoolsChannelsInfoCommand extends ContainerAwareCommand
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
            ->setName('commercetools:channels-info')
            ->setDescription('Get channels information')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $channels = $this->repository->getChannels();

        foreach ($channels as $channel) {
            $output->writeln(sprintf('Channel\'s key: %s', $channel->getKey()));
            $output->writeln(sprintf('Channel\'s name: %s', $channel->getName()));
            $output->writeln(sprintf('Channel\'s description: %s', $channel->getDescription()));
            $output->writeln(sprintf('Roles: %s', implode(', ', $channel->getRoles())));
            $output->writeln(sprintf('Address: %s', json_encode($channel->getAddress())));
            $output->writeln(sprintf('Geolocation: %s', json_encode($channel->getGeoLocation())));
            $output->writeln(sprintf('Created at: %s', $channel->getCreatedAt()->format('c')));
            $output->writeln(sprintf('Last modified at: %s', $channel->lastModifiedAt()->format('c')));
            $output->writeln('=========================================================');
        }


    }
}
