<?php declare(strict_types=1);

use Jasny\EventDispatcher\EventDispatcher;

/**
 * Instantiate a process from a scenario.
 * @immutable
 */
class ProcessInstantiator
{
    /**
     * @var StateInstantiator
     */
    protected $stateInstantiator;


    /**
     * Class constructor.
     *
     * @param StateInstantiator $stateInstantiator
     * @param EventDispatcher   $dispatcher
     */
    public function __construct(StateInstantiator $stateInstantiator, EventDispatcher $dispatcher)
    {
        $this->stateInstantiator = $stateInstantiator;
        $this->dispatcher = $dispatcher;
    }


    /**
     * Instantiate a process from a scenario.
     *
     * @param Scenario $scenario
     * @return Process
     */
    public function instantiate(Scenario $scenario): Process
    {
        $process = Process();
        $process->setDispatcher($this->dispatcher);

        $process->scenario = $scenario;
        $process->schema = str_replace('/scenario/', '/process/', $scenario->schema);
        $process->title = $scenario->title;

        $process->info = Asset::fromData($scenario->info->build());
        $process->actors = $this->instantiateActors($scenario);
        $process->assets = $this->instantiateAssets($scenario);
        $process->definitions = clone $scenario->definitions;

        $initialState = $scenario->getState(':initial');
        $process->current = $this->stateInstantiator->instantiate($initialState, $process);

        $this->dispatcher->trigger('instantiate', $process);

        return $process;
    }

    /**
     * Instantiate the process actors.
     *
     * @param Scenario $scenario
     * @return AssocEntitySet
     */
    protected function instantiateActors(Scenario $scenario): AssocEntitySet
    {
        $actors = [];

        foreach ($scenario->actors as $key => $schema) {
            $actors[$key] = $schema->build();
        }

        return AssocEntitySet::forClass(Actor::class, $actors);
    }

    /**
     * Instantiate the process assets.
     *
     * @param Scenario $scenario
     * @return AssocEntitySet
     */
    protected function instantiateAssets(Scenario $scenario): AssetSet
    {
        $assets = [];

        foreach ($scenario->assets as $key => $schema) {
            $assets[$key] = $schema->build();
        }

        return new AssetSet($assets);
    }
}
