<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 06/02/17
 * Time: 15:33
 */

namespace Commercetools\Symfony\CtpBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CommercetoolsImportStatesCommand  extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('commercetools:import:states')
            ->setDescription('...')
            ->addArgument('file', InputArgument::OPTIONAL, 'states file to import')
            ->addOption('delimiter', null, InputOption::VALUE_OPTIONAL, 'Column delimiter', ';')
            ->addOption('enclosure', null, InputOption::VALUE_OPTIONAL, 'Column enclosure', '"')
            ->addOption('escape', null, InputOption::VALUE_OPTIONAL, 'Column escape', '\\')
            ->addOption('identifiedBy', null, InputOption::VALUE_OPTIONAL, 'Column to query the states by', 'key')
            ->addOption('packedRequests', null, InputOption::VALUE_OPTIONAL, 'number of queries to send together', 10)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $importer = $this->getContainer()->get('commercetools.importer.state');
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
