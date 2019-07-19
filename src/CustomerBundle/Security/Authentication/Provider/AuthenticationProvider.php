<?php
/**
 */

namespace Commercetools\Symfony\CustomerBundle\Security\Authentication\Provider;

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Config;
use Commercetools\Symfony\CustomerBundle\Security\User\CtpUser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Provider\UserAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use function strtolower;

class AuthenticationProvider extends UserAuthenticationProvider
{
    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $config;

    /**
     * @var Session
     */
    private $session;

    /**
     * AuthenticationProvider constructor.
     * @param Client $client
     * @param Config $config
     * @param UserProviderInterface $userProvider
     * @param UserCheckerInterface $userChecker
     * @param $providerKey
     * @param bool $hideUserNotFoundExceptions
     * @param LoggerInterface $logger
     * @param Session $session
     */
    public function __construct(
        Client $client,
        Config $config,
        UserProviderInterface $userProvider,
        UserCheckerInterface $userChecker,
        $providerKey,
        $hideUserNotFoundExceptions = true,
        // phpcs:ignore
        LoggerInterface $logger,
        // phpcs:ignore
        Session $session
    ) {
        parent::__construct($userChecker, $providerKey, $hideUserNotFoundExceptions);
        $this->userProvider = $userProvider;
        $this->config = $config;
        $this->client = $client;
        $this->logger = $logger;
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $access_token)
    {
        $currentUser = $access_token->getUser();

        if ($currentUser instanceof UserInterface) {
            if ($currentUser->getPassword() !== $user->getPassword()) {
                throw new BadCredentialsException('The credentials were changed from another session.');
            }
        } else {
            if (!$presentedPassword = $access_token->getCredentials()) {
                throw new BadCredentialsException('The presented password cannot be empty.');
            }

            $provider = new PasswordFlowTokenProvider(
                $this->session,
                $this->config,
                $currentUser,
                $presentedPassword
            );

            try {
                $token = $provider->getToken();
            } catch (ClientException $e) {
                throw new BadCredentialsException('The presented password is invalid.');
            }

            $request = RequestBuilder::of()->me()->get();
            $psrResponse = $this->client->send($request->httpRequest());
            $customer = $request->mapFromResponse($psrResponse);

            if (strtolower($currentUser) !== strtolower($customer->getEmail())) {
                throw new BadCredentialsException('The presented password is invalid.');
            }

            if ($user instanceof CtpUser) {
                $user->setId($customer->getId());
                $user->setAccessToken($token->getToken());
                $user->setRefreshToken($token->getRefreshToken());

                $request = RequestBuilder::of()->me()->carts()->getActiveCart();
                $psrResponse = $this->client->send($request->httpRequest());
                $cart = $request->mapFromResponse($psrResponse);

                if (!is_null($cart)) {
                    $user->setCartId($cart->getId());
                    $user->setCartItemCount($cart->getLineItemCount());
                }

                $defaultShippingAddress = $customer->getDefaultShippingAddress();
                if (!is_null($defaultShippingAddress)) {
                    $user->setDefaultShippingAddress($defaultShippingAddress);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function retrieveUser($username, UsernamePasswordToken $token)
    {
        $user = $token->getUser();
        if ($user instanceof UserInterface) {
            return $user;
        }

        try {
            $user = $this->userProvider->loadUserByUsername($username);

            if (!$user instanceof UserInterface) {
                throw new AuthenticationServiceException('The user provider must return a UserInterface object.');
            }

            return $user;
        } catch (UsernameNotFoundException $notFound) {
            throw $notFound;
        } catch (\Exception $repositoryProblem) {
            throw new AuthenticationServiceException($repositoryProblem->getMessage(), 0, $repositoryProblem);
        }
    }
}
