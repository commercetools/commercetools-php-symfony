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

    public function getStateObjsFromArr($stateDataArray)
    {
        if (isset($stateDataArray[self::NAME]) && !empty($stateDataArray[self::NAME])) {
            $stateDataArray[self::NAME] = LocalizedString::fromArray($stateDataArray[self::NAME]);
        } elseif (isset($stateDataArray[self::NAME])) {
            unset($stateDataArray[self::NAME]);
        }
        if (isset($stateDataArray[self::DESCRIPTION]) && !empty($stateDataArray[self::DESCRIPTION])) {
            $stateDataArray[self::DESCRIPTION] = LocalizedString::fromArray($stateDataArray[self::DESCRIPTION]);
        } elseif (isset($stateDataArray[self::DESCRIPTION])) {
            unset($stateDataArray[self::DESCRIPTION]);
        }
        if (isset($stateDataArray[self::INITIAL])) {
            $stateDataArray[self::INITIAL] = boolval($stateDataArray[self::INITIAL]);
        } elseif (isset($stateDataArray[self::INITIAL])) {
            unset($stateDataArray[self::INITIAL]);
        }
        $state = StateDraft::fromArray($stateDataArray);
        return $state;
    }
}
