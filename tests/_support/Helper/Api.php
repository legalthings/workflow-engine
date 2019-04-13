<?php
namespace Helper;

use Faker;
use Jasny\HttpSignature\HttpSignature;
use Jasny\HttpMessage\ServerRequest;
use PHPUnit\Framework\Assert;
use Codeception\PHPUnit\Constraint\JsonContains;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Api extends \Codeception\Module
{
    protected $privateKeys = [
        'user' => '4hGqYDMDaV2coJWigCtfQUzGbRVv6EjF9tPumxfdsV42KNF3LCpvewg6LXUmN11rjTnsgk32V8yr2Aqs8nRW9q7w',
        'organization' => '2DDGtVHrX66Ae8C4shFho4AqgojCBTcE4phbCRTm3qXCKPZZ7reJBXiiwxweQAkJ3Tsz6Xd3r5qgnbA67gdL5fWE',
        'node' => '37gsytK7XoJzzhyVNuTTm1rNRVpiXcvTWBM994KXgr5nYDMH6j5GJqcGKEXmqeJ1P93mKeDHAR1x3anS3VbBCgsi',
        'stranger' => '8gMxsaj2YT8HkLCh6k4pnYPUiAXkHtJRA2Bc29c1CMhN7FsMxSQSWjK7rWirHgoP9bkHy6ExwfiGPdQA3yyj1N1',
    ];

    /**
     * @return \Codeception\Module
     */
    protected function getJasnyModule()
    {
        return $this->getModule('\Jasny\Codeception\Module');
    }
    
    public function signRequest(string $method, string $path)
    {
        $account = $this->getJasnyModule()->container->get(\LTO\Account::class);
        $request = $this->getSignedRequest($account, $method, $path);

        $rest = $this->getModule('REST');
        $rest->haveHttpHeader('Date', $request->getHeaderLine('Date'));
        $rest->haveHttpHeader('Authorization', $request->getHeaderLine('Authorization'));
    }

    public function signRequestAs(string $role, string $method, string $path)
    {
        if (!isset($this->privateKeys[$role])) {
            throw new \InvalidArgumentException("Role '$role' is not defined");
        }

        $privateKey = $this->privateKeys[$role];

        $accountFactory = $this->getJasnyModule()->container->get(\LTO\AccountFactory::class);
        $account = $accountFactory->create($privateKey, 'base58');

        $request = $this->getSignedRequest($account, $method, $path);

        $rest = $this->getModule('REST');
        $rest->haveHttpHeader('Date', $request->getHeaderLine('Date'));
        $rest->haveHttpHeader('Authorization', $request->getHeaderLine('Authorization'));
    }

    /**
     * Get signed request
     *
     * @param string $method
     * @param string $path 
     * @return Jasny\HttpMessage\ServerRequest
     */
    protected function getSignedRequest(\LTO\Account $account, string $method, string $path): ServerRequest
    {
        $headers = ['date' => date(DATE_RFC1123)];

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

        $httpSignature = new HttpSignature(
            'ed25519',
            new \LTO\Account\SignCallback($account),
            function () {}
        );

        return $httpSignature->sign($request, $account->getPublicSignKey());
    }
    
    /**
     * Sign as one of the predefined identities.
     * 
     * @param string $role
     */
    public function amSignatureAuthenticatedAs(string $role)
    {
        if (!isset($this->privateKeys[$role])) {
            throw new \InvalidArgumentException("Role '$role' is not defined");
        }

        $privateKey = $this->privateKeys[$role];

        $module = $this->getJasnyModule();

        $accountFactory = $module->container->get(\LTO\AccountFactory::class);
        $account = $accountFactory->create($privateKey, 'base58');

        $request = $module->client->getBaseRequest()->withAttribute('account', $account);
        $module->client->setBaseRequest($request);
    }
    
    /**
     * Set responses for Guzzle mock
     * 
     * @param callable|\GuzzleHttp\Psr7\Response $response
     */
    public function expectHttpRequest($response)
    {
        $module = $this->getJasnyModule();
        
        $mock = $module->container->get(\GuzzleHttp\Handler\MockHandler::class);
        $mock->append($response);
    }

    /**
     * Get entity data from json file
     *
     * @param string $name
     * @return array
     */
    public function getEntityDump(string $folder, string $name): array
    {
        $scenario = file_get_contents("tests/_data/$folder/$name.json");

        return json_decode($scenario, true);
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
     * See if given event body equals given data
     *
     * @param int $idx
     * @param array $data
     */
    public function seeResponseChainEventHasBody(int $idx, array $data)
    {
        $body = $this->getDecodedEventBodyFromResponseChain($idx);

        Assert::assertEquals($data, $body);
    }

    /**
     * See if given event of event-chain contains data
     *
     * @param int $idx
     * @param array $data
     */
    public function seeResponseChainEventContainsJson(int $idx, array $data)
    {
        $body = $this->getDecodedEventBodyFromResponseChain($idx);

        Assert::assertThat(json_encode($body), new JsonContains($data));
    }

    /**
     * See if response event chain contains given number of events
     *
     * @param int $count
     */
    public function seeResponseChainEventsCount(int $count)
    {
        $json = $this->getResponseJson();

        Assert::assertSame($count, count($json->events));
    }

    /**
     * Get decoded body for given event from response event chain
     *
     * @param int $idx
     * @return array
     */
    protected function getDecodedEventBodyFromResponseChain(int $idx)
    {
        $json = $this->getResponseJson();
        $body = $json->events[$idx]->body;

        return $this->decodeEventBody($body);
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
     * Decode event body
     * @param  string $body  Encoded event body
     * @return array
     */
    public function decodeEventBody($body): array
    {
        $data = base58_decode($body);        

        return json_decode($data, true);
    }

    /**
     * Assert response equals the contents of a JSON file.
     *
     * @param string $path
     */
    protected function assertResponseJsonEqualsFile(string $path): void
    {
        $expected = json_decode(file_get_contents($path));
        $actual = $this->getResponseJson();

        unset($actual->id);

        Assert::assertEquals($expected, $actual);
    }

    /**
     * Get response json data
     *
     * @return stdClass
     */
    protected function getResponseJson(): \stdClass
    {
        $json = $this->getModule('REST')->grabResponse();
        Assert::assertJson($json);

        return json_decode($json);
    }
}
