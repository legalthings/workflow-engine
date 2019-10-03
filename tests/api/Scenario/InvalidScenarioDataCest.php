<?php

/**
 * Try adding scenario with invalid data
 */

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class InvalidScenarioDataCest
{
    /**
     * @var array
     **/
    protected $data;

    /**
     * Init scenario data
     */
    public function _before(ApiTester $I): void
    {
        $data = file_get_contents('tests/_data/scenarios/basic-user-and-system.json');

        $this->data = json_decode($data, true);        
        $this->data['id'] = '3461288f-108e-4398-8d2d-7914ffd99ly8';
    }

    /**
     * Provide data for testing invalid values
     *
     * @return array
     */
    protected function invalidValuesProvider()
    {
        return [
            [
                'field' => '$schema', 
                'value' => 'foo', 
                'message' => ['schema property value is not valid'], 
                'code' => 400
            ],
            [
                'field' => '$schema', 
                'value' => 'https://specs.livecontracts.io/scenario/schema.json#', 
                'message' => ['schema property value is not valid'], 
                'code' => 400
            ],
            [
                'field' => '$schema', 
                'value' => 'https://specs.livecontracts.io/0.2.0/scenario/schema.json#', 
                'message' => ['schema property value is not valid'], 
                'code' => 400
            ],
            [
                'field' => '$schema', 
                'value' => 'https://specs.livecontracts.io/v0.2.0/process/schema.json#', 
                'message' => ['schema property value is not valid'], 
                'code' => 400
            ],
            [
                'field' => '$schema', 
                'value' => 'https://specs.livecontracts.io/v10.25.120/scenario/schema.json#', 
                'code' => 200
            ],
            [
                'field' => 'id', 
                'value' => '2557288f-108e-4398-8d2d-7914ffd93150', // id of existing scenario
                'code' => 204
            ]
        ];
    }

    /**
     * Save scenario with invalid values
     *
     * @dataprovider invalidValuesProvider
     */
    public function testInvalidValues(ApiTester $I, \Codeception\Example $example)
    {
        $this->data[$example['field']] = $example['value'];

        $this->test($I, $example['message'] ?? null, $example['code'] ?? 500);
    }

    /**
     * Perform test
     */
    protected function test(ApiTester $I, $message, $code = 500)
    {
        $I->am('organization');
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/scenarios', $this->data);

        $I->seeResponseCodeIs($code);

        $isJson = isset($message) && !is_string($message);
        if ($isJson) {
            $I->seeResponseIsJson();
            $I->seeResponseContainsJson($message);
        } elseif (isset($message)) {
            $I->seeResponseEquals($message);
        } elseif ($code === 204) {
            $I->seeResponseEquals('');
        } else {
            $I->seeResponseContainsJson(['title' => 'Basic system and user']);
        }
    }
}
