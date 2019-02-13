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
        unset($data->description);        

        foreach ($data->actions as $key => $action) {
            $data->actions[$key] = $this->decorateAction($action);            
        }

        foreach ($data->states as $key => $state) {
            $data->states[$key] = $this->decorateState($state);            
        }

        $data->assets = (object)$data->assets;
        $data->definitions = (object)$data->definitions;

        return $data;
    }

    /**
     * Decorate action data
     *
     * @param stdClass $action
     * @return stdClass
     */
    protected function decorateAction(stdClass $action): stdClass
    {
        $with = ['title' => 1, 'responses' => 1, 'url' => 1, '$schema' => 1, 'actors' => 1, 'trigger_response' => 1, 'data' => 1];

        foreach ($action as $key => $value) {
            if (!isset($with[$key])) {
                unset($action->$key);
            }
        }

        if (count($action->actors) === 1) {
            $action->actor = $action->actors[0];
            unset($action->actors);
        }

        foreach ($action->responses as $key => $response) {
            $action->responses[$key] = (object)[];
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
        $with = ['display' => 1, 'transitions' => 1, 'actions' => 1];

        foreach ($state as $key => $value) {
            if (!isset($with[$key])) {
                unset($state->$key);
            }
        }

        if (count($state->actions) === 1) {
            $state->action = $state->actions[0];
            unset($state->actions);
        }

        foreach ($state->transitions as $key => $transition) {            
            $this->transitions[$key] = $this->decorateTransition($transition);
        }

        if (count($state->transitions) === 1) {
            $state->transition = $state->transitions[0]->transition;
            unset($state->transitions);
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
        $with = ['action' => 1, 'response' => 1, 'transition' => 1];

        foreach ($transition as $key => $value) {
            if (!isset($with[$key])) {
                unset($transition->$key);
            }
        }

        return $transition;
    }
}
