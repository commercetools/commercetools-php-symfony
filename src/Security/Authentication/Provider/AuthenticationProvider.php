<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Security\Authentication\Provider;

use Commercetools\Core\Request\Customers\CustomerLoginRequest;
use Commercetools\Symfony\CtpBundle\Model\AuthSuccess;
use Commercetools\Symfony\CtpBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\CtpBundle\Model\Repository\CustomerRepository;
use Commercetools\Symfony\CtpBundle\Security\User\User;
use Commercetools\Symfony\CtpBundle\Service\ClientFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Provider\UserAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AuthenticationProvider extends UserAuthenticationProvider
{
    /**
     * @var UserProviderInterface
     */
    private $userProvider;
    
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ClientFactory $clientFactory,
        UserProviderInterface $userProvider,
        UserCheckerInterface $userChecker,
        $providerKey,
        $hideUserNotFoundExceptions = true,
        LoggerInterface $logger
    )
    {
        parent::__construct($userChecker, $providerKey, $hideUserNotFoundExceptions);
        $this->userProvider = $userProvider;
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        $currentUser = $token->getUser();

        if ($currentUser instanceof UserInterface) {
            if ($currentUser->getPassword() !== $user->getPassword()) {
                throw new BadCredentialsException('The credentials were changed from another session.');
            }
        } else {
            if (!$presentedPassword = $token->getCredentials()) {
                throw new BadCredentialsException('The presented password cannot be empty.');
            }

            $client = $this->clientFactory->build();
            $cartId = null;
            if ($user instanceof User) {
                $cartId = $user->getCartId();
            }
            $request = CustomerLoginRequest::ofEmailAndPassword($token->getUser(), $presentedPassword, $cartId);
            $response = $request->executeWithClient($client);
            if ($response->isError()) {
                throw new BadCredentialsException('The presented password is invalid.');
            }
            $result = $request->mapResponse($response);
            $customer = $result->getCustomer();
            if ($currentUser !== $customer->getEmail()) {
                throw new BadCredentialsException('The presented password is invalid.');
            }
            if ($user instanceof User) {
                $user->setId($customer->getId());
                $cart = $result->getCart();
                if (!is_null($cart)) {
                    $user->setCartId($cart->getId());
                    $user->setCartItemCount($cart->getLineItemCount());
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
            throw new AuthenticationServiceException($repositoryProblem->getMessage(), $token, 0, $repositoryProblem);
        }
    }
}
