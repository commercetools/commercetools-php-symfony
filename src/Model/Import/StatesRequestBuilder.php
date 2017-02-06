<?php
/**
 * Created by PhpStorm.
 * User: ibrahimselim
 * Date: 06/02/17
 * Time: 15:57
 */

namespace Commercetools\Symfony\CtpBundle\Model\Import;

use Commercetools\Core\Client;
use Commercetools\Core\Request\States\StateCreateRequest;
use Commercetools\Core\Request\States\StateQueryRequest;

class StatesRequestBuilder extends AbstractRequestBuilder
{
    const ID='id';
    const KEY='key';
    const NAME='name';
    const INITIAL='initial';
    const DESCRIPTION='description';

    private $client;
    private $stateDataObj;
    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->stateDataObj= new StatesData($client);
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
