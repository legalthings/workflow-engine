<?php declare(strict_types=1);

namespace JsonView;

use stdClass;

/**
 * Output Scenario in a readable way, omitting properties where possible.
 *
 * Examples;
 *   - If all responses of an action have the same value for `display`, set the `display` property for the action.
 *   - If there is only a single 'ok' response, the `responses` property for an action.
 *   - If a state has only one allowed action, use the `action` property rather than `actions`.
 *   - etc
 */
class PrettyScenarioDecorator
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
     * @param Scenario $process
     * @param stdClass $data
     * @return stdClass
     */
    public function __invoke(\Scenario $scenario, stdClass $data): stdClass
    {
        foreach ($data->actors as &$actor) {
            $actor = $this->decorateActor($actor);
        }

        foreach ($data->actions as &$action) {
            $action = $this->decorateAction($action);
        }

        foreach ($data->states as &$state) {
            $state = $this->decorateState($state);
        }

        $data->assets = (object)$data->assets;
        $data->definitions = (object)$data->definitions;

        $this->removeEmptyProperties($data, ['assets', 'definitions', 'allow_actions', 'description']);

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
        return std_object_only_with($actor, ['$schema', 'title']);
    }

    /**
     * Decorate action data
     *
     * @param stdClass $action
     * @return stdClass
     */
    protected function decorateAction(stdClass $action): stdClass
    {
        $action = std_object_only_with(
            $action, 
            ['title', 'responses', 'url', '$schema', 'actors', 'trigger_response', 'data']
        );

        if (count($action->actors) === 1) {
            $action->actor = reset($action->actors);
            unset($action->actors);
        }

        foreach ($action->responses as &$response) {
            $response = (object)[];
        }

        return $action;
    }

    /**
     * Decorate state data
     *
     * @param stdClass $state
     * @return stdClass
     */
    protected function decorateState(stdClass $state): stdClass
    {
        $state = std_object_only_with($state, ['display', 'transitions', 'actions']);

        if (count($state->actions) === 1) {
            $state->action = reset($state->actions);
            unset($state->actions);
        }

        foreach ($state->transitions as $key => $transition) {            
            $state->transitions[$key] = $this->decorateTransition($transition);
        }

        if (count($state->transitions) === 1) {
            $state->transition = $state->transitions[0]->transition;
            unset($state->transitions);
        }

        if ($state->display === 'always') {
            unset($state->display);
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
        $object = std_object_only_with($transition, ['action', 'response', 'transition']);
        $this->removeEmptyProperties($object, ['action', 'response']);

        return $object;
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
