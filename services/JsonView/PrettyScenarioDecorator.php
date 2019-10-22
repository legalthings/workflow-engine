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
            $response = $this->decorateResponse($response);            
        }

        return $action;
    }

    /**
     * Decorate response
     *
     * @param stdClass $response
     * @return stdClass
     */
    protected function decorateResponse(stdClass $response): stdClass
    {
        unset($response->key);
        if (isset($response->display) && $response->display === 'always') {
            unset($response->display);
        }

        foreach ($response->update ?? [] as $key => $update) {            
            $response->update[$key] = $this->decorateUpdateInstruction($update);
        }

        if (isset($response->update) && count($response->update) === 1 && is_string($response->update[0])) {
            $response->update = $response->update[0];
        }

        $this->removeEmptyProperties($response, ['title', 'update']);

        return $response;
    }

    /**
     * Decorate update instruction
     *
     * @param stdClass $update
     * @return stdClass|string
     */
    protected function decorateUpdateInstruction(stdClass $update)
    {
        $prettify = !empty($update->select) &&
            (!isset($update->patch) || (bool)$update->patch === true) &&
            !isset($update->data) &&
            !isset($update->projection);

        $update = $prettify ? $update->select : $update;

        if (!is_string($update)) {
            $this->removeEmptyProperties($update, ['data', 'projection']);
        }

        return $update;
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

        foreach ($state->transitions as $key => $transition) {
            $state->transitions[$key] = $this->decorateTransition($transition);
        }

        if (count($state->transitions) === 1) {
            $state->on = $state->transitions[0]->on;
            $state->goto = $state->transitions[0]->goto;
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
        $object = std_object_only_with($transition, ['on', 'goto', 'condition']);
        $this->removeEmptyProperties($object, ['on', 'goto', 'condition']);

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

            if ($value === null || $value === [] || ($value instanceof stdClass && $value == (object)[])) {
                unset($data->$prop);
            }
        }
    }
}
