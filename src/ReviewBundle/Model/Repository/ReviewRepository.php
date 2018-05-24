<?php
/**
 */

namespace Commercetools\Symfony\ReviewBundle\Model\Repository;

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Model\Review\Review;
use Commercetools\Core\Model\Review\ReviewDraft;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Core\Client;
use Psr\Cache\CacheItemPoolInterface;

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

    public function getReviewById($locale, $reviewId, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->reviews()->getById($reviewId);

        return $this->executeRequest($request, $locale, $params);
    }

    public function getReviewsByProductId($locale, $productId, QueryParams $params = null)
    {
        $predicate = 'target(id = "' . $productId . '") and target(typeId="product")';
        $request = RequestBuilder::of()->reviews()->query()->where($predicate)->sort('createdAt desc');

        return $this->executeRequest($request, $locale, $params);
    }

    public function createReviewForProduct($locale, $productReference, $customerReference, $text, $rating)
    {
        $reviewDraft = ReviewDraft::of()
            ->setText($text)
            ->setRating($rating)
            ->setCustomer($customerReference)
            ->setTarget($productReference)
            ->setLocale($locale);
        $request = RequestBuilder::of()->reviews()->create($reviewDraft);

        return $this->executeRequest($request, $locale);
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
