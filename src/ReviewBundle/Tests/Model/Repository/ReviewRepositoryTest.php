<?php
/**
 *
 */

namespace Commercetools\Symfony\ReviewBundle\Tests\Model\Repository;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\Product\ProductReference;
use Commercetools\Core\Model\Review\Review;
use Commercetools\Core\Model\Review\ReviewDraft;
use Commercetools\Core\Request\Reviews\Command\ReviewSetKeyAction;
use Commercetools\Core\Request\Reviews\ReviewByIdGetRequest;
use Commercetools\Core\Request\Reviews\ReviewCreateRequest;
use Commercetools\Core\Request\Reviews\ReviewQueryRequest;
use Commercetools\Core\Request\Reviews\ReviewUpdateRequest;
use Commercetools\Core\Response\ResourceResponse;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Commercetools\Symfony\ReviewBundle\Model\Repository\ReviewRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Cache\Tests\Fixtures\ExternalAdapter;

class ReviewRepositoryTest extends TestCase
{
    private $cache;
    private $mapperFactory;
    private $response;
    private $client;

    protected function setUp()
    {
        $this->cache = new ExternalAdapter();
        $this->mapperFactory = $this->prophesize(MapperFactory::class);

        $this->response = $this->prophesize(ResourceResponse::class);
        $this->response->toArray()->willReturn([]);
        $this->response->getContext()->willReturn(null);
        $this->response->isError()->willReturn(false);

        $this->client = $this->prophesize(Client::class);
    }

    private function getReviewRepository()
    {
        return new ReviewRepository(
            false,
            $this->cache,
            $this->client->reveal(),
            $this->mapperFactory->reveal()
        );
    }

    public function testGetReviewById()
    {
        $this->client->execute(
            Argument::that(function (ReviewByIdGetRequest $request) {
                static::assertSame('review-1', $request->getId());
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $reviewRepository = $this->getReviewRepository();
        $reviewRepository->getReviewById('en', 'review-1');
    }

    public function testGetReviewsByProductId()
    {
        $this->client->execute(
            Argument::that(function (ReviewQueryRequest $request) {
                static::assertSame(
                    'reviews?sort=createdAt+desc&where=target%28id+%3D+%22product-1%22%29+and+target%28typeId%3D%22product%22%29',
                    (string)$request->httpRequest()->getUri()
                );
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $params = new QueryParams();
        $params->add('sort', 'createdAt desc');

        $reviewRepository = $this->getReviewRepository();
        $reviewRepository->getReviewsByProductId('en', 'product-1', $params);
    }

    public function testGetReviewForUser()
    {
        $this->client->execute(
            Argument::that(function (ReviewQueryRequest $request) {
                static::assertSame(
                    'reviews?where=id+%3D+%22review-id%22+and+customer%28id+%3D+%22user-1%22%29+and+target%28typeId%3D%22product%22%29',
                    (string)$request->httpRequest()->getUri()
                );
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $reviewRepository = $this->getReviewRepository();
        $reviewRepository->getReviewForUser('en', 'user-1', 'review-id');
    }

    public function testUpdate()
    {
        $this->client->execute(
            Argument::that(function (ReviewUpdateRequest $request) {
                static::assertSame(Review::class, $request->getResultClass());
                static::assertSame('review-1', $request->getId());
                static::assertSame(1, $request->getVersion());

                $action = current($request->getActions());
                static::assertInstanceOf(ReviewSetKeyAction::class, $action);
                static::assertSame('foobar', $action->getKey());

                static::assertSame(
                    'reviews/review-1?foo=bar',
                    (string)$request->httpRequest()->getUri()
                );

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $actions = [
            ReviewSetKeyAction::of()->setKey('foobar')
        ];

        $params = new QueryParams();
        $params->add('foo', 'bar');

        $reviewRepository = $this->getReviewRepository();
        $reviewRepository->update(Review::of()->setId('review-1')->setVersion(1), $actions, $params);
    }

    public function testCreateReviewForProduct()
    {
        $this->client->execute(
            Argument::that(function (ReviewCreateRequest $request) {
                static::assertInstanceOf(ReviewDraft::class, $request->getObject());
                static::assertInstanceOf(ProductReference::class, $request->getObject()->getTarget());
                static::assertInstanceOf(CustomerReference::class, $request->getObject()->getCustomer());
                static::assertSame('product-1', $request->getObject()->getTarget()->getId());
                static::assertSame('customer-1', $request->getObject()->getCustomer()->getId());
                static::assertSame(2, $request->getObject()->getRating());
                static::assertSame('foo', $request->getObject()->getText());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $product = ProductReference::ofId('product-1');
        $customer = CustomerReference::ofId('customer-1');

        $reviewRepository = $this->getReviewRepository();
        $reviewRepository->createReviewForProduct('en', $product, $customer, 'foo', 2);
    }
}
