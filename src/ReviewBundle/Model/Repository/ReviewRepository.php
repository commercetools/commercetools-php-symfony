<?php
/**
 */

namespace Commercetools\Symfony\ReviewBundle\Model\Repository;

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Model\Review\Review;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Core\Client;
use Psr\Cache\CacheItemPoolInterface;
use Commercetools\Core\Request\ClientRequestInterface;

class ReviewRepository extends Repository
{
    public function __construct(
        $enableCache,
        CacheItemPoolInterface $cache,
        Client $client,
        MapperFactory $mapperFactory
    ) {
        parent::__construct($enableCache, $cache, $client, $mapperFactory);
    }

    public function executeRequest(ClientRequestInterface $request, $locale, QueryParams $params = null)
    {
        $client = $this->getClient();

        if(!is_null($params)){
            foreach ($params->getParams() as $param) {
                $request->addParamObject($param);
            }
        }

        $response = $request->executeWithClient($client);
        $reviews = $request->mapFromResponse(
            $response,
            $this->getMapper($locale)
        );

        return $reviews;
    }

    public function getReviewById($locale, $reviewId, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->reviews()->getById($reviewId);

        return $this->executeRequest($request, $locale, $params);
    }

    public function update(Review $review, array $actions, QueryParams $params = null)
    {
        $client = $this->getClient();
        $request = RequestBuilder::of()->reviews()->update($review)->setActions($actions);

        if(!is_null($params)){
            foreach ($params->getParams() as $param) {
                $request->addParamObject($param);
            }
        }

        $response = $request->executeWithClient($client);
        $review = $request->mapFromResponse($response);

        return $review;

    }
}
