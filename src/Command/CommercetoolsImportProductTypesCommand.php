<?php

namespace Commercetools\Symfony\CtpBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CommercetoolsImportProductTypesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('commercetools:import:product-types')
            ->setDescription('...')
            ->addArgument('file', InputArgument::OPTIONAL, 'Category file to import')
            ->addOption('identifiedBy', null, InputOption::VALUE_OPTIONAL, 'Column to identify', 'key')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $importer = $this->getContainer()->get('commercetools.importer.product-types');
        $loader = $this->getContainer()->get('commercetools.importer.loader.json');

        $file = $input->getArgument('file');
        $identifiedBy = $input->getOption('identifiedBy');

        $data = $loader->load($file);

        $importer->setOptions($identifiedBy);
        $importer->import($data);
    }

}
