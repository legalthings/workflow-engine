<?php

namespace Helper;

use App;
use Codeception\Exception\ContentNotFound;
use Codeception\Exception\ParseException;
use Codeception\TestInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Jasny\DotKey;
use PHPUnit\Framework\Assert;
use SessionManager;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler as HttpMockHandler;
use Improved as i;
use Improved\IteratorPipeline\Pipeline;
use Jasny\Container\Container;
use Jasny\Container\Loader\EntryLoader;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Flow extends \Codeception\Module
{
    private static $loadDeclarations = [
        'autowire',
        'config',
        'env',
        'global-data',
        'json-schema',
        'legalthings-services',
        'lto-accounts',
        'reflection',
    ];

    /**
     * @var string
     */
    protected $testDir;

    /**
     * @var HttpMockHandler
     */
    protected $httpMock;

    /**
     * @var array
     */
    protected $httpTriggerHistory = [];

    /**
     * @var string|null
     */
    protected $actor = null;

    /**
     * @var \Process
     */
    protected $process;


    /**
     * Get the entries for the container.
     *
     * @return EntryLoader
     */
    protected function getContainerEntries(): EntryLoader
    {
        $genericFiles = i\iterable_map(self::$loadDeclarations, function ($item) {
            return 'declarations/generic/' . $item . '.php';
        });

        $modelFiles = glob('declarations/models/*.php');

        $files = array_merge(i\iterable_to_array($genericFiles), $modelFiles);

        return new EntryLoader(i\iterable_to_iterator($files));
    }

    /**
     * Create a Guzzle mock handler for the \Trigger\Http class
     *
     * @return HttpClient
     */
    public function createHttpMock(): HttpClient
    {
        $this->httpMock = new HttpMockHandler([]);

        $handler = \GuzzleHttp\HandlerStack::create($this->httpMock);
        $handler->push(\GuzzleHttp\Middleware::history($this->httpTriggerHistory));

        return new HttpClient(['handler' => $handler]);
    }

    /**
     * Initialize the global application container.
     */
    protected function initContainer(): void
    {
        $basicEntries = i\iterable_to_array($this->getContainerEntries(), true);

        $httpClient = $this->createHttpMock();

        $entries = $basicEntries + [
                HttpClient::class => static function () use ($httpClient) {
                    return $httpClient;
                },
                SessionManager::class => static function () {
                    return (new SessionManager)->asMock();
                }
            ];

        App::setContainer(new Container($entries));
    }

    /**
     * **HOOK** executed before test
     *
     * @param TestInterface $test
     */
    public function _before(TestInterface $test)
    {
        $this->testDir = dirname($test->getMetadata()->getFilename());

        $this->httpTriggerHistory = [];
        $this->initContainer();
    }

    /**
     * **HOOK** executed after suite
     */
    public function _afterSuite()
    {
        App::setContainer(new Container([]));
    }


    /**
     * Set the current user.
     *
     * @param string $key
     */
    public function setActor(string $key): void
    {
        $this->actor = $key;
    }

    /**
     * Create a scenario from a json file and instantiate a process.
     *
     * @param string $scenarioFile
     * @return \Process
     */
    public function createProcessFrom(string $scenarioFile): \Process
    {
        $scenarioPath = $this->testDir . DIRECTORY_SEPARATOR . $scenarioFile;

        if (!file_exists($scenarioPath)) {
            throw new ContentNotFound("Scenario source not found: $scenarioPath");
        }

        $scenarioJson = file_get_contents($scenarioPath);
        $scenarioSource = json_decode($scenarioJson);

        if (!is_object($scenarioSource)) {
            throw new ParseException("Failed to parse scenario JSON: " . json_last_error_msg());
        }

        $scenario = \Scenario::fromData($scenarioSource);

        $this->process = $scenario->instantiate();

        return $this->process;
    }

    /**
     * Set the current process.
     *
     * @param \Process $process
     */
    public function useProcess(\Process $process): void
    {
        $this->process = $process;
    }


    /**
     * Set session user info.
     *
     * @param array|/stdClass $user
     */
    public function haveSessionUser($user)
    {
        /** @var SessionManager $sessionManager */
        $sessionManager = App::getContainer()->get(SessionManager::class);

        $sessionManager->setMockData('1', ['user' => (object)$user]);
    }

    /**
     * Unset session user info.
     */
    public function haveNoSessionUser()
    {
        /** @var SessionManager $sessionManager */
        $sessionManager = App::getContainer()->get(SessionManager::class);

        $sessionManager->setMockData('1', []);
    }

    /**
     * Get the session id.
     *
     * @return string
     */
    public function grabSessionId(): string
    {
        /** @var SessionManager $sessionManager */
        $sessionManager = App::getContainer()->get(SessionManager::class);

        return $sessionManager->getSessionId();
    }

    /**
     * Get the session user.
     *
     * @return array|null
     */
    public function grabSessionUser(): array
    {
        /** @var SessionManager $sessionManager */
        $sessionManager = App::getContainer()->get(SessionManager::class);

        return $sessionManager->getSession()['user'] ?? null;
    }


    /**
     * Assert a process attribute equals the expected value.
     *
     * @param string $property
     * @param mixed  $expected
     */
    public function seeProcessHas(string $property, $expected): void
    {
        if ($this->process === null) {
            $this->fail("No process is running");
            return;
        }

        $actual = DotKey::on($this->process)->get($property);

        Assert::assertEquals($expected, $actual);
    }

    /**
     * Check the current state of the process.
     *
     * @param string $key
     */
    public function seeCurrentStateIs(string $key): void
    {
        if ($this->process === null) {
            $this->fail("No process is running");
            return;
        }

        Assert::assertEquals($key, $this->process->current->getKey());
    }

    /**
     * Assert the default action.
     *
     * @param string $key
     */
    public function seeDefaultActionIs(string $key): void
    {
        if ($this->process === null) {
            $this->fail("No process is running");
            return;
        }

        $action = $this->process->current->getDefaultAction();

        if ($action === null) {
            $this->fail(sprintf("State '%s' doesn't have a default action", $this->process->current->getKey()));
            return;
        }

        Assert::assertEquals($key, $action->getKey());
    }

    /**
     * Assert that the current actor is allow to perform the specified action in the current state.
     *
     * @param string $key
     */
    public function seeICanDoAction(string $key): void
    {
        if ($this->process === null) {
            $this->fail("No process is running");
            return;
        }

        $action = $this->process->getCurrentAllowedAction($key, $this->actor);

        if ($action === null) {
            $state = $this->process->current->getKey();
            $msg = $this->process->getCurrentAllowedAction($key)
                ? sprintf("Action '%s' is not allowed in state '%s'", $key, $state)
                : sprintf("Actor '%s' is not allowed to do '%s' in state '%s'", $this->actor, $key, $state);

            $this->fail($msg);
            return;
        }
    }

    /**
     * Assert the default action has an attribute that equals the expected value.
     *
     * @param string $property
     * @param mixed  $expected
     */
    public function seeDefaultActionHas(string $property, $expected): void
    {
        if ($this->process === null) {
            $this->fail("No process is running");
            return;
        }

        $action = $this->process->current->getDefaultAction();

        if ($action === null) {
            $this->fail(sprintf("State '%s' doesn't have a default action", $this->process->current->getKey()));
            return;
        }

        $actual = DotKey::on($action)->get($property);

        Assert::assertEquals($expected, $actual);
    }

    /**
     * Assert a action in the current state exists and has an attribute that equals the expected value.
     *
     * @param string $key
     * @param string $property
     * @param mixed  $expected
     */
    public function seeActionHas(string $key, string $property, $expected): void
    {
        if ($this->process === null) {
            $this->fail("No process is running");
            return;
        }

        $action = $this->process->getCurrentAllowedAction($key);

        if ($action === null) {
            $this->fail(sprintf("Action '%s' is not allowed in state '%s'", $key, $this->process->current->getKey()));
            return;
        }

        $actual = DotKey::on($action)->get($property);

        Assert::assertEquals($expected, $actual);
    }

    /**
     * Assert the previous responses by action and response key.
     *
     * @param array $keys  ['action.response', ...]
     */
    public function seePreviousResponsesWere(array $keys): void
    {
        if ($this->process === null) {
            $this->fail("No process is running");
            return;
        }

        $previous = Pipeline::with($this->process->previous)
            ->map(function(\Response $response) {
                return sprintf('%s.%s', $response->action, $response->getKey());
            })
            ->toArray();

        Assert::assertEquals($keys, $previous);
    }

    /**
     * Assert an attribute of the previous response equals the expected value.
     *
     * @param string $property
     * @param mixed  $expected
     */
    public function seePreviousResponseHas(string $property, $expected): void
    {
        if ($this->process === null) {
            $this->fail("No process is running");
            return;
        }

        if (count($this->process->previous) === 0) {
            $this->fail("Process doesn't has any responses yet");
            return;
        }

        $response = i\iterable_last($this->process->previous);
        $actual = DotKey::on($response)->get($property);

        Assert::assertEquals($expected, $actual);
    }

    /**
     * Assert the next states by key.
     *
     * @param array $keys
     * @throws \RuntimeException
     */
    public function seeNextStatesAre(array $keys): void
    {
        if ($this->process === null) {
            $this->fail("No process is running");
            return;
        }

        $next = Pipeline::with($this->process->getNext())
            ->map(function(\State $state) {
                return $state->getKey();
            })
            ->toArray();

        Assert::assertEquals($keys, $next);
    }

    /**
     * Assert an asset exists and equals the expected value.
     *
     * @param string           $key
     * @param \Asset|\AssetSet $expected
     */
    public function seeAssetEquals(string $key, $expected): void
    {
        if ($this->process === null) {
            $this->fail("No process is running");
            return;
        }

        if (!isset($this->process->assets[$key])) {
            $this->fail("Process doesn't have '$key' asset");
            return;
        }

        Assert::assertEquals($expected, $this->process->assets[$key]);
    }

    /**
     * Assert a asset exists and has an attribute that equals the expected value.
     *
     * @param string $key
     * @param string $property
     * @param mixed  $expected
     */
    public function seeAssetHas(string $key, string $property, $expected): void
    {
        if ($this->process === null) {
            $this->fail("No process is running");
            return;
        }

        if (!isset($this->process->assets[$key])) {
            $this->fail("Process doesn't have '$key' asset");
            return;
        }

        $actual = DotKey::on($this->process->assets[$key])->get($property);

        Assert::assertEquals($expected, $actual);
    }

    /**
     * Assert an actor exists and equals the expected value.
     *
     * @param string $key
     * @param \Actor $expected
     */
    public function seeActorEquals(string $key, \Actor $expected): void
    {
        if ($this->process === null) {
            $this->fail("No process is running");
            return;
        }

        if (!isset($this->process->actors[$key])) {
            $this->fail("Process doesn't have '$key' actor");
            return;
        }

        $expected->link($this->process, $key);

        Assert::assertEquals($expected, $this->process->actors[$key]);
    }

    /**
     * Assert a actor exists and has an attribute that equals the expected value.
     *
     * @param string $key
     * @param string $property
     * @param mixed  $expected
     */
    public function seeActorHas(string $key, string $property, $expected): void
    {
        if ($this->process === null) {
            $this->fail("No process is running");
            return;
        }

        if (!isset($this->process->actors[$key])) {
            $this->fail("Process doesn't have '$key' actor");
            return;
        }

        $actual = DotKey::on($this->process->actors[$key])->get($property);

        Assert::assertEquals($expected, $actual);
    }


    /**
     * Step to the next state in the process.
     *
     * @param string        $action    The action key
     * @param string|null   $response  The response key
     * @param mixed         $result
     */
    public function doAction(string $action, ?string $response = null, $result = null): void
    {
        if ($this->process === null) {
            $this->fail("No process is running");
            return;
        }

        $this->process->step($response, $result, $action, $this->actor);
    }

    /**
     * Invoke the trigger of a system action.
     *
     * @param string|null $action  The action key
     */
    public function invokeTrigger(?string $action = null): void
    {
        if ($this->process === null) {
            $this->fail("No process is running");
            return;
        }

        $response = $this->process->invokeTrigger($action, $this->actor);

        if ($response === null) {
            $state = $this->process->current->getKey();
            $this->fail("Failed to trigger '$action'" . ($this->actor !== null ? " by '$this->actor'" : '')
                . " in state '$state'");
            return;
        }

        $this->process->step($response->response, $response->data, $action, $this->actor);
    }

    /**
     * Modify a property of the process (using `setValues`).
     *
     * @param string $property
     * @param mixed  $value
     */
    public function modifyProcess(string $property, $value): void
    {
        if ($this->process === null) {
            $this->fail("No process is running");
            return;
        }

        $this->process->setValues([$property => $value]);
    }

    /**
     * Set responses for Guzzle mock.
     *
     * @param callable|Response ...$responses
     */
    public function expectHttpRequest(...$responses): void
    {
        $this->httpMock->append(...$responses);
    }

    /**
     * Clear the HTTP request history.
     */
    public function startCountingHttpRequests(): void
    {
        $this->httpTriggerHistory = [];
    }

    /**
     * Assert the number of http requests.
     *
     * @param int $count  Call number
     */
    public function seeTheNumberOfHttpRequestWere($count): void
    {
        \PHPUnit\Framework\Assert::assertCount(
            $count,
            $this->httpTriggerHistory,
            "Expected $count HTTP requests"
        );
    }

    /**
     * Get a http trigger request from history.
     *
     * @param int $i  Call number. Omit to get latest
     * @return Request
     */
    public function grabHttpRequest($i = -1): Request
    {
        $module = $this->getJasnyModule();
        $history = $module->container->get('httpHistory');

        if ($i < 0) {
            $i = count($history) + $i;
        }

        return isset($history[$i]) ? $history[$i]['request'] : null;
    }
}
