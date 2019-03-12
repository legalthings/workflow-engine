<?php
namespace Helper;

use Faker;
use Jasny\HttpSignature\HttpSignature;
use Jasny\HttpMessage\ServerRequest;
use PHPUnit\Framework\Assert;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Api extends \Codeception\Module
{
    protected $sessions = [
        'actor' => 'a00000000000000000000001',
        'admin' => 'a00000000000000000000002',
        'other user' => 'a00000000000000000000003',
        'affiliate' => 'a00000000000000000000004',
        'client' => 'a00000000000000000000010',
        'lawyer' => 'a00000000000000000000011'
    ];

    /**
     * @return \Codeception\Module
     */
    public function getJasnyModule()
    {
        return $this->getModule('\Jasny\Codeception\Module');
    }
    
    /**
     * Adds Signature authentication via ED25519 secret key.
     *
     * @param string $secretkey
     * @part json
     * @part xml
     */
    public function amSignatureAuthenticated($secretkey)
    {
        $module = $this->getJasnyModule();
        
        $accountFactory = $module->container->get(\LTO\AccountFactory::class);
        $account = $accountFactory->create($secretkey, 'base64');
        
        $request = $module->client->getBaseRequest()->withAttribute('account', $account);
        $module->client->setBaseRequest($request);
    }

    /**
     * Get signed request
     *
     * @param string $method
     * @param string $path 
     * @param array $headers 
     * @return Jasny\HttpMessage\ServerRequest
     */
    public function getSignedRequest(string $method, string $path, array $headers): ServerRequest
    {
        $request = new ServerRequest();
        $uri = $request->getUri()->withPath($path);

        $request = $request
            ->withUri($uri)
            ->withMethod($method);

        $headersNames = ['(request-target)'];
        foreach ($headers as $name => $value) {
            $headersNames[] = strtolower($name);
            $request = $request->withHeader($name, $value);
        }

        $module = $this->getJasnyModule();
        $node = $module->container->get(\LTO\Account::class);
        $httpSignature = $module->container->get(HTTPSignature::class);

        return $httpSignature->sign($request, $node->getPublicSignKey(), 'ed25519');
    }
    
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
        } else if ($role == 'trusted') {
            $rest->haveHttpHeader('X-Trusted-Ip', true);
        } else {
            $rest->haveHttpHeader('X-Session', null); // Delete ?
        }
    }
    
    /**
     * Drop a MongoDB collection
     * 
     * @param string $collection
     */
    public function dropCollection($collection)
    {
        $this->getModule('MongoDb')->driver->getDbh()->selectCollection($collection)->drop();
    }
    
    /**
     * Add a number of processes to the process collection
     * 
     * @param int    $count
     * @param string $organizationId  Organization id for Huey and Dewey
     */
    public function seedProcessesCollection($count, $organizationId = null)
    {
        $list = [];
        
        $faker = Faker\Factory::create();
        $faker->seed(0);

        $db = $this->getModule('MongoDb')->driver->getDbh();
        $scenario = $db->scenarios->findOne(['_id' => 'dummy-wizard']);
        
        $dummyActors = [
            ["id" => sprintf('%024x', 1), "name" => "Huey", "email" => "huey@example.com"],
            ["id" => sprintf('%024x', 2), "name" => "Dewey", "email" => "dewey@example.com"],
            ["id" => sprintf('%024x', 3), "name" => "Louie", "email" => "louie@example.com"],
            ["requirement" => "affiliate"]
        ];

        if (isset($organizationId)) {
            $organization = ['id' => $organizationId, 'name' => 'Acme'];
            $dummyActors[0] += compact('organization');
            $dummyActors[1] += compact('organization');
        }
        
        $date = new \DateTime();
        
        for ($i = 0; $i < $count; $i++) {
            $index = $i;
            $id = $faker->regexify('[0-9a-f]{24}');
            $title = $faker->word;
            $color = $faker->colorName;
            $data = (object)['foo' => $faker->word];
            $random = [$faker->word => $faker->catchPhrase];
            $actors = ["dummy" => $faker->randomElement($dummyActors)];
            
            $previous = [];
            for ($j = 1, $n = $faker->numberBetween(0, 3); $j <= $n; $j++) {
                $previous[] = $this->getFakeAction($faker, $actors["dummy"], $j, true);
            }
            $transition = empty($previous) ? "step1" : end($previous)['response']['transition'];
            $current = $transition[0] === ':'
                ? ["key" => $transition]
                : $this->getFakeAction($faker, $actors["dummy"], substr($transition, -1), false);
            
            $process = compact('id', 'scenario', 'actors', 'title', 'color', 'previous', 'current', 'data', 'index') + $random;

            if (isset($process['previous']) && strpos($process["current"]["key"], ':') !== 0) {
                $process['nextCount'] = 3 - count($process['previous']) - 1;
            }
            
            $process['creation'] = $date->modify("+$i day")->format(\DateTime::ISO8601);
            
            $db->processes->insert($this->processAsMongoDocument($process));
            $list[] = $process;
        }

        return $list;
    }
    
    /**
     * Turn process data to a mongo document
     * 
     * @param array $process
     * @return array
     */
    protected function processAsMongoDocument($process)
    {
        $document = ["_id" => new \MongoId($process['id'])] + $process;
        unset($document['id']);
        
        foreach ($document['actors'] as &$actor) {
            if (!empty($actor['organization'])) {
                $actor['organization'] = $actor['organization']['id'];
            }
        }
        
        self::convertAssocToOrdered($document['actors']);
        if (isset($document['current']['responses'])) self::convertAssocToOrdered($document['current']['responses']);
        
        return $document;
    }
    
    /**
     * Turn an associative array in a ordered array, saving the key as $key
     */
    protected static function convertAssocToOrdered(&$array)
    {
        foreach ($array as $key => &$item) {
            $item['key'] = $key;
        }
        
        $array = array_values($array);
    }
    
    /**
     * Get a fake process action
     * 
     * @param \Faker  $faker
     * @param Actor   $actor
     * @param int     $i
     * @param boolean $done
     * @return array
     */
    protected function getFakeAction($faker, $actor, $i, $done)
    {
        $titles = [1 => "The first step", "The second step", "The last step"];
        
        $action = [
            "key" => "step{$i}",
            "definition" => "custom",
            "title" => $titles[$i],
            "actor" => ["key" => "dummy"] + $actor,
            "responses" => [
                "ok" => [
                    "transition" => $i < 3 ? "step" . ($i + 1) : ":success",
                    "title" => "Next"
                ],
                "cancel" => [
                    "transition" => ":failed"
                ]
            ]
        ];
        
        if ($done) {
            $key = $faker->boolean ? 'ok' : 'cancel';
            $action['response'] = ['key' => $key, 'transition' => $action['responses'][$key]['transition']];
            unset($action['responses']);
        }

        return $action;
    }
    
    /**
     * See that the response is json with a process list and that list contains the specified processes
     * 
     * @param array $processes
     */
    public function seeResponseIsProcessListWith(array $processes)
    {
        $list = array_map(function($process) {
            if (isset($process["previous"])) {
                $process["previous"] = count($process["previous"]);
            }
            
            if (isset($process["current"])) {
                $process["current"] = array_without($process["current"], ["responses", "definition"]);
                $process["state"] = in_array($process["current"]["key"], [':success', ':failed'])
                    ? "finished" : "running";

                if (isset($process['previous']) && strpos($process["current"]["key"], ':') !== 0) {
                    $process["next"] = 3 - $process['previous'] - 1;
                }
            }

            return array_without($process, ['data', 'scenario', 'actors']);
        }, $processes);
        
        foreach ($list as $item) {
            $this->getModule('REST')->seeResponseContainsJson($item);
        }
        
        $actual = json_decode($this->getModule('REST')->grabResponse());
        Assert::assertIsArray($actual);
        Assert::assertCount(count($processes), $actual);
    }
    
    /**
     * See that the response is json with a process list and that list contains the specified processes
     *
     * @param array $processes
     */
    public function seeResponseIsProcessFullListWith(array $processes)
    {
        $actual = json_decode($this->getModule('REST')->grabResponse());

        Assert::assertIsArray($actual);
        Assert::assertCount(count($processes), $actual);
    }
    
    /**
     * Set the `authz` property of the current action of the process
     * 
     * @param string $processId
     * @param string $token
     */
    public function willAuthorizeResponseWithToken($processId, $token)
    {
        $collection = $this->getModule('MongoDb')->driver->getDbh()->selectCollection('processes');
        $collection->update(['_id' => new \MongoId($processId)], ['$set' => ['current.authz' => compact('token')]]);
    }
    
    /**
     * Set the `permissions` property of a response of an action of the process
     * 
     * @param string       $processId
     * @param string       $actionIndex
     * @param string       $responseIndex
     * @param string|array $permissions
     */
    public function addPermissionsToResponse($processId, $actionIndex, $responseIndex, $permissions)
    {
        $collection = $this->getModule('MongoDb')->driver->getDbh()->selectCollection('processes');
        $collection->update([
            '_id' => new \MongoId($processId)],
            ['$set' => ["scenario.actions.{$actionIndex}.responses.{$responseIndex}.permissions" => $permissions]]
        );
    }

    /**
     * Check if the response JSON matches a process from the data directory.
     *
     * @param string $name    Process filename (without ext)
     * @param string $variant
     */
    public function seeResponseIsProcess(string $name, string $variant = '')
    {
        $path = getcwd() . '/tests/_data/processes/' . $name . ($variant !== '' ? '.' . $variant : '') . '.json';

        if (!file_exists($path)) {
            throw new \BadMethodCallException("Unable to locate process JSON: '$path' doesn't exist.");
        }

        $this->assertResponseJsonEqualsFile($path);
    }

    /**
     * Check if the response JSON matches a scenario from the data directory.
     *
     * @param string $name  Scenario filename (without ext)
     */
    public function seeResponseIsScenario($name)
    {
        $path = getcwd() . '/tests/_data/scenarios/' . $name . '.json';

        if (!file_exists($path)) {
            throw new \BadMethodCallException("Unable to locate scenario JSON: '$path' doesn't exist.");
        }

        $this->assertResponseJsonEqualsFile($path);
    }

    /**
     * Check if the response JSON matches an identity from the data directory.
     *
     * @param string $name  Identity filename (without ext)
     */
    public function seeResponseIsIdentity($name)
    {
        $path = getcwd() . '/tests/_data/identities/' . $name . '.json';

        if (!file_exists($path)) {
            throw new \BadMethodCallException("Unable to locate scenario JSON: '$path' doesn't exist.");
        }

        $this->assertResponseJsonEqualsFile($path);
    }

    /**
     * Assert response equals the contents of a JSON file.
     *
     * @param string $path
     */
    protected function assertResponseJsonEqualsFile(string $path): void
    {
        $json = $this->getModule('REST')->grabResponse();

        Assert::assertJson($json);

        $expected = json_decode(file_get_contents($path));
        $actual = json_decode($json);

        unset($actual->id);

        Assert::assertEquals($expected, $actual);
    }
}
