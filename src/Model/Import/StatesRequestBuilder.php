<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 06/02/17
 * Time: 15:57
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\State\State;
use Commercetools\Core\Request\States\Command\StateChangeInitialAction;
use Commercetools\Core\Request\States\Command\StateChangeKeyAction;
use Commercetools\Core\Request\States\Command\StateChangeTypeAction;
use Commercetools\Core\Request\States\Command\StateSetDescriptionAction;
use Commercetools\Core\Request\States\Command\StateSetNameAction;
use Commercetools\Core\Request\States\StateCreateRequest;
use Commercetools\Core\Request\States\StateQueryRequest;
use Commercetools\Core\Request\States\StateUpdateRequest;

class StatesRequestBuilder extends AbstractRequestBuilder
{
    const ID='id';
    const KEY='key';
    const NAME='name';
    const INITIAL='initial';
    const DESCRIPTION='description';
    const VERSION='version';
    const TYPE='type';

    private $client;
    private $stateDataObj;
    private $state;
    private $stateData;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->stateDataObj= new StatesData();
    }
    private function getStatesByIdentifiedByColumn($states, $identifiedByColumn)
    {
        $parts = explode('.', $identifiedByColumn);
        $statesArr=[];
        foreach ($states as $state) {
            switch ($parts[0]) {
                case self::KEY:
                case self::ID:
                    $statesArr[$state->toArray()[$identifiedByColumn]] = $state;
                    break;
            }
        }
        return $statesArr;
    }
    private function getStatesDataByIdentifiedByColumn($statesData, $identifiedByColumn)
    {
        $statesDataArr=[];
        $parts = explode('.', $identifiedByColumn);
        foreach ($statesData as $stateData) {
            switch ($parts[0]) {
                case self::KEY:
                case self::ID:
                    $statesDataArr[$stateData[$identifiedByColumn]] = $stateData;
                    break;
            }
        }
        return $statesDataArr;
    }
    /**
     * @param $statesData
     * @param $identifiedByColumn
     * @return ClientRequestInterface[]|null
     */
    public function createRequest($statesData, $identifiedByColumn)
    {
        $requests=[];
        $request = StateQueryRequest::of()
            ->where(
                sprintf(
                    $this->getIdentifierQuery($identifiedByColumn),
                    $this->getIdentifierFromArray($identifiedByColumn, $statesData)
                )
            )
            ->limit(500);

        $response = $request->executeWithClient($this->client);
        $states = $request->mapFromResponse($response);

        $statesArr=$this->getStatesByIdentifiedByColumn($states, $identifiedByColumn);
        $statesDataArr=$this->getStatesDataByIdentifiedByColumn($statesData, $identifiedByColumn);

        foreach ($statesDataArr as $key => $stateData) {
            if (!isset($statesArr[$key])) {
                $request  = $this->getCreateRequest($stateData);
                $requests []= $request;
            } else {
                $state = $statesArr[$key];
                $request = $this->getUpdateRequest($state, $stateData);
                if (!$request->hasActions()) {
                    $request = null;
                }
                $requests []=$request;
            }
        }
        return $requests;
    }
    private function getCreateRequest($stateDataArray)
    {
        $stateDataobj= $this->stateDataObj->getStateObjsFromArr($stateDataArray);
        $request = StateCreateRequest::ofDraft($stateDataobj);
        return $request;
    }
    private function getUpdateRequestsToChange($toChange)
    {
        $actions=[];
        foreach ($toChange as $heading => $data) {
            switch ($heading) {
                case self::KEY:
                    $actions[$heading] = StateChangeKeyAction::ofKey($this->stateData[$heading]);
                    break;
                case self::NAME:
                    $action = StateSetNameAction::of();
                    if (!empty($this->stateData[$heading])) {
                        $action->setName(LocalizedString::fromArray($this->stateData[$heading]));
                    }
                    if (!empty($this->stateData[$heading]) || !empty($this->state[$heading])) {
                        $actions[$heading] = $action;
                    }
                    break;
                case self::DESCRIPTION:
                    $action = StateSetDescriptionAction::of();
                    if (!empty($this->stateData[$heading])) {
                        $action->setDescription(LocalizedString::fromArray($this->stateData[$heading]));
                    }
                    if (!empty($this->stateData[$heading]) || !empty($this->state[$heading])) {
                        $actions[$heading] = $action;
                    }
                    break;
                case self::INITIAL:
                    if (isset($this->stateData[self::INITIAL])) {
                        $action = StateChangeInitialAction::of();
                        $action->setInitial($this->stateData[$heading]);
                        $actions[$heading] = $action;
                    } elseif (isset($this->state[$heading]) && !$this->state[$heading]) {
                        $action = StateChangeInitialAction::ofInitial(true);
                        $actions[$heading] = $action;
                    }
                    break;
                case self::TYPE:
                    $actions[$heading] = StateChangeTypeAction::ofType($this->stateData[$heading]);
                    break;
            }
        }
        return $actions;
    }
    private function getUpdateRequest(State $state, $stateData)
    {
        $this->state= $state->toArray();
        if (isset($stateData[self::INITIAL])) {
            $stateData[self::INITIAL] = boolval($stateData[self::INITIAL]);
        }
        $this->stateData= $stateData;
        $toChange = $this->arrayDiffRecursive($stateData, $this->state);
        $toChange = array_merge_recursive($toChange, $this->arrayDiffRecursive($this->state, $stateData));

        $actions=[];
        $actions = array_merge_recursive($actions, $this->getUpdateRequestsToChange($toChange));

        $request = StateUpdateRequest::ofIdAndVersion($this->state[self::ID], $this->state[self::VERSION]);
        $request->setActions($actions);
        return $request;
    }
    public function getIdentifierQuery($identifierName, $query = ' in (%s)')
    {
        $value = '';
        switch ($identifierName) {
            case self::KEY:
            case self::ID:
                $value = $identifierName. $query;
                break;
        }
        return $value;
    }
    public function getIdentifierFromArray($identifierName, $rows)
    {
        $parts = explode('.', $identifierName);
        $value=[];
        foreach ($rows as $row) {
            switch ($parts[0]) {
                case self::KEY:
                case self::ID:
                    $value [] = '"'.$row[$parts[0]].'"';
                    break;
            }
        }
        return implode(',', $value);
    }
}
