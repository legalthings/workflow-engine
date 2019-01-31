<?php declare(strict_types=1);

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request;
use LTO\EventChain;
use function GuzzleHttp\Psr7\stream_for;

/**
 * Interface to the LegalEvents API
 */
class LegalEvents
{
    /**
     * @var HttpClient
     */
    protected $client;

    /**
     * @var string
     */
    protected $baseUri;


    /**
     * Class constructor
     *
     * @param HttpClient     $client
     * @param string         $url             "config.legalevent.url"
     * @param SessionManager $sessionManager
     * @throws ConfigException
     */
    public function __construct(HttpClient $client, string $url)
    {
        if (!in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new ConfigException("Invalid LegalEvent url '$url'");
        }

        $this->client = $client;
        $this->baseUri = substr($url, -1) !== '/' ? "$url/" : $url;
    }

    /**
     * Create request based on event chain
     * 
     * @param EventChain $chain
     * @return Request
     */
    public function createRequest(EventChain $chain): Request
    {        
        $request = new Request('POST', 'event-chains');
        $request->withHeader('Content-Type', 'application/json');

        $body = stream_for(json_encode($chain));
        $request = $request->withBody($body);
        
        return $request;
    }
    
    /**
     * Send a psr7 request to the event service
     * 
     * @param Request $request
     */
    public function send($request): void
    {
        try {
            $this->client->send($request, ['base_uri' => $this->baseUri]);
        } catch (ClientException $exception) {
            throw new Exception("Failed to send message to legalevents service", $exception);
        }
    }
}
