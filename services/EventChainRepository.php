<?php declare(strict_types=1);

use Improved\IteratorPipeline\Pipeline;
use GuzzleHttp\ClientInterface as HttpClient;
use GuzzleHttp\Promise;
use LTO\EventChain;
use LTO\Event;

/**
 * Service to register, load and persist event chains.
 */
class EventChainRepository
{
    /**
     * @var HttpClient
     */
    protected $client;

    /**
     * @var EventChain[]
     */
    protected $chains;

    /**
     * Latest persisted hashes per chain.
     * @var array<string,string>
     */
    protected $latestHashes;

    /**
     * @var callable
     */
    protected $createEvent;

    /**
     * Account to sign new events (our account).
     * @var LTO\Account
     */
    protected $account;

    /**
     * Class constructor.
     *
     * @param HttpClient $client
     */
    public function __construct(callable $createEvent, LTO\Account $account, HttpClient $client)
    {
        $this->client = $client;
        $this->createEvent = $createEvent;
        $this->account = $account;
    }

    /**
     * Register a known event chain.
     *
     * @param EventChain $chain
     */
    public function register(EventChain $chain): void
    {
        $this->chains[$chain->id] = clone $chain;
        $this->latestHashes[$chain->id] = $chain->getLatestHash();
    }

    /**
     * Add response as event to given chain
     *
     * @param string $id
     * @param Response $response 
     * @return LTO\Event
     */
    public function addResponse(string $id, Response $response)
    {
        $chain = $this->get($id);        
        $event = ($this->createEvent)($response, $chain->getLatestHash())->signWith($this->account);

        $chain->add($event);
        $this->update($chain);

        return $event;
    }

    /**
     * Get an event chain. Fetch it if isn't not registered.
     *
     * @param string $id
     * @return EventChain
     * @throws RuntimeException
     */
    public function get(string $id): EventChain
    {
        return isset($this->chains[$id]) ? clone $this->chains[$id] : $this->fetch($id);
    }

    /**
     * Fetch an event chain and register it.
     *
     * @param string $id
     * @return EventChain
     * @throws RuntimeException
     */
    public function fetch(string $id): EventChain
    {
        $response = $this->client->request('GET', 'event-chains/' . $id);
        $data = $this->unserializeEventChainJson((string)$response->getBody());

        $chain = new EventChain($data->id, $data->latest_hash);

        $this->register($chain);

        return $chain;
    }

    /**
     * Unserialize the event chain JSON body and assert the response data.
     *
     * @param string $json
     * @return stdClass
     * @throws UnexpectedValueException
     */
    protected function unserializeEventChainJson(string $json): stdClass
    {
        $err = null;
        $data = json_decode($json);

        if (!isset($data)) {
            $err = json_last_error_msg();
        } elseif (!isset($data->id)) {
            $err = "'id' property is missing";
        } elseif (!isset($data->latest_hash)) {
            $err = "'latest_hash' property is missing";
        }

        if ($err !== null) {
            throw new UnexpectedValueException('Received invalid JSON from event chain service. ' . $err);
        }

        return $data;
    }


    /**
     * Update an event chain, which may contain newly added events.
     * Note that the new events aren't persisted until `persist()` is called.
     *
     * @todo Detect local forks.
     *
     * @param EventChain $chain
     */
    public function update(EventChain $chain): void
    {
        if (!isset($this->chains[$chain->id])) {
            throw new BadMethodCallException("Chain '{$chain->id}' is not registered with the repository");
        }

        $this->chains[$chain->id] = $chain;
    }

    /**
     * Persist new events added to an event chain.
     *
     * @param string $id
     * @throws RuntimeException
     */
    public function persist(string $id): void
    {
        if (!isset($this->chains[$id])) {
            throw new BadMethodCallException("Chain '{$id}' is not registered with the repository");
        }

        $partialChain = $this->chains[$id]->getPartialAfter($this->latestHashes[$id]);

        if ($partialChain->events === []) {
            return;
        }

        $this->client->request('POST', 'queue', ['json' => $partialChain]);
    }

    /**
     * Persist new events added to any registered event chain.
     *
     * @throws RuntimeException
     */
    public function persistAll(): void
    {
        $promises = Pipeline::with($this->chains)
            ->map(function(EventChain $chain) {
                return $chain->getPartialAfter($this->latestHashes[$chain->id]);
            })
            ->filter(static function(EventChain $chain) {
                return $chain->events !== [];
            })
            ->map(function(EventChain $chain) {
                return $this->client->requestAsync('POST', 'queue', ['json' => $chain]);
            })
            ->toArray();

        Promise\unwrap($promises);
    }
}
