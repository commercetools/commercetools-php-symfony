<?php

namespace Commercetools\Symfony\CtpBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CommercetoolsImportProductsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('commercetools:import:products')
            ->setDescription('...')
            ->addArgument('file', InputArgument::OPTIONAL, 'Products file to import')
            ->addOption('delimiter', null, InputOption::VALUE_OPTIONAL, 'Column delimiter', ';')
            ->addOption('enclosure', null, InputOption::VALUE_OPTIONAL, 'Column enclosure', '"')
            ->addOption('escape', null, InputOption::VALUE_OPTIONAL, 'Column escape', '\\')
            ->addOption('identifiedBy', null, InputOption::VALUE_OPTIONAL, 'Column to query the products by', 'id')
            ->addOption('packedRequests', null, InputOption::VALUE_OPTIONAL, 'number of queries to send together', 10)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $importer = $this->getContainer()->get('commercetools.importer.product');
        $loader = $this->getContainer()->get('commercetools.importer.loader.csv');

        $file = $input->getArgument('file');

        $enclosure = $input->getOption('enclosure');
        $delimiter = $input->getOption('delimiter');
        $escape = $input->getOption('escape');
        $identifiedByColumn = $input->getOption('identifiedBy');
        $packedRequests = $input->getOption('packedRequests');

        $loader->setCsvControl($delimiter, $enclosure, $escape);
        $data = $loader->load($file);
        $importer->setOptions($identifiedByColumn, $packedRequests);
        $importer->import($data);
    }

}
