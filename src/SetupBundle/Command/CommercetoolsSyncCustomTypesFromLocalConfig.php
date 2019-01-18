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

        $actions = $processor->mapChangesToRequests($processor->getChangesForServerSync($localTypes, $serverTypesFormatted));
//        $actions = $processor->mapChangesToRequests($processor->createChangesArray($localTypes, $serverTypesFormatted));

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
//        $types = $helper->parse($customTypes);

//        $yaml = Yaml::dump($types, 100, 4);
//        $yaml = Yaml::dump($customTypes->toArray(), 100, 4);
//        $kernel = $this->getContainer()->get('kernel');
//        $filename = $kernel->getProjectDir() . '/config/packages/' . $kernel->getEnvironment() . '/custom_types1.yaml';
//
//        if ($kernel->getEnvironment() !== 'test') {
//            file_put_contents($filename, $yaml);
//        }

        $output->writeln('CustomTypes synced to server successfully');
    }
}
