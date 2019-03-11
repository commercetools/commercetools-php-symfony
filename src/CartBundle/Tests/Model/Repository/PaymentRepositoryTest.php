<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Model\Repository;

use Commercetools\Core\Client;
use Commercetools\Core\Error\InvalidArgumentException;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Model\Payment\PaymentDraft;
use Commercetools\Core\Model\Payment\PaymentStatus;
use Commercetools\Core\Request\Payments\Command\PaymentSetKeyAction;
use Commercetools\Core\Request\Payments\PaymentByIdGetRequest;
use Commercetools\Core\Request\Payments\PaymentCreateRequest;
use Commercetools\Core\Request\Payments\PaymentQueryRequest;
use Commercetools\Core\Request\Payments\PaymentUpdateRequest;
use Commercetools\Core\Response\ResourceResponse;
use Commercetools\Symfony\CartBundle\Model\Repository\PaymentRepository;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Cache\Tests\Fixtures\ExternalAdapter;

class PaymentRepositoryTest extends TestCase
{
    private $cache;
    private $mapperFactory;
    private $response;
    private $client;

    protected function setUp()
    {
        $this->cache = new ExternalAdapter();
        $this->mapperFactory = $this->prophesize(MapperFactory::class);

        $this->response = $this->prophesize(ResourceResponse::class);
        $this->response->toArray()->willReturn([]);
        $this->response->getContext()->willReturn(null);
        $this->response->isError()->willReturn(false);

        $this->client = $this->prophesize(Client::class);
    }

    private function getPaymentRepository()
    {
        return new PaymentRepository(
            false,
            $this->cache,
            $this->client->reveal(),
            $this->mapperFactory->reveal()
        );
    }

