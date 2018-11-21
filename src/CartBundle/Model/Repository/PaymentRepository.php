<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Model\Repository;


use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Error\InvalidArgumentException;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Model\Payment\PaymentCollection;
use Commercetools\Core\Model\Payment\PaymentDraft;
use Commercetools\Core\Model\Payment\PaymentStatus;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Model\Repository;

class PaymentRepository extends Repository
{
    /**
     * @param $locale
     * @param $paymentId
     * @return Payment
     */
    public function getPaymentById($locale, $paymentId)
    {
        $request = RequestBuilder::of()->payments()->getById($paymentId);

        return $this->executeRequest($request, $locale);
    }

    /**
     * @param $locale
     * @param $paymentId
     * @param CustomerReference|null $customer
     * @param $anonymousId
     * @return PaymentCollection
     */
    public function getPaymentForUser($locale, $paymentId, CustomerReference $customer = null, $anonymousId = null)
    {
        $request = RequestBuilder::of()->payments()->query();

        if (!is_null($customer)) {
            $request->where('id = "' . $paymentId . '" and customer(id = "' . $customer->getId() . '")');
        } elseif (!is_null($anonymousId)) {
            $request->where('id = "' . $paymentId . '" and anonymousId = "' . $anonymousId . '"');
        } else {
            throw new InvalidArgumentException('At least one of CustomerReference or AnonymousId should be present');
        }

        $payments = $this->executeRequest($request, $locale);

        return $payments->current();
    }

    /**
     * @param $locale
     * @param array $payments
     * @return array|mixed
     */
    public function getPaymentsForOrder($locale, array $payments)
    {
        $request = RequestBuilder::of()->payments()->query();

        $request->where('id in ("'.implode('", "', $payments).'")');

        $payments = $this->executeRequest($request, $locale);

        return $payments;
    }

    /**
     * @param Payment $payment
     * @param array $actions
     * @param QueryParams|null $params
     * @return Payment
     */
    public function update(Payment $payment, array $actions, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->payments()->update($payment)->setActions($actions);

        if(!is_null($params)){
            foreach ($params->getParams() as $param) {
                $request->addParamObject($param);
            }
        }

        return $this->executeRequest($request);
    }

    /**
     * @param $locale
     * @param Money $amountPlanned
     * @param CustomerReference|null $customerReference
     * @param null $anonymousId
     * @param PaymentStatus|null $paymentStatus
     * @return Payment
     */
    public function createPayment(
        $locale,
        Money $amountPlanned,
        CustomerReference $customerReference = null,
        $anonymousId = null,
        PaymentStatus $paymentStatus = null
    ) {
        $paymentDraft = PaymentDraft::of()->setAmountPlanned($amountPlanned);

        if (!is_null($paymentStatus)) {
            $paymentDraft->setPaymentStatus($paymentStatus);
        }

        if (!is_null($customerReference)) {
            $paymentDraft->setCustomer($customerReference);
        } else if (!is_null($anonymousId)) {
            $paymentDraft->setAnonymousId($anonymousId);
        } else {
            throw new InvalidArgumentException('At least one of CustomerReference or AnonymousId should be present');
        }

        $request = RequestBuilder::of()->payments()->create($paymentDraft);

        return $this->executeRequest($request, $locale);
    }
}
