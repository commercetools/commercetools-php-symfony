<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Security\Authentication\Provider;

use Commercetools\Core\Request\Customers\CustomerLoginRequest;
use Commercetools\Symfony\CtpBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\CtpBundle\Model\Repository\CustomerRepository;
use Commercetools\Symfony\CtpBundle\Service\ClientFactory;
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
     * @var Session
     */
    private $session;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Session $session,
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
        $this->session = $session;
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

            $client = $this->clientFactory->build('en');
            $cartId = $this->session->get(CartRepository::CART_ID);
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
            $user->setId($customer->getId());
            $this->session->set(CustomerRepository::CUSTOMER_ID, $customer->getId());
            $this->session->set(CartRepository::CART_ID, $result->getCart()->getId());
            $this->session->set(CartRepository::CART_ITEM_COUNT, $result->getCart()->getLineItemCount());
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
