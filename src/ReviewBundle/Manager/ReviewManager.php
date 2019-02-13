<?php
/**
 */

namespace Commercetools\Symfony\ReviewBundle\Manager;

use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\Product\ProductReference;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Core\Model\Review\Review;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\ReviewBundle\Event\ReviewUpdateEvent;
use Commercetools\Symfony\ReviewBundle\Event\ReviewPostUpdateEvent;
use Commercetools\Symfony\ReviewBundle\Model\Repository\ReviewRepository;
use Commercetools\Symfony\ReviewBundle\Model\ReviewUpdateBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReviewManager
{
    /**
     * @var ReviewRepository
     */
    private $repository;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * ReviewManager constructor.
     * @param ReviewRepository $repository
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(ReviewRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param $locale
     * @param $reviewId
     * @param QueryParams|null $params
     * @return mixed
     */
    public function getById($locale, $reviewId, QueryParams $params = null)
    {
        return $this->repository->getReviewById($locale, $reviewId, $params);
    }

    public function getByProductId($locale, $productId, QueryParams $params = null)
    {
        return $this->repository->getReviewsByProductId($locale, $productId, $params);
    }

    public function getReviewForUser($locale, $userId, $reviewId, QueryParams $params = null)
    {
        return $this->repository->getReviewForUser($locale, $userId, $reviewId, $params);
    }

    /**
     * @param $locale
     * @param ProductReference $productReference
     * @param CustomerReference|null $customer
     * @param null $text
     * @param null $rating
     * @return mixed
     */
    public function createForProduct($locale, ProductReference $productReference, CustomerReference $customer = null, $text = null, $rating = null)
    {
        return $this->repository->createReviewForProduct($locale, $productReference, $customer, $text, $rating);
    }

    /**
     * @param Review $review
     * @return ReviewUpdateBuilder
     */
    public function update(Review $review)
    {
        return new ReviewUpdateBuilder($review, $this);
    }

    public function dispatch(Review $review, AbstractAction $action, $eventName = null)
    {
        $eventName = is_null($eventName) ? get_class($action) : $eventName;

        $event = new ReviewUpdateEvent($review, $action);
        $event = $this->dispatcher->dispatch($eventName, $event);

        return $event->getActions();
    }

    /**
     * @param Review $review
     * @param array $actions
     * @return Review
     */
    public function apply(Review $review, array $actions)
    {
        $review = $this->repository->update($review, $actions);

        return $this->dispatchPostUpdate($review, $actions);
    }

    public function dispatchPostUpdate(Review $review, array $actions)
    {
        $event = new ReviewPostUpdateEvent($review, $actions);
        $event = $this->dispatcher->dispatch(ReviewPostUpdateEvent::class, $event);

        return $event->getReview();
    }
}