    public function testGetPaymentById()
    {
        $this->client->execute(
            Argument::that(function (PaymentByIdGetRequest $request) {
                static::assertSame('payment-1', $request->getId());
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $paymentRepository = $this->getPaymentRepository();
        $paymentRepository->getPaymentById('en', 'payment-1');
    }

    public function testGetPaymentForAnonymous()
    {
        $this->client->execute(
            Argument::that(function (PaymentQueryRequest $request) {
                static::assertSame(
                    'payments?where=id+%3D+%22payment-1%22+and+anonymousId+%3D+%22anon-1%22',
                    (string)$request->httpRequest()->getUri()
                );
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $paymentRepository = $this->getPaymentRepository();
        $paymentRepository->getPayment('en', 'payment-1', null, 'anon-1');
    }

    public function testGetPaymentForCustomer()
    {
        $this->client->execute(
            Argument::that(function (PaymentQueryRequest $request) {
                static::assertSame(
                    'payments?where=id+%3D+%22payment-1%22+and+customer%28id+%3D+%22user-1%22%29',
                    (string)$request->httpRequest()->getUri()
                );
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $user = $this->prophesize(CustomerReference::class);
        $user->getId()->willReturn('user-1')->shouldBeCalledOnce();

        $paymentRepository = $this->getPaymentRepository();
        $paymentRepository->getPayment('en', 'payment-1', $user->reveal());
    }

    public function testGetPaymentWithoutUser()
    {
        $this->client->execute(
            Argument::that(function (PaymentQueryRequest $request) {
                static::assertStringStartsWith('payments', (string)$request->httpRequest()->getUri());
                static::assertContains('where=', (string)$request->httpRequest()->getUri());
                static::assertContains('id+%3D+%22payment-1%22', (string)$request->httpRequest()->getUri());
                static::assertNotContains('customer', (string)$request->httpRequest()->getUri());
                static::assertNotContains('anonymousId', (string)$request->httpRequest()->getUri());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $paymentRepository = $this->getPaymentRepository();
        $paymentRepository->getPayment('en', 'payment-1');
    }

    public function testGetMultiplePayments()
    {
        $this->client->execute(
            Argument::that(function (PaymentQueryRequest $request) {
                static::assertSame(
                    'payments?where=id+in+%28%22payment-1%22%2C+%22payment-2%22%2C+%22payment-7%22%29',
                    (string)$request->httpRequest()->getUri()
                );
                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $payments = ['payment-1', 'payment-2', 'payment-7'];

        $paymentRepository = $this->getPaymentRepository();
        $paymentRepository->getPaymentsBulk('en', $payments);
    }

    public function testUpdatePayment()
    {
        $this->client->execute(
            Argument::that(function (PaymentUpdateRequest $request) {
                static::assertSame(Payment::class, $request->getResultClass());
                static::assertSame('payment-1', $request->getId());
                static::assertSame(1, $request->getVersion());

                $action = current($request->getActions());
                static::assertInstanceOf(PaymentSetKeyAction::class, $action);
                static::assertSame('foobar', $action->getKey());

                static::assertSame(
                    'payments/payment-1?foo=bar',
                    (string)$request->httpRequest()->getUri()
                );

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $actions = [
            PaymentSetKeyAction::of()->setKey('foobar')
        ];

        $params = new QueryParams();
        $params->add('foo', 'bar');

        $paymentRepository = $this->getPaymentRepository();
        $paymentRepository->update(Payment::of()->setId('payment-1')->setVersion(1), $actions, $params);
    }

    public function testCreatePaymentForAnonymous()
    {
        $this->client->execute(
            Argument::that(function (PaymentCreateRequest $request) {
                static::assertInstanceOf(PaymentDraft::class, $request->getObject());
                static::assertInstanceOf(Money::class, $request->getObject()->getAmountPlanned());
                static::assertInstanceOf(PaymentStatus::class, $request->getObject()->getPaymentStatus());
                static::assertSame('anon-1', $request->getObject()->getAnonymousId());
                static::assertNull($request->getObject()->getCustomer());
                static::assertSame(100, $request->getObject()->getAmountPlanned()->getCentAmount());
                static::assertSame('EUR', $request->getObject()->getAmountPlanned()->getCurrencyCode());
                static::assertSame('foo', $request->getObject()->getPaymentStatus()->getInterfaceText());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $paymentStatus = PaymentStatus::of()->setInterfaceText('foo');
        $money =  Money::of()->setCurrencyCode('EUR')->setCentAmount(100);

        $paymentRepository = $this->getPaymentRepository();
        $paymentRepository->createPayment('en', $money, null, 'anon-1', $paymentStatus);
    }

    public function testCreatePaymentForCustomer()
    {
        $this->client->execute(
            Argument::that(function (PaymentCreateRequest $request) {
                static::assertInstanceOf(PaymentDraft::class, $request->getObject());
                static::assertInstanceOf(Money::class, $request->getObject()->getAmountPlanned());
                static::assertNull($request->getObject()->getPaymentStatus());
                static::assertSame('user-1', $request->getObject()->getCustomer()->getId());
                static::assertNull($request->getObject()->getAnonymousId());
                static::assertSame('EUR', $request->getObject()->getAmountPlanned()->getCurrencyCode());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $customer = CustomerReference::ofId('user-1');
        $money =  Money::of()->setCurrencyCode('EUR');

        $paymentRepository = $this->getPaymentRepository();
        $paymentRepository->createPayment('en', $money, $customer);
    }

    public function testCreateWithoutUser()
    {
        $this->client->execute(
            Argument::that(function (PaymentCreateRequest $request) {
                static::assertInstanceOf(PaymentDraft::class, $request->getObject());
                static::assertInstanceOf(Money::class, $request->getObject()->getAmountPlanned());
                static::assertNull($request->getObject()->getPaymentStatus());
                static::assertNull($request->getObject()->getCustomer());
                static::assertNull($request->getObject()->getAnonymousId());
                static::assertSame('EUR', $request->getObject()->getAmountPlanned()->getCurrencyCode());

                return true;
            }),
            Argument::is(null)
        )->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $paymentRepository = $this->getPaymentRepository();
        $paymentRepository->createPayment('en', Money::of()->setCurrencyCode('EUR'));
    }
}
