<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Service;

use Commercetools\Core\Config;
use Commercetools\Core\Client\ClientFactory as CtpClientFactory;
use GuzzleHttp\Client;

class ClientFactory
{
    public function buildOauthClient(CtpClientFactory $factory, Config $config): Client
    {
        return $factory->createAuthClient($config->getOAuthClientOptions(), $config->getCorrelationIdProvider());
    }
}
