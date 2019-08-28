<?php
/**
 *
 */

namespace Commercetools\Symfony\CustomerBundle\Tests\Security\Authentication\Provider;

use Commercetools\Core\Client\HttpClient;
use Commercetools\Core\Client\HttpRequest;
use Commercetools\Core\Client\OAuth\ClientCredentials;
use Commercetools\Core\Client\OAuth\PasswordFlowTokenProvider;
use Commercetools\Core\Client\OAuth\Token;
use Commercetools\Core\Config;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Cart\LineItem;
use Commercetools\Core\Model\Cart\LineItemCollection;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\Common\AddressCollection;
use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Request\Customers\CustomerLoginRequest;
use Commercetools\Core\Response\AbstractApiResponse;
use Commercetools\Core\Response\ResourceResponse;
use Commercetools\Symfony\CustomerBundle\Security\Authentication\Provider\AuthenticationProvider;
use Commercetools\Symfony\CustomerBundle\Security\User\User;
use Commercetools\Symfony\CustomerBundle\Security\User\UserProvider;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthenticationProviderTest extends TestCase
{
    private $client;
    private $userProvider;
    private $userChecker;
    private $logger;
    private $config;
    private $session;
    private $passwordFlowTokenProvider;

    public function setUp()
    {
        $this->client = $this->prophesize(HttpClient::class);
        $this->config = $this->prophesize(Config::class);
        $this->userProvider = $this->prophesize(UserProvider::class);
        $this->userChecker = $this->prophesize(UserChecker::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->session = $this->prophesize(Session::class);
        $this->passwordFlowTokenProvider = $this->prophesize(PasswordFlowTokenProvider::class);
    }

    private function getAuthenticationProvider()
    {
        return new TestAuthProv(
            $this->client->reveal(),
            $this->config->reveal(),
            $this->userProvider->reveal(),
            $this->userChecker->reveal(),
            'foo',
            true,
            $this->logger->reveal(),
            $this->passwordFlowTokenProvider->reveal()
        );
    }

    public function testCheckAuthentication()
    {
        $token = $this->prophesize(UsernamePasswordToken::class);
        $userInterface = $this->prophesize(UserInterface::class);
        $userInterface->getPassword()->willReturn('foo')->shouldBeCalledOnce();
        $token->getUser()->willReturn($userInterface->reveal())->shouldBeCalled();

        $user = $this->prophesize(User::class);
        $user->getPassword()->willReturn('foo')->shouldBeCalledOnce();

        $provider = $this->getAuthenticationProvider();
        $provider->checkAuthentication($user->reveal(), $token->reveal());
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\BadCredentialsException
     */
    public function testCheckAuthenticationWithDifferentPassword()
    {
        $token = $this->prophesize(UsernamePasswordToken::class);
        $userInterface = $this->prophesize(UserInterface::class);
        $userInterface->getPassword()->willReturn('foo')->shouldBeCalledOnce();
        $token->getUser()->willReturn($userInterface->reveal())->shouldBeCalled();

        $user = $this->prophesize(User::class);
        $user->getPassword()->willReturn('bar')->shouldBeCalledOnce();

        $provider = $this->getAuthenticationProvider();
        $provider->checkAuthentication($user->reveal(), $token->reveal());
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\BadCredentialsException
     */
    public function testCheckAuthenticationWithEmptyPassword()
    {
        $token = $this->prophesize(UsernamePasswordToken::class);
        $token->getUser()->willReturn(null)->shouldBeCalled();
        $token->getCredentials()->willReturn(null)->shouldBeCalled();
        $user = $this->prophesize(User::class);

        $provider = $this->getAuthenticationProvider();
        $provider->checkAuthentication($user->reveal(), $token->reveal());
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\BadCredentialsException
     */
    public function testCheckAuthenticationErrorResponse()
    {
        $token = $this->prophesize(UsernamePasswordToken::class);
        $token->getUser()->willReturn(null)->shouldBeCalled();
        $token->getCredentials()->willReturn('foo')->shouldBeCalled();

        $customer = Customer::of()->setEmail('foo@mail.com');

        $response = $this->prophesize(ResourceResponse::class);
        $response->toArray()->willReturn([]);
        $response->getContext()->willReturn(null);
        $response->isError()->willReturn(true);
        $response->getBody()->willReturn(json_encode($customer));

        $this->client->send(
            Argument::type(HttpRequest::class)
        )->willReturn($response->reveal())->shouldBeCalledOnce();

        $user = $this->prophesize(User::class);

        $provider = $this->getAuthenticationProvider();
        $provider->checkAuthentication($user->reveal(), $token->reveal());
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\BadCredentialsException
     */
    public function testCheckAuthenticationWrongUsername()
    {
        $token = $this->prophesize(UsernamePasswordToken::class);
        $token->getUser()->willReturn('user-1')->shouldBeCalled();
        $token->getCredentials()->willReturn('foo')->shouldBeCalled();

        $response = $this->prophesize(ResourceResponse::class);
        $response->toArray()->willReturn([
            'customer' => Customer::of()
                ->setEmail('user-2')
                ->toArray()
            ])->shouldBeCalled();

        $response->getContext()->willReturn(null);
        $response->isError()->willReturn(false);

        $this->client->send(
            Argument::type(HttpRequest::class)
        )->willReturn($response->reveal())->shouldBeCalled();

        $user = $this->prophesize(User::class);

        $provider = $this->getAuthenticationProvider();
        $provider->checkAuthentication($user->reveal(), $token->reveal());
    }

    public function testCheckAuthenticationSuccessResponse()
    {
        $token = $this->prophesize(UsernamePasswordToken::class);
        $token->getUser()->willReturn('USER@localhost')->shouldBeCalled();
        $token->getCredentials()->willReturn('foo')->shouldBeCalled();

        $oauthToken = $this->prophesize(Token::class);
        $oauthToken->getToken()->willReturn('token-1')->shouldBeCalled();
        $oauthToken->getRefreshToken()->willReturn('refresh-1')->shouldBeCalled();

        $customer = Customer::of()
            ->setId('id-1')
            ->setEmail('user@LOCALHOST')
            ->setPassword('foo')
            ->setAddresses(AddressCollection::of()->add(Address::of()->setId('address-1')->setCountry('DE')))
            ->setDefaultShippingAddressId('address-1')
            ->toArray();

        $response = $this->prophesize(ResourceResponse::class);
        $response->toArray()->willReturn($customer)->shouldBeCalled();
        $response->getContext()->willReturn(null)->shouldBeCalled();
        $response->isError()->willReturn(false)->shouldBeCalled();



        $this->passwordFlowTokenProvider->getTokenFor(
            Argument::is('USER@localhost'),
            Argument::is('foo')
        )->willReturn($oauthToken->reveal())->shouldBeCalledOnce();

        $this->client->send(Argument::type(HttpRequest::class))->willReturn($response->reveal())->shouldBeCalledTimes(2);

        $user = $this->prophesize(User::class);
        $user->setId('id-1')->shouldBeCalledOnce();
        $user->setCartId('id-1')->shouldBeCalledOnce();
        $user->setCartItemCount(0)->shouldBeCalledOnce();
        $user->setDefaultShippingAddress(Argument::that(function (Address $address) {
            static::assertSame('address-1', $address->getId());
            static::assertSame('DE', $address->getCountry());
            return true;
        }))->shouldBeCalled();
        $user->setAccessToken('token-1')->shouldBeCalledOnce();
        $user->setRefreshToken('refresh-1')->shouldBeCalledOnce();

        $provider = $this->getAuthenticationProvider();
        $provider->checkAuthentication($user->reveal(), $token->reveal());
    }

    public function testRetrieveUser()
    {
        $token = $this->prophesize(UsernamePasswordToken::class);
        $userInterface = $this->prophesize(UserInterface::class);
        $token->getUser()->willReturn($userInterface->reveal())->shouldBeCalled();

        $provider = $this->getAuthenticationProvider();
        $user = $provider->retrieveUser('foo', $token->reveal());

        $this->assertInstanceOf(UserInterface::class, $user);
    }

    public function testRetrieveUserByUsername()
    {
        $token = $this->prophesize(UsernamePasswordToken::class);
        $userInterface = $this->prophesize(UserInterface::class);

        $token->getUser()->willReturn(null)->shouldBeCalled();

        $this->userProvider->loadUserByUsername('foo')->willReturn($userInterface->reveal())->shouldBeCalled();

        $provider = $this->getAuthenticationProvider();
        $user = $provider->retrieveUser('foo', $token->reveal());

        $this->assertInstanceOf(UserInterface::class, $user);
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\AuthenticationServiceException
     */
    public function testRetrieveUserByUsernameWithError()
    {
        $token = $this->prophesize(UsernamePasswordToken::class);

        $token->getUser()->willReturn(null)->shouldBeCalled();

        $this->userProvider->loadUserByUsername('foo')->willReturn(null)->shouldBeCalled();

        $provider = $this->getAuthenticationProvider();
        $provider->retrieveUser('foo', $token->reveal());
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testRetrieveUserNotFound()
    {
        $token = $this->prophesize(UsernamePasswordToken::class);

        $token->getUser()->willReturn(null)->shouldBeCalled();

        $this->userProvider->loadUserByUsername('foo')->will(function () {
            throw new UsernameNotFoundException('foo');
        })->shouldBeCalled();

        $provider = $this->getAuthenticationProvider();
        $provider->retrieveUser('foo', $token->reveal());
    }
}

//phpcs:disable
class TestAuthProv extends AuthenticationProvider
{
    public function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        parent::checkAuthentication($user, $token);
    }

    public function retrieveUser($username, UsernamePasswordToken $token)
    {
        return parent::retrieveUser($username, $token);
    }
}
