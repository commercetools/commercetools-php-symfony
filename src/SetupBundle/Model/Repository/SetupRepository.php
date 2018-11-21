<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Model\Repository;


use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Project\Project;
use Commercetools\Symfony\CtpBundle\Logger\Logger;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Commercetools\Symfony\SetupBundle\Model\ConfigureProject;
use Commercetools\Symfony\SetupBundle\Model\ProjectUpdateBuilder;
use Psr\Cache\CacheItemPoolInterface;

class SetupRepository extends Repository
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * SetupRepository constructor.
     * @param $enableCache
     * @param CacheItemPoolInterface $cache
     * @param Client $client
     * @param MapperFactory $mapperFactory
     * @param Logger $logger
     */
    public function __construct($enableCache, CacheItemPoolInterface $cache, Client $client, MapperFactory $mapperFactory, Logger $logger)
    {
        parent::__construct($enableCache, $cache, $client, $mapperFactory);
        $this->logger = $logger;
    }

    /**
     * @return mixed
     */
    public function getProject()
    {
        $request = RequestBuilder::of()->project()->get();

        return $this->executeRequest($request);
    }

    /**
     * @param Project $project
     * @param array $actions
     * @return mixed
     */
    public function updateProject(Project $project, array $actions)
    {
        $updateRequest = RequestBuilder::of()->project()->update($project)->setActions($actions);

        return $this->executeRequest($updateRequest);
    }

    /**
     * @param array $config
     * @param Project $online
     * @return Project|null
     */
    public function applyConfiguration(array $config, Project $online)
    {
        return ConfigureProject::of()->update($config, $online, $this->getActionBuilder($online));
    }

    /**
     * @param Project $project
     * @return ProjectUpdateBuilder
     */
    public function getActionBuilder(Project $project)
    {
        return new ProjectUpdateBuilder($project, $this);
    }
}
