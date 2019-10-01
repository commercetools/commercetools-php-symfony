<?php
/**
 */

namespace Commercetools\Symfony\ReviewBundle\Tests\Manager;

use Commercetools\Core\Model\Cart\LineItemDraftCollection;
use Commercetools\Core\Model\Product\ProductReference;
use Commercetools\Core\Model\Review\Review;
use Commercetools\Core\Model\Review\ReviewCollection;
use Commercetools\Core\Model\Zone\Location;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\ReviewBundle\Event\ReviewPostUpdateEvent;
use Commercetools\Symfony\ReviewBundle\Event\ReviewUpdateEvent;
use Commercetools\Symfony\ReviewBundle\Manager\ReviewManager;
use Commercetools\Symfony\ReviewBundle\Model\Repository\ReviewRepository;
use Commercetools\Symfony\ReviewBundle\Model\ReviewUpdateBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReviewManagerTest extends TestCase
{
    public function testApply()
    {
        $review = $this->prophesize(Review::class);
        $repository = $this->prophesize(ReviewRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->update($review, Argument::type('array'))
            ->will(function ($args) {
                return $args[0];
            })->shouldBeCalled();

        $dispatcher->dispatch(
            Argument::type(ReviewPostUpdateEvent::class)
        )->will(function ($args) {
            return $args[0];
        })->shouldBeCalled();

        $manager = new ReviewManager($repository->reveal(), $dispatcher->reveal());
        $review = $manager->apply($review->reveal(), []);

        $this->assertInstanceOf(Review::class, $review);
    }

    public function testDispatch()
    {
        $review = $this->prophesize(Review::class);
        $repository = $this->prophesize(ReviewRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $dispatcher->dispatch(
            Argument::type(ReviewUpdateEvent::class),
            Argument::containingString(AbstractAction::class)
        )->will(function ($args) {
            return $args[0];
        })->shouldBeCalled();
        $action = $this->prophesize(AbstractAction::class);

        $manager = new ReviewManager($repository->reveal(), $dispatcher->reveal());

        $actions = $manager->dispatch($review->reveal(), $action->reveal());
        $this->assertInstanceOf(AbstractAction::class, current($actions));
        $this->assertCount(1, $actions);
    }

    public function testCreateReview()
    {
        $repository = $this->prophesize(ReviewRepository::class);
        $productReference = $this->prophesize(ProductReference::class);

        $repository->createReviewForProduct('en', $productReference->reveal(), null, 'foo', '2')
            ->willReturn(Review::of())->shouldBeCalled();

        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $manager = new ReviewManager($repository->reveal(), $dispatcher->reveal());

        $review = $manager->createForProduct('en', $productReference->reveal(), null, 'foo', '2');
        $this->assertInstanceOf(Review::class, $review);
    }

    public function testUpdate()
    {
        $review = $this->prophesize(Review::class);
        $repository = $this->prophesize(ReviewRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $manager = new ReviewManager($repository->reveal(), $dispatcher->reveal());
        $this->assertInstanceOf(ReviewUpdateBuilder::class, $manager->update($review->reveal()));
    }

    public function testGetReviewById()
    {
        $repository = $this->prophesize(ReviewRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->getReviewById('en', '123', null)
            ->willReturn(Review::of())->shouldBeCalled();

        $manager = new ReviewManager($repository->reveal(), $dispatcher->reveal());
        $review = $manager->getById('en', '123');

        $this->assertInstanceOf(Review::class, $review);
    }

    public function testGetReviewsByProductId()
    {
        $repository = $this->prophesize(ReviewRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->getReviewsByProductId('en', '123', null)
            ->willReturn(ReviewCollection::of())->shouldBeCalled();

        $manager = new ReviewManager($repository->reveal(), $dispatcher->reveal());
        $reviews = $manager->getByProductId('en', '123');

        $this->assertInstanceOf(ReviewCollection::class, $reviews);
    }

    public function testGetReviewForUser()
    {
        $repository = $this->prophesize(ReviewRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->getReviewForUser('en', 'user-1', 'review-1', null)
            ->willReturn(ReviewCollection::of())->shouldBeCalled();

        $manager = new ReviewManager($repository->reveal(), $dispatcher->reveal());
        $reviews = $manager->getReviewForUser('en', 'user-1', 'review-1');

        $this->assertInstanceOf(ReviewCollection::class, $reviews);
    }
}
