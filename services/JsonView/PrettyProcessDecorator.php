<?php declare(strict_types=1);

namespace JsonView;

use \stdClass;

/**
 * Output Process in a readable way, omitting properties where possible.
 */
class PrettyProcessDecorator
{
    /**
     * Get the options for json_encode.
     *
     * @return int
     */
    public function getJsonOptions(): int
    {
        return \JSON_PRETTY_PRINT;
    }

    /**
     * Apply decorator to data.
     *
     * @param \Process $process
     * @param \stdClass $data
     */
    public function __invoke(\Process $process, stdClass $data)
    {
        foreach ($data->actors as &$actor) {
            $actor = $this->decorateActor($actor);
        }

        foreach ($data->previous as &$response) {
            $response = $this->decorateResponse($response);
        }

        if (is_array($data->next) && $data->next !== []) {
            foreach ($data->next as &$nextState) {
                $nextState = $this->decorateNextState($nextState);
            }            
        }

        if (isset($data->current)) {
            $data->current = $this->decorateCurrentState($data->current);   
        }

        $this->removeEmptyProperties($data, ['chain', 'assets', 'definitions', 'next']);

        $data->scenario = $data->scenario->id;
        $data = object_rename_key($data, 'schema', '$schema');

        return $data;
    }

    /**
     * Decorate actor data
     *
     * @param stdClass $actor
     * @return stdClass
     */
    protected function decorateActor(stdClass $actor): stdClass
    {
        unset($actor->key);

        if (isset($actor->identity)) {
            $actor->identity = $this->decorateIdentity($actor->identity);
        }

        return $actor;
    }

    protected function decorateIdentity(stdClass $identity): stdClass
    {
        $this->removeEmptyProperties($identity, ['node', 'signkeys', 'encryptkey']);

        return $identity;
    }

    /**
     * Decorate response data
     *
     * @param stdClass $response
     * @return stdClass
     */
    protected function decorateResponse(stdClass $response): stdClass
    {
        $response = std_object_only_with($response, ['title', 'action', '$schema', 'actor', 'key', 'data', 'display']);

        unset($response->actor->{'$schema'});

        $response->actor = $this->decorateActor($response->actor);
        $response->action = $response->action->key;

        return $response;
    }

    /**
     * Decorate next state data
     *
     * @param stdClass $nextState
     * @return stdClass
     */
    protected function decorateNextState(stdClass $nextState): stdClass
    {
        $nextState = std_object_only_with($nextState, ['key', 'actors', 'display']);   

        if (count($nextState->actors) === 1) {
            $nextState->actor = reset($nextState->actors);
            unset($nextState->actors);
        }

        return $nextState;
    }

    /**
     * Decorate state data
     *
     * @param stdClass $state
     * @return stdClass
     */
    protected function decorateCurrentState(stdClass $state): stdClass
    {
        if (in_array($state->key, [':success', ':failed', ':cancelled'])) {
            return (object)['key' => $state->key];
        }

        $state = std_object_only_with($state, ['key', 'display', 'transitions', 'actions', 'actor']);

        if (isset($state->display) && $state->display === 'always') {
            unset($state->display);
        }

        foreach ($state->actions as &$action) {
            $action = $action->key;
        }

        if (count((array)$state->actions) === 1) {
            $state->action = reset($state->actions);
            unset($state->actions);
        }

        foreach ($state->transitions as &$transition) {
            $transition = $this->decorateTransition($transition);
        }

        if (count($state->transitions) === 1) {
            $state->goto = $state->transitions[0]->goto;
            unset($state->transitions);
        }

        if (isset($state->actor)) {
            $state->actor = $state->actor->key;
        } else {
            unset($state->actor);
        }

        return $state;
    }

    /**
     * Decorate state transition data
     *
     * @param stdClass $transition
     * @return stdClass
     */
    protected function decorateTransition(stdClass $transition): stdClass
    {
        return std_object_only_with($transition, ['on', 'goto']);
    }

    /**
     * Remove all properties that are `null` or an empty array.
     *
     * @param stdClass $data
     * @param string[] $properties
     */
    protected function removeEmptyProperties(stdClass $data, array $properties): void
    {
        foreach ($properties as $prop) {
            $value = $data->$prop ?? null;

            if ($value === null || $value === [] || ($value instanceof stdClass &&  $value == (object)[])) {
                unset($data->$prop);
            }
        }
    }
}
