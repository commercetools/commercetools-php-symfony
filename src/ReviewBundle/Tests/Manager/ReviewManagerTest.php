<?php
/**
 */

namespace Commercetools\Symfony\ReviewBundle\Tests\Manager;

use Commercetools\Core\Model\Cart\LineItemDraftCollection;
use Commercetools\Core\Model\Review\Review;
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
            ->will(function ($args) { return $args[0]; })->shouldBeCalled();

        $dispatcher->dispatch(
            Argument::containingString(ReviewPostUpdateEvent::class),
            Argument::type(ReviewPostUpdateEvent::class)
        )->will(function ($args) { return $args[1]; })->shouldBeCalled();

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
            Argument::containingString(AbstractAction::class),
            Argument::type(ReviewUpdateEvent::class)
        )->will(function ($args) { return $args[1]; })->shouldBeCalled();
        $action = $this->prophesize(AbstractAction::class);

        $manager = new ReviewManager($repository->reveal(), $dispatcher->reveal());

        $actions = $manager->dispatch($review->reveal(), $action->reveal());
        $this->assertInstanceOf(AbstractAction::class, current($actions));
        $this->assertCount(1, $actions);
    }

    public function testCreateReview()
    {
        $repository = $this->prophesize(ReviewRepository::class);
        $lineItemsCollection = $this->prophesize(LineItemDraftCollection::class);
        $location = $this->prophesize(Location::class);

        $repository->createReview('en', 'EUR', $location->reveal(), $lineItemsCollection->reveal(), null, '123')->
        willReturn(Review::of())->shouldBeCalled();

        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $manager = new ReviewManager($repository->reveal(), $dispatcher->reveal());

        $review = $manager->createReview('en', 'EUR', $location->reveal(), $lineItemsCollection->reveal(), null, '123');
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
}
