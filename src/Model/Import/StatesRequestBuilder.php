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
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Model\State\StateReferenceCollection;
use Commercetools\Core\Request\States\Command\StateAddRolesAction;
use Commercetools\Core\Request\States\Command\StateChangeInitialAction;
use Commercetools\Core\Request\States\Command\StateChangeKeyAction;
use Commercetools\Core\Request\States\Command\StateChangeTypeAction;
use Commercetools\Core\Request\States\Command\StateRemoveRolesAction;
use Commercetools\Core\Request\States\Command\StateSetDescriptionAction;
use Commercetools\Core\Request\States\Command\StateSetNameAction;
use Commercetools\Core\Request\States\Command\StateSetRolesAction;
use Commercetools\Core\Request\States\Command\StateSetTransitionsAction;
use Commercetools\Core\Request\States\StateCreateRequest;
use Commercetools\Core\Request\States\StateUpdateRequest;
use Commercetools\Core\Model\State\StateDraft;

class StatesRequestBuilder extends AbstractRequestBuilder
{
    const ID='id';
    const KEY='key';
    const NAME='name';
    const INITIAL='initial';
    const DESCRIPTION='description';
    const VERSION='version';
    const TYPE='type';
    const TRANSITION='transitions';
    const OBJ='obj';
    const ROLES='roles';

    private $client;
    private $stateDataObj;
    private $state;
    private $stateData;
    private $statesToUpdateTransitions;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->stateDataObj= new StatesData($this->client);
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
        $statesDataArr=$this->getStatesDataByIdentifiedByColumn($statesData, $identifiedByColumn);

