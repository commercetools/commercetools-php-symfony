<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model\CtpMarkingStore;

use Commercetools\Core\Model\Payment\Payment;

class CtpMarkingStorePaymentState extends CtpMarkingStore
{
    protected function getStateReference($subject)
    {
        if ($subject instanceof Payment) {
            $paymentStatus = $subject->getPaymentStatus();

            if (is_null($paymentStatus)) {
                return $this->initialState;
            }

            return $paymentStatus->getState();
        }

        return parent::getStateReference($subject);
    }
}
