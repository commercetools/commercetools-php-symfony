<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Manager;


use Commercetools\Symfony\CartBundle\Model\Repository\ShippingMethodRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShippingMethodManager
{
    /**
     * @var ShippingMethodRepository
     */
    private $repository;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * CartManager constructor.
     * @param ShippingMethodRepository $repository
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(ShippingMethodRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }
}
