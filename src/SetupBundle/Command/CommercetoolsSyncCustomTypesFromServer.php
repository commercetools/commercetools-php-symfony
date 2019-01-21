<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Command;


use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\SetupBundle\Model\ProcessCustomTypes;
use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class CommercetoolsSyncCustomTypesFromServer extends ContainerAwareCommand
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
            ->setName('commercetools:sync-custom-types-from-server')
            ->setDescription('Sync custom types from server to local')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $params = QueryParams::of()->add('limit', '500');
        $customTypes = $this->repository->getCustomTypes('en', $params);

        $types = ProcessCustomTypes::of()->getConfigArray($customTypes);
        $yaml = Yaml::dump($types, 100, 4);

        $kernel = $this->getContainer()->get('kernel');
        $filename = $kernel->getProjectDir() . '/config/packages/' . $kernel->getEnvironment() . '/custom_types.yaml';

        if ($kernel->getEnvironment() !== 'test') {
            file_put_contents($filename, $yaml);
        }

        $output->writeln('CustomTypes map file saved successfully at ' . $filename);
    }
}
