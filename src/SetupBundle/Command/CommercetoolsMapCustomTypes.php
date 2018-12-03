<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Command;


use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class CommercetoolsMapCustomTypes extends ContainerAwareCommand
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
            ->setName('commercetools:map-custom-types')
            ->setDescription('Save custom types as a yaml file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $params = QueryParams::of()->add('limit', '500');

        $customTypes = $this->repository->getCustomTypes('en', $params);

        $yaml = Yaml::dump($customTypes->toArray(), 100, 4);
        $kernel = $this->getContainer()->get('kernel');

        $filename = $kernel->getProjectDir() . '/config/packages/' . $kernel->getEnvironment() . '/custom_types1.yaml';

        if ($kernel->getEnvironment() !== 'test') {
            file_put_contents($filename, $yaml);
        }

        $output->writeln('CustomTypes map file saved successfully at ' . $filename);
    }
}
