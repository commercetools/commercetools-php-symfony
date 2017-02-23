<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 06/02/17
 * Time: 17:53
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Commons\Helper\QueryHelper;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\State\State;
use Commercetools\Core\Model\State\StateCollection;
use Commercetools\Core\Model\State\StateDraft;
use Commercetools\Core\Request\States\StateQueryRequest;

class StatesData
{
    const NAME='name';
    const INITIAL='initial';
    const DESCRIPTION='description';
    const TRANSITION='transitions';
    const OBJ='obj';

    private $states=[];
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->setStates();
    }
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
        }
        return $stateDataArray;
    }
    private function setStates()
    {
        $request = StateQueryRequest::of();
        $helper = new QueryHelper();
        $states = $helper->getAll($this->client, $request);
        /**
         * @var StateCollection $states ;
         */
        foreach ($states as $state) {
            $this->states[$state->getId()] = $state;
            $this->states[$state->getKey()] = $state;
        }
    }
    public function getState($key)
    {
        if (isset($this->states[$key])) {
            $state = $this->states[$key];
            return $state;
        }
        return null;
    }
    public function getStatesRef($key)
    {
        /**
         * @var State $state;
         */
        if (isset($this->states[$key])) {
            $state = $this->states[$key];
            return $state->getReference();
        }
        return null;
    }
}
