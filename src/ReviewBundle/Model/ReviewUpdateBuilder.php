<?php
/**
 */

namespace Commercetools\Symfony\CustomerBundle\Model;


use Commercetools\Core\Builder\Update\CustomersActionBuilder;
use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CustomerBundle\Manager\ReviewManager;

class ReviewUpdateBuilder extends CustomersActionBuilder
{
    /**
     * @var ReviewManager
     */
    private $manager;

    /**
     * @var Customer
     */
    private $review;

    /**
     * ShoppingListUpdate constructor.
     * @param ReviewManager $manager
     * @param Customer $review
     */
    public function __construct(Customer $review, ReviewManager $manager)
    {
        $this->manager = $manager;
        $this->review = $review;
    }


    public function addAction(AbstractAction $action, $eventName = null)
    {
        $actions = $this->manager->dispatch($this->review, $action, $eventName);

        $this->setActions(array_merge($this->getActions(), $actions));

        return $this;
    }

    /**
     * @return Customer
     */
    public function flush()
    {
        return $this->manager->apply($this->review, $this->getActions());
    }
}
