<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Command;


use Commercetools\Core\Model\Type\TypeCollection;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\SetupBundle\Model\ProcessCustomTypes;
use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class CommercetoolsSyncCustomTypesFromLocalConfig extends ContainerAwareCommand
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
            ->setName('commercetools:sync-custom-types-from-local')
            ->setDescription('Sync custom types from local to server')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $params = QueryParams::of()->add('limit', '500');
        $serverTypes = $this->repository->getCustomTypes('en', $params);

        $serverTypesFormatted = TypeCollection::fromArray(ProcessCustomTypes::of()->parseTypes($serverTypes));

        $localTypesArray = $this->getContainer()->getParameter('commercetools.custom_types');
        $localTypes = TypeCollection::fromArray($localTypesArray);

        $processor = ProcessCustomTypes::of();

        $actions = $processor->getChangesForServerSync($localTypes, $serverTypesFormatted);
        $actions = $processor->convertFieldDefinitionsToObject($actions);
        $actions = $processor->mapChangesToRequests($actions);

//        dump($actions);

        $results = [];
        foreach ($actions as $action) {
            dump($action);
            $results[] = $this->repository->executeRequest($action);
        }

//        foreach ($results as $result) {
//           dump($result);
//        }
        dump($results);

        $output->writeln('CustomTypes synced to server successfully');
    }
}
