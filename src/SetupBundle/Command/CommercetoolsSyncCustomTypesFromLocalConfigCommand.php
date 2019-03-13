<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Command;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Type\TypeCollection;
use Commercetools\Core\Response\ErrorResponse;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\SetupBundle\Model\ProcessCustomTypes;
use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommercetoolsSyncCustomTypesFromLocalConfigCommand extends Command
{
    private $repository;
    private $client;
    private $parameters;

    public function __construct(SetupRepository $repository, Client $client, array $parameters)
    {
        parent::__construct();
        $this->repository = $repository;
        $this->client = $client;
        $this->parameters = $parameters;
    }

    protected function configure()
    {
        $this
            ->setName('commercetools:sync-custom-types-from-local')
            ->setDescription('Sync custom types from local to server')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $params = QueryParams::of()->add('limit', '500');
        $serverTypes = $this->repository->getCustomTypes('en', $params);

        $serverTypesFormatted = TypeCollection::fromArray(ProcessCustomTypes::of()->parseTypes($serverTypes));

        $localTypesArray = $this->parameters;
        $localTypes = TypeCollection::fromArray($localTypesArray);

        $processor = ProcessCustomTypes::of();

        $actions = $processor->getChangesForServerSync($localTypes, $serverTypesFormatted);
        $actions = $processor->convertFieldDefinitionsToObject($actions);
        $requests = $processor->mapChangesToRequests($actions);

        if (empty($requests)) {
            $output->writeln('No changes found between server and local');
            return;
        }

        $success = true;
        foreach ($requests as $request) {
            $response = $request->executeWithClient($this->client);

            if ($response instanceof ErrorResponse) {
                $success = false;
                $correlationId = $response->getCorrelationId();
                $message = $response->getMessage();

                $output->writeln("Action failed: $message \nCorrelationId: $correlationId");
            }
        }

        if ($success) {
            $output->writeln('CustomTypes synced to server successfully');
        }
    }
}
