<?php
/**
 *
 */

namespace Commercetools\Symfony\CustomerBundle\Security\Authentication\Provider;

use Commercetools\Core\Client\OAuth\ClientCredentials;
use Commercetools\Core\Client\OAuth\Token;
use Commercetools\Core\Client\OAuth\TokenProvider;
use Commercetools\Core\Config;
use Commercetools\Symfony\CustomerBundle\Model\Repository\CustomerRepository;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Session\Session;

class AnonymousFlowTokenProvider implements TokenProvider
{
    const GRANT_TYPE = 'grant_type';
    const GRANT_TYPE_CLIENT_CREDENTIALS = 'client_credentials';
    const SCOPE = 'scope';
    const REFRESH_TOKEN = 'refresh_token';
    const ACCESS_TOKEN = 'access_token';
    const EXPIRES_IN = 'expires_in';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var ClientCredentials
     */
    private $credentials;

    /**
     * @var string
     */
    private $accessTokenUrl;

    /**
     * @var Session
     */
    private $session;


    /**
     * RefreshFlowTokenProvider constructor.
     * @param Session $session
     * @param ClientCredentials $config
     * @param string $accessTokenUrl
     */
    public function __construct(Session $session, Config $config)
    {
        $this->client = new Client($config->getOAuthClientOptions());

        $this->credentials = $config->getClientCredentials();
        $this->accessTokenUrl = $config->getOauthUrl(Config::GRANT_TYPE_ANONYMOUS);
        $this->session = $session;
    }

    /**
     * @return Token
     */
    public function getToken()
    {
        $data = [
            self::GRANT_TYPE => self::GRANT_TYPE_CLIENT_CREDENTIALS,
        ];
        $options = [
            'form_params' => $data,
            'auth' => [$this->credentials->getClientId(), $this->credentials->getClientSecret()]
        ];

        $result = $this->client->post($this->accessTokenUrl, $options);

        $body = json_decode((string)$result->getBody(), true);
        $token = new Token((string)$body[self::ACCESS_TOKEN], (int)$body[self::EXPIRES_IN], $body[self::SCOPE]);
        $token->setRefreshToken((string)$body[self::REFRESH_TOKEN]);

        $this->session->set(CustomerRepository::CUSTOMER_ACCESS_TOKEN, $token->getToken());
        $this->session->set(CustomerRepository::CUSTOMER_REFRESH_TOKEN, $token->getRefreshToken());

        return $token;
    }
}
