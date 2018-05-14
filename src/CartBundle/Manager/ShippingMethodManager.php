<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Manager;


use Commercetools\Core\Model\Zone\Location;
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

    public function getShippingMethodsByLocation($locale, Location $location, $currency = null)
    {
        return $this->repository->getShippingMethodsByLocation($locale, $location, $currency);
    }

    public function getShippingMethodByCart($locale, $cartId)
    {
        return $this->repository->getShippingMethodByCart($locale, $cartId);
    }
}
