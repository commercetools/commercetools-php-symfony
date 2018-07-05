<?php

namespace Commercetools\Symfony\SetupBundle\Command;

use Commercetools\Core\Request\Project\Command\ProjectChangeMessagesEnabledAction;
use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommercetoolsProjectChangeMessagesEnabledCommand extends ContainerAwareCommand
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
            ->setName('commercetools:project-change-messages-enabled')
            ->setDescription('Set the creation of messages on the project via the conf file (true/false)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $messages = $this->getContainer()->getParameter('commercetools.project_settings.messages');

        if (is_string($messages)) {
            $messages = ($messages == "true");
        }

        $actions[] = ProjectChangeMessagesEnabledAction::of()->setMessagesEnabled($messages);
        $project = $this->repository->updateProject($this->repository->getProject(), $actions);

        $output->writeln(sprintf('CTP response: %s', json_encode($project)));
        $output->writeln(sprintf('Conf file messages: %s', ($messages ? 'enabled' : 'disabled')));
    }
}
