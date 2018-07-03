<?php

namespace Commercetools\Symfony\SetupBundle\Command;

use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommercetoolsProjectSetCountriesCommand extends ContainerAwareCommand
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
            ->setName('commercetools:project-set-countries')
            ->setDescription('Set the countries of the project via the conf file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $countries = $this->getContainer()->getParameter('commercetools.project_settings.countries');

        $project = $this->repository->setCountries($countries);

        $output->writeln(sprintf('CTP response: %s', json_encode($project)));
        $output->writeln(sprintf('Conf file countries %s', implode(', ', $countries)));
    }
}
