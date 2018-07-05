<?php

namespace Commercetools\Symfony\SetupBundle\Command;

use Commercetools\Core\Request\Project\Command\ProjectChangeCurrenciesAction;
use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommercetoolsProjectChangeCurrenciesCommand extends ContainerAwareCommand
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
            ->setName('commercetools:project-change-currencies')
            ->setDescription('Set the currencies of the project via the conf file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currencies = $this->getContainer()->getParameter('commercetools.currencies');

        $currenciesUnformatted = [];
        foreach ($currencies as $currency) {
            $currenciesUnformatted[] = $currency;
        }

        $actions[] = ProjectChangeCurrenciesAction::of()->setCurrencies($currenciesUnformatted);
        $project = $this->repository->updateProject($this->repository->getProject(), $actions);

        $output->writeln(sprintf('CTP response: %s', json_encode($project)));
        $output->writeln(sprintf('Conf file currencies %s', implode(', ', $currenciesUnformatted)));
    }
}
