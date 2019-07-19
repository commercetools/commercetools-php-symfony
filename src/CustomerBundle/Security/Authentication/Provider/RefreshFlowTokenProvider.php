<?php
/**
 *
 */

namespace Commercetools\Symfony\CustomerBundle\Security\Authentication\Provider;

use Commercetools\Core\Client\OAuth\RefreshTokenProvider;
use Commercetools\Core\Client\OAuth\Token;
use Commercetools\Core\Config;
use Commercetools\Symfony\CustomerBundle\Model\Repository\CustomerRepository;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Session\Session;

class RefreshFlowTokenProvider implements RefreshTokenProvider
{
    const GRANT_TYPE = 'grant_type';
    const GRANT_TYPE_REFRESH_TOKEN = 'refresh_token';
    const SCOPE = 'scope';
    const REFRESH_TOKEN = 'refresh_token';
    const ACCESS_TOKEN = 'access_token';
    const EXPIRES_IN = 'expires_in';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Config $config
     */
    private $config;

    /**
     * RefreshFlowTokenProvider constructor.
     * @param Session $session
     * @param Config $config
     */
    public function __construct(Session $session, Config $config)
    {
        $this->client = new Client($config->getOAuthClientOptions());

        $this->config = $config;
        $this->session = $session;
    }


    /**
     * @return Token
     */
    public function getToken()
    {
        if ($token = $this->session->get(CustomerRepository::CUSTOMER_ACCESS_TOKEN)) {
            return new Token($token);
        }
        return $this->provideToken();
    }

    /**
     * @return Token
     */
    public function provideToken()
    {
        if ($this->session->get(CustomerRepository::CUSTOMER_REFRESH_TOKEN)) {
            return $this->refreshToken();
        }

        return (new AnonymousFlowTokenProvider(
            $this->session,
            $this->config
        ))->getToken();
    }

    /**
     * @return Token
     */
    public function refreshToken()
    {
        $data = [
            self::GRANT_TYPE => self::GRANT_TYPE_REFRESH_TOKEN,
            self::REFRESH_TOKEN => $this->session->get(CustomerRepository::CUSTOMER_REFRESH_TOKEN)
        ];
        $options = [
            'form_params' => $data,
            'auth' => [$this->config->getClientCredentials()->getClientId(), $this->config->getClientCredentials()->getClientSecret()]
        ];

        $result = $this->client->post($this->config->getOauthUrl(), $options);

        $body = json_decode((string)$result->getBody(), true);
        $token = new Token((string)$body[self::ACCESS_TOKEN], (int)$body[self::EXPIRES_IN], $body[self::SCOPE]);
        $token->setRefreshToken((string)$body[self::REFRESH_TOKEN]);

        $this->session->set(CustomerRepository::CUSTOMER_ACCESS_TOKEN, $token->getToken());
        $this->session->set(CustomerRepository::CUSTOMER_REFRESH_TOKEN, $token->getRefreshToken());

        return $token;
    }
}
