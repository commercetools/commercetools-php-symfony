<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Model\Repository;


use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Channel\Channel;
use Commercetools\Core\Model\Project\Project;
use Commercetools\Symfony\CtpBundle\Logger\Logger;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Commercetools\Symfony\SetupBundle\Model\ProjectUpdateBuilder;
use Psr\Cache\CacheItemPoolInterface;

class SetupRepository extends Repository
{
    private $logger;

    public function __construct($enableCache, CacheItemPoolInterface $cache, Client $client, MapperFactory $mapperFactory, Logger $logger)
    {
        parent::__construct($enableCache, $cache, $client, $mapperFactory);
        $this->logger = $logger;
    }

    public function getProject()
    {
        $request = RequestBuilder::of()->project()->get();

        return $this->executeRequest($request);
    }

    public function updateProject(Project $project, array $actions)
    {
        $updateRequest = RequestBuilder::of()->project()->update($project)->setActions($actions);

        return $this->executeRequest($updateRequest);
    }

    public function getChannels($condition = null)
    {
        $request = RequestBuilder::of()->channels()->query();

        if (!is_null($condition)) {
            $request->where($condition);
        }

        return $this->executeRequest($request);
    }

    public function createChannel($channelDraft)
    {
        $request = RequestBuilder::of()->channels()->create($channelDraft);

        return $this->executeRequest($request);
    }

    public function updateChannel(Channel $channel, array $actions)
    {
        $updateRequest = RequestBuilder::of()->channels()->update($channel)->setActions($actions);

        return $this->executeRequest($updateRequest);
    }

    public function deleteChannel(Channel $channel)
    {
        $deleteRequest = RequestBuilder::of()->channels()->delete($channel);

        return $this->executeRequest($deleteRequest);
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
