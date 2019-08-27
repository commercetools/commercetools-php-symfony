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

class PasswordFlowTokenProvider implements TokenProvider
{
    const GRANT_TYPE = 'grant_type';
    const GRANT_TYPE_PASSWORD = 'password';
    const SCOPE = 'scope';
    const REFRESH_TOKEN = 'refresh_token';
    const ACCESS_TOKEN = 'access_token';
    const EXPIRES_IN = 'expires_in';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var ClientCredentials $config
     */
    private $credentials;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $userName;

    /**
     * @var string
     */
    private $tokenUrl;

    /**
     * RefreshFlowTokenProvider constructor.
     * @param Session $session
     * @param Config $config
     * @param string $userName
     * @param string $password
     * @param Client $client
     */
    public function __construct(Session $session, Config $config, $userName, $password, Client $client)
    {
        $this->client = $client;

//            $this->client = new Client($config->getOAuthClientOptions());

        $this->credentials = $config->getClientCredentials();
        $this->tokenUrl = $config->getOauthUrl(Config::GRANT_TYPE_PASSWORD);
        $this->session = $session;
        $this->userName = $userName;
        $this->password = $password;
    }

    /**
     * @return Token
     */
    public function getToken()
    {
        $data = [
            self::GRANT_TYPE => self::GRANT_TYPE_PASSWORD,
            'username' => $this->userName,
            'password' => $this->password
        ];
        $options = [
            'form_params' => $data,
            'auth' => [$this->credentials->getClientId(), $this->credentials->getClientSecret()]
        ];

        $result = $this->client->post($this->tokenUrl, $options);

        $body = json_decode((string)$result->getBody(), true);
        $token = new Token((string)$body[self::ACCESS_TOKEN], (int)$body[self::EXPIRES_IN], $body[self::SCOPE]);
        $token->setRefreshToken((string)$body[self::REFRESH_TOKEN]);

        $this->session->set(CustomerRepository::CUSTOMER_ACCESS_TOKEN, $token->getToken());
        $this->session->set(CustomerRepository::CUSTOMER_REFRESH_TOKEN, $token->getRefreshToken());

        return $token;
    }
}
