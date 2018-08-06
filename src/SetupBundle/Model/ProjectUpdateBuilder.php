<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Model;

use Commercetools\Core\Builder\Update\ProjectActionBuilder;
use Commercetools\Core\Model\Project\Project;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\SetupBundle\Model\Repository\SetupRepository;

class ProjectUpdateBuilder extends ProjectActionBuilder
{
    private $project;
    private $repository;

    public function __construct(Project $project, SetupRepository $repository)
    {
        $this->project = $project;
        $this->repository =$repository;
    }

    public function addAction(AbstractAction $action, $eventName = null)
    {
        $this->setActions(array_merge($this->getActions(), [$action]));

        return $this;
    }

    /**
     * @return Project
     */
    public function flush()
    {
        return $this->repository->updateProject($this->project, $this->getActions());
    }

}
