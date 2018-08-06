<?php

namespace Commercetools\Symfony\SetupBundle\Command;

use Commercetools\Core\Model\Channel\ChannelDraft;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\Common\GeoLocation;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommercetoolsChannelsSetCommand extends ContainerAwareCommand
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
            ->setName('commercetools:channels-set')
            ->setDescription('Create or update the channels of the project via the conf file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $channels = $this->getContainer()->getParameter('commercetools.channels');
        $channelKeys = array_keys($channels);
        $existingChannels = $this->repository->getChannels();

        foreach ($existingChannels as $existingChannel) {
            $output->writeln(sprintf('deleting: %s', $existingChannel->getKey()));
            $this->repository->deleteChannel($existingChannel);
        }

        foreach ($channelKeys as $channelKey) {
            $output->writeln(sprintf('creating: %s', $channelKey));
            $channel = $channels[$channelKey];

            $channelDraft = ChannelDraft::ofKey($channelKey);

            if (isset($channel['name']) && !is_null($channel['name'])) {
                $channelDraft->setName(LocalizedString::fromArray($channel['name']));
            }

            if (isset($channel['description']) && !is_null($channel['description'])) {
                $channelDraft->setDescription(LocalizedString::fromArray($channel['description']));
            }

            if (isset($channel['roles']) && !is_null($channel['roles'])) {
                $channelDraft->setRoles($channel['roles']);
            }

            if (isset($channel['address']) && !is_null($channel['address'])) {
                $channelDraft->setAddress(Address::fromArray($channel['address']));
            }

            if (isset($channel['geoLocation']) && !is_null($channel['geoLocation'])) {
                $channelDraft->setGeoLocation(GeoLocation::fromArray($channel['geoLocation']));
            }

            $channel = $this->repository->createChannel($channelDraft);
            $output->writeln(sprintf('channel created: %s', json_encode($channel)));
        }
    }
}
