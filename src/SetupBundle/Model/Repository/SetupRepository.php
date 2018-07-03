<?php
/**
 *
 */

namespace Commercetools\Symfony\SetupBundle\Model\Repository;


use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Client;
use Commercetools\Core\Request\Project\Command\ProjectChangeCountriesAction;
use Commercetools\Core\Request\Project\Command\ProjectChangeLanguagesAction;
use Commercetools\Symfony\CtpBundle\Logger\Logger;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Psr\Cache\CacheItemPoolInterface;

class SetupRepository extends Repository
{
    private $logger;

    public function __construct($enableCache, CacheItemPoolInterface $cache, Client $client, MapperFactory $mapperFactory, Logger $logger)
    {
        parent::__construct($enableCache, $cache, $client, $mapperFactory);
        $this->logger = $logger;
    }

    public function getProjectInfo()
    {
        $request = RequestBuilder::of()->project()->get();

        return $this->executeRequest($request);
    }

    public function setCountries($countries)
    {
        $projectRequest = RequestBuilder::of()->project()->get();
        $project = $this->executeRequest($projectRequest);

        $actions[] = ProjectChangeCountriesAction::of()->setCountries($countries);
        $updateRequest = RequestBuilder::of()->project()->update($project)->setActions($actions);
        $project = $this->executeRequest($updateRequest);

        return $project;
    }

    public function setLanguages($languages)
    {
        $projectRequest = RequestBuilder::of()->project()->get();
        $project = $this->executeRequest($projectRequest);

        $actions[] = ProjectChangeLanguagesAction::of()->setLanguages($languages);
        $updateRequest = RequestBuilder::of()->project()->update($project)->setActions($actions);
        $project = $this->executeRequest($updateRequest);

        return $project;
    }
}
