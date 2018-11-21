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
use Twig\TwigFunction;

class ItemStateExtension extends AbstractExtension
{
    private $stateKeyResolver;

    public function __construct(StateKeyResolver $stateKeyResolver)
    {
        $this->stateKeyResolver = $stateKeyResolver;
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('wrapItemState', [$this, 'wrapItemState']),
        );
    }

    public function wrapItemState(Order $order, StateReference $itemStateReference, $item)
    {
        $wrappedItemState = ItemStateWrapper::create($order, $itemStateReference, $item);

        return $wrappedItemState;
    }
}
