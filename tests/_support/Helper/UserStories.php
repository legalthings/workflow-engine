<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class UserStories extends \Codeception\Module
{
    protected $sessions = [
        'client' => 'a00000000000000000000001',
        'admin' => 'a00000000000000000000002',
        'lawyer' => 'a00000000000000000000011',
        'notary' => 'a00000000000000000000011',
        
        'external architect' => 'a00000000000000000001001',
        'internal architect' => 'a00000000000000000001002',
        'division leader' => 'a00000000000000000001005',
        'oru reviewer 1' => 'a00000000000000000001011',
        'oru reviewer 2' => 'a00000000000000000001012',
        'oru reviewer 3' => 'a00000000000000000001013',
        'oru reviewer 4' => 'a00000000000000000001014'
    ];

    protected $actors = [
        'external architect' => [
            "id" => "000000000000000000001001",
            "name" => "Monica",
            "email" => "monica@example.com",
            "organization" => [
                "id" => "ccc000000000000000001000",
                "name" => "Example Corp Inc",
                "type" => "primary",
                "organization_type" => "corporation"
            ]
        ],
        'internal architect' => [
            "id" => "000000000000000000001002",
            "name" => "John",
            "email" => "john@example.com",
            "organization" => [
                "id" => "ccc000000000000000001000",
                "name" => "Example Corp Inc",
                "type" => "primary",
                "organization_type" => "corporation"
            ]
        ],
        'oru reviewer 1' => [
            "id" => "000000000000000000001011",
            "name" => "Arnold",
            "email" => "arnold@example.com",
            "organization" => [
                "id" => "ccc000000000000000001011",
                "name" => "Onze",
                "type" => "client",
                "organization_type" => "oru"
            ]
        ],
        'oru reviewer 2' => [
            "id" => "000000000000000000001012",
            "name" => "Beatrice",
            "email" => "beatrice@example.com",
            "organization" => [
                "id" => "ccc000000000000000001012",
                "name" => "Douze",
                "type" => "client",
                "organization_type" => "oru"
            ]
        ],
        'oru reviewer 3' => [
            "id" => "000000000000000000001013",
            "name" => "Claudia",
            "email" => "claudia@example.com",
            "organization" => [
                "id" => "ccc000000000000000001013",
                "name" => "Treze",
                "type" => "client",
                "organization_type" => "oru"
            ]
        ],
        'oru reviewer 4' => [
            "id" => "000000000000000000001014",
            "name" => "Donald",
            "email" => "donald@example.com",
            "organization" => [
                "id" => "ccc000000000000000001014",
                "name" => "Quatorze",
                "type" => "client",
                "organization_type" => "oru"
            ]
        ]
    ];
    
    /**
     * Act a role
     * 
     * @param $role
     */
    public function am($role)
    {
        $rest = $this->getModule('REST');
        
        if (isset($this->sessions[$role])) {
            $rest->haveHttpHeader('X-Session', $this->sessions[$role]);
        } else {
            $rest->haveHttpHeader('X-Session', null); // Delete ?
        }
    }
    
    /**
     * Add a user story scenario to the DB
     * 
     * @param string $scenario
     * @param array $data      optionally modify the inserted scenario with additional data
     */
    public function loadScenario($scenario, $data = [])
    {
        $mongo = $this->getModule('MongoDb');
        $file = dirname($mongo->_getConfig('dump')) . '/user-stories/' . $scenario . '.json';
        $contents = file_get_contents($file);
        $scenario = array_merge(json_decode($contents, true), $data);
        
        $mongo->haveInCollection('scenarios', $scenario);
    }
    
    /**
     * Assert that an actor matches one of the roles
     * 
     * @param string $role
     * @param array  $actual
     */
    public function assertActorEquals($role, $actual)
    {
        if (!isset($this->actors[$role])) {
            $this->fail("'$role' actor is not defined");
        }

        $match = array_intersect_key($actual, $this->actors[$role]);
        $this->assertEquals($this->actors[$role], $match, $role);
    }
}
