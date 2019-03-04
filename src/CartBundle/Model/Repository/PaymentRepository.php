<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Model\Repository;


use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\CustomField\CustomFieldObjectDraft;
use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Model\Payment\PaymentCollection;
use Commercetools\Core\Model\Payment\PaymentDraft;
use Commercetools\Core\Model\Payment\PaymentStatus;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Model\Repository;

class PaymentRepository extends Repository
{
    /**
     * @param string $locale
     * @param string $paymentId
     * @return Payment
     */
    public function getPaymentById($locale, $paymentId)
    {
        $request = RequestBuilder::of()->payments()->getById($paymentId);

        return $this->executeRequest($request, $locale);
    }

    /**
     * @param string $locale
     * @param string $paymentId
     * @param CustomerReference|null $customer
     * @param string|null $anonymousId
     * @return Payment
     */
    public function getPayment($locale, $paymentId, CustomerReference $customer = null, $anonymousId = null)
    {
        $request = RequestBuilder::of()->payments()->query();

        $predicate = 'id = "' . $paymentId . '"';

        if (!is_null($customer)) {
            $predicate .= ' and customer(id = "' . $customer->getId() . '")';
        } elseif (!is_null($anonymousId)) {
            $predicate .= ' and anonymousId = "' . $anonymousId . '"';
        }

        $request->where($predicate);

        $payments = $this->executeRequest($request, $locale);

        return $payments->current();
    }

    /**
     * @param string $locale
     * @param array $payments
     * @return PaymentCollection
     */
    public function getPaymentsBulk($locale, array $payments)
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
     * @param string $locale
     * @param Money $amountPlanned
     * @param CustomerReference|null $customerReference
     * @param string|null $anonymousId
     * @param PaymentStatus|null $paymentStatus
     * @param CustomFieldObjectDraft|null $customFieldObject
     * @return Payment
     */
    public function createPayment(
        $locale,
        Money $amountPlanned,
        CustomerReference $customerReference = null,
        $anonymousId = null,
        PaymentStatus $paymentStatus = null,
        CustomFieldObjectDraft $customFieldObject = null
    ) {
        $paymentDraft = PaymentDraft::of()->setAmountPlanned($amountPlanned);

        if (!is_null($paymentStatus)) {
            $paymentDraft->setPaymentStatus($paymentStatus);
        }

        if (!is_null($customerReference)) {
            $paymentDraft->setCustomer($customerReference);
        } else if (!is_null($anonymousId)) {
            $paymentDraft->setAnonymousId($anonymousId);
        }

        if (!is_null($customFieldObject)) {
            $paymentDraft->setCustom($customFieldObject);
        }

        $request = RequestBuilder::of()->payments()->create($paymentDraft);

        return $this->executeRequest($request, $locale);
    }
}
