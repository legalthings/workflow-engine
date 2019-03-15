<?php declare(strict_types=1);

use Improved as i;

/**
 * Instantiate a process from a scenario.
 * @immutable
 */
class ProcessInstantiator
{
    /**
     * @var ProcessGateway
     */
    protected $gateway;

    /**
     * @var IdentityGateway
     **/
    protected $identityGateway;

    /**
     * @var StateInstantiator
     */
    protected $stateInstantiator;


    /**
     * Class constructor.
     *
     * @param ProcessGateway    $gateway
     * @param StateInstantiator $stateInstantiator
     */
    public function __construct(
        ProcessGateway $gateway, 
        StateInstantiator $stateInstantiator, 
        IdentityGateway $identityGateway
    )
    {
        $this->gateway = $gateway;
        $this->identityGateway = $identityGateway;
        $this->stateInstantiator = $stateInstantiator;        
    }


    /**
     * Instantiate a process from a scenario.
     *
     * @param Scenario $scenario
     * @return Process
     */
    public function instantiate(Scenario $scenario): Process
    {
        $process = $this->gateway->create();

        $process->scenario = $scenario;
        $process->schema = str_replace('/scenario/', '/process/', $scenario->schema);
        $process->title = $scenario->title;

        $process->actors = $this->instantiateActors($scenario);
        $process->assets = $this->instantiateAssets($scenario);
        $process->definitions = clone $scenario->definitions;

        $initialState = $scenario->getState(':initial');
        $process->current = $this->stateInstantiator->instantiate($initialState, $process);

        $process->dispatch('instantiate');

        return $process;
    }

    /**
     * Instantiate the process actors.
     *
     * @param Scenario $scenario
     * @return AssocEntitySet&iterable<Actor>
     */
    protected function instantiateActors(Scenario $scenario): AssocEntitySet
    {
        $actors = [];

        foreach ($scenario->actors as $key => $schema) {
            $actor = i\type_check(
                $schema->build(),
                'object',
                new InvalidArgumentException("Invalid JSON Schema for actor '$key'; got %s.")
            );

            $actor->title = $schema->title;

            if (isset($schema->signkeys)) {
                $actor->signkeys = $schema->signkeys;
            }
            if (isset($schema->identity)) {
                $actor->identity = $this->identityGateway->fetch($schema->identity);

                if (!isset($actor->identity)) {
                    throw new Exception("Identity with id {$schema->identity} not found");
                }
            }K

            $actors[$key] = $actor;
        }

        return AssocEntitySet::forClass(Actor::class, $actors);
    }

    /**
     * Instantiate the process assets.
     *
     * @param Scenario $scenario
     * @return AssocEntitySet&iterable<Asset>
     */
    protected function instantiateAssets(Scenario $scenario): AssocEntitySet
    {
        $assets = [];

        foreach ($scenario->assets as $key => $schema) {
            $asset = $schema->build();
            $asset->title = $schema->title;
            $asset->description = $schema->description;

            $assets[$key] = $asset;
        }

        return AssocEntitySet::forClass(Asset::class, $assets);
    }
}
