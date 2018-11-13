<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Extension;


use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Symfony\StateBundle\Cache\StateKeyResolver;
use Commercetools\Symfony\StateBundle\Model\ItemStateWrapper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class ItemStateExtension extends AbstractExtension
{
    private $cacheHelper;

    public function __construct(StateKeyResolver $cacheHelper)
    {
        $this->cacheHelper = $cacheHelper;
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('wrapItemState', [$this, 'wrapItemState']),
        );
    }

    public function getFilters()
    {
        return array(
            new TwigFilter('resolveStateId', [$this, 'resolveStateId']),
        );
    }

    public function wrapItemState(Order $order, StateReference $itemStateReference, $item)
    {
        $wrappedItemState = ItemStateWrapper::create($order, $itemStateReference, $item);

        return $wrappedItemState;
    }

    public function resolveStateId($id)
    {
        return $this->cacheHelper->resolve($id);
    }

}
