<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Manager;

use Commercetools\Core\Model\ShippingMethod\ShippingMethod;
use Commercetools\Core\Model\ShippingMethod\ShippingMethodCollection;
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

    /**
     * @param string $locale
     * @param Location $location
     * @param string|null $currency
     * @return ShippingMethodCollection
     */
    public function getShippingMethodsByLocation($locale, Location $location, $currency = null)
    {
        return $this->repository->getShippingMethodsByLocation($locale, $location, $currency);
    }

    /**
     * @param string $locale
     * @param string $id
     * @return ShippingMethod
     */
    public function getShippingMethodById($locale, $id)
    {
        return $this->repository->getShippingMethodById($locale, $id);
    }

    /**
     * @param string $locale
     * @param string $cartId
     * @return ShippingMethodCollection
     */
    public function getShippingMethodsByCart($locale, $cartId)
    {
        return $this->repository->getShippingMethodsByCart($locale, $cartId);
    }
}
