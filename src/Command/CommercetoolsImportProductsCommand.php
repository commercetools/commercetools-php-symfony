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
            ->addOption('identifiedBy', null, InputOption::VALUE_OPTIONAL, 'Column to identify', 'id')
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

        $loader->setCsvControl($delimiter, $enclosure, $escape);
        $data = $loader->load($file);
        $importer->setOptions($identifiedByColumn);
        $importer->import($data);
    }

}