        foreach ($statesDataArr as $key => $stateData) {
            if (!$this->stateDataObj->getState($key)) {
                $request  = $this->getCreateRequest($stateData);
                $requests []= $request;
            } else {
                $state = $this->stateDataObj->getState($key);
                $request = $this->getUpdateRequest($state, $stateData);
                if (!$request->hasActions()) {
                    $request = null;
                }
                $requests []=$request;
            }
        }
        return $requests;
    }
    public function getTransitionsUpdate()
    {
        $this->stateDataObj= new StatesData($this->client);
        $requests=[];
        if (!empty($this->statesToUpdateTransitions)) {
            foreach ($this->statesToUpdateTransitions as $key => $stateData) {
                unset($this->statesToUpdateTransitions[$key]);
                $state = $this->stateDataObj->getState($key);
                $request = $this->getUpdateRequest($state, $stateData);
                if (!$request->hasActions()) {
                    $request = null;
                }
                $requests [] = $request;
            }
        }
        return $requests;
    }
    private function getCreateRequest($stateDataArray)
    {
        $stateDataArray= $this->stateDataObj->getStateObjsFromArr($stateDataArray);
        if (isset($stateDataArray[self::TRANSITION]) && !empty($stateDataArray[self::TRANSITION])) {
            $transitions = explode(';', $stateDataArray[self::TRANSITION]);
            $transitionArr=$stateDataArray[self::TRANSITION];
            $stateDataArray[self::TRANSITION]=[];
            foreach ($transitions as $key => $value) {
                $transition = $this->stateDataObj->getStatesRef($value);
                if ($transition) {
                    $transition = $transition->toArray();
                    if (isset($transition[self::OBJ])) {
                        unset($transition[self::OBJ]);
                    }
                    $stateDataArray[self::TRANSITION][] = $transition;
                } else {
                    $stateDataArray[self::TRANSITION]=$transitionArr;
                    $this->statesToUpdateTransitions [$stateDataArray[self::KEY]] = $stateDataArray;
                    $stateDataArray[self::TRANSITION]=[];
                    break;
                }
            }
        } else {
            $stateDataArray[self::TRANSITION] = [];
        }
        $stateDataObj = StateDraft::fromArray($stateDataArray);
        $request = StateCreateRequest::ofDraft($stateDataObj);
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
                case self::TRANSITION:
                    $action = StateSetTransitionsAction::of();
                    $transitions = [];
                    if (isset($this->stateData[$heading])) {
                        foreach ($this->stateData[$heading] as $transition) {
                            if ($transition ==null) {
                                continue;
                            }
                            $transitions[] = StateReference::fromArray($transition);
                        }
                        $action->setTransitions(StateReferenceCollection::fromArray($transitions));
                    }
                    $actions[$heading] = $action;
                    break;
            }
        }
        return $actions;
    }
    private function getUpdateRequestsToAdd($toAdd)
    {
        $actions=[];
        foreach ($toAdd as $heading => $data) {
            switch ($heading) {
                case self::ROLES:
                    if (!empty($data)) {
                        foreach ($data as $role) { //It seems useless but to avoid index issues
                            $roles [] =$role;
                        }
                        if (isset($this->state[self::ROLES])) {
                            $action = StateAddRolesAction::ofRoles($roles);
                        } else {
                            $action = StateSetRolesAction::ofRoles($roles);
                        }
                        $actions[$heading."toAdd"] = $action;
                    }
                    break;
            }
        }
        return $actions;
    }
    private function getUpdateRequestsToDelete($toDelete)
    {
        $actions=[];
        foreach ($toDelete as $heading => $data) {
            switch ($heading) {
                case self::ROLES:
                    if (!empty($data)) {
                        foreach ($data as $role) { //It seems useless but to avoid index issues
                            $roles [] =$role;
                        }
                        $action = StateRemoveRolesAction::ofRoles($roles);
                        $actions[$heading."toRemove"] = $action;
                    }
                    break;
            }
        }
        return $actions;
    }
    private function getUpdateRequest(State $state, $stateData)
    {
        $this->state= $state->toArray();
        if (isset($stateData[self::TRANSITION])) {
            $transitions  = explode(';', $stateData[self::TRANSITION]);
            $transitionArr=$stateData[self::TRANSITION];

            $stateData[self::TRANSITION]=[];
            foreach ($transitions as $key => $value) {
                $transition = $this->stateDataObj->getStatesRef($value);

                if ($transition) {
                    $transition = $transition->toArray();
                    if (isset($transition[self::OBJ])) {
                        unset($transition[self::OBJ]);
                    }
                    $stateData[self::TRANSITION][] = $transition;
                } else {
                    $stateData[self::TRANSITION]=$transitionArr;
                    $this->statesToUpdateTransitions [$stateData[self::KEY]] = $stateData;
                    break;
                }
            }
        } else {
            $stateData[self::TRANSITION] = [];
        }

        if (isset($stateData[self::INITIAL])) {
            $stateData[self::INITIAL] = boolval($stateData[self::INITIAL]);
        }

        $toAdd=[];
        $toDelete=[];
        if (isset($stateData[self::ROLES]) && !empty($stateData[self::ROLES])) {
            $stateData[self::ROLES] = explode(';', $stateData[self::ROLES]);
            $toAdd[self::ROLES]= $stateData[self::ROLES];
            if (isset($this->state[self::ROLES])) {
                $toAdd[self::ROLES]=array_diff($stateData[self::ROLES], $this->state[self::ROLES]);
            }
        }
        if (isset($this->state[self::ROLES]) && !empty($this->state[self::ROLES])) {
            $toDelete[self::ROLES]= $this->state[self::ROLES];

            if (isset($stateData[self::ROLES])) {
                $toDelete[self::ROLES]=array_diff($this->state[self::ROLES], $stateData[self::ROLES]);
            }
        }

        $this->stateData= $stateData;

        //check if we will update transitions later so ignore it now
        if (isset($this->statesToUpdateTransitions[$stateData[self::KEY]])) {
            $stateData[self::TRANSITION]=[];
            $this->state[self::TRANSITION]=[];
        }

        $toChange = $this->arrayDiffRecursive($stateData, $this->state);
        $toChange = array_merge_recursive($toChange, $this->arrayDiffRecursive($this->state, $stateData));

        $actions=[];
        $actions = array_merge_recursive($actions, $this->getUpdateRequestsToChange($toChange));
        $actions = array_merge_recursive($actions, $this->getUpdateRequestsToAdd($toAdd));
        $actions = array_merge_recursive($actions, $this->getUpdateRequestsToDelete($toDelete));

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
