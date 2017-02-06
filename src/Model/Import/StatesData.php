<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 06/02/17
 * Time: 17:53
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\State\StateDraft;

class StatesData
{
    const NAME='name';
    const INITIAL='initial';
    const DESCRIPTION='description';

    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getStateObjsFromArr($stateDataArray)
    {
        $stateDataArray[self::NAME]=LocalizedString::fromArray($stateDataArray[self::NAME]);
        $stateDataArray[self::DESCRIPTION]=LocalizedString::fromArray($stateDataArray[self::DESCRIPTION]);
        $stateDataArray[self::INITIAL]=boolval($stateDataArray[self::INITIAL]);
        $state = StateDraft::fromArray($stateDataArray);
        return $state;
    }
}
