<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Service;

use Commercetools\Core\Client\OAuth\TokenStorage;
use Symfony\Component\HttpFoundation\Session\Session;

class SessionTokenStorage implements TokenStorage
{
    const CUSTOMER_ACCESS_TOKEN = 'customer.access_token';
    const CUSTOMER_REFRESH_TOKEN = 'customer.refresh_token';

    /**
     * @var Session
     */
    private $session;

    /**
     * SessionTokenStorage constructor.
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->session->get(self::CUSTOMER_REFRESH_TOKEN);
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->session->get(self::CUSTOMER_ACCESS_TOKEN);
    }

    /**
     * @param string $refreshToken
     * @return void
     */
    public function setRefreshToken($refreshToken)
    {
        $this->session->set(self::CUSTOMER_REFRESH_TOKEN, $refreshToken);
    }

    /**
     * @param string $accessToken
     * @return void
     */
    public function setAccessToken($accessToken)
    {
        $this->session->set(self::CUSTOMER_ACCESS_TOKEN, $accessToken);
    }
}
