<?php

declare(strict_types=1);

/**
 * Normalize scenario from pretty format.
 *
 * Also convert old formats to new.
 * Works both for data loaded from the DB and input data.
 *
 * Supported old formats;
 *  - v0.2
 */
class ScenarioNormalizer
{
    public const CURRENT_SCHEMA = "https://specs.letsflow.io/v0.3.0/scenario#";

    /**
     * Apply conversion to input data.
     *
     * @param array $values
     * @return array
     */
    public function convertInput(array $values): array
    {
        $values = array_rename_key($values, '$schema', 'schema');
        $values['states'] = $values['states'] ?? [];

        if (isset($values['schema']) && $values['schema'] !== self::CURRENT_SCHEMA) {
            $values = $this->convertBC($values);
        }

        $this->normalize($values);

        return $values;
    }

    /**
     * Apply conversion to data loaded from DB.
     *
     * @param array $values
     * @return array
     */
    public function convertData(array $values): array
    {
        if (isset($values['schema']) && $values['schema'] !== self::CURRENT_SCHEMA) {
            $values = $this->convertBC($values);
        }

        return $values;
    }

    /**
     * Convert old scenario format to new.
     *
     * @param array $values
     * @return array
     */
    protected function convertBC(array $values): array
    {
        foreach ($values['states'] as &$state) {
            $state = $this->convertState((array)$state);
        }

        return $values;
    }

    /**
     * Convert a transition.
     *
     * @param array $state
     * @return array
     */
    protected function convertState(array $state): array
    {
        if (isset($state['action']) && isset($state['transition'])) {
            $state['transitions'] = [
                ['on' => $state['action'], 'goto' => $state['transition']],
            ];

            unset($state['action'], $state['transition']);
            return $state;
        }

        if (!isset($state['transitions'])) {
            return $state;
        }

        $actions = (array)($state['actions'] ?? $state['action'] ?? []);
        unset($state['actions'], $state['action']);

        $newTransitions = [];

        foreach ($state['transitions'] as $transition) {
            array_rename_key($state, 'transition', 'goto');

            if (isset($transition['action'])) {
                $on = $transition['action'] . (isset($transition['response']) ? '.' . $transition['response'] : '');
                $newTransitions[] = ['on' => $on] + array_without($transition, ['action', 'response']);
            } else {
                foreach ($actions as $action) {
                    $on = $action . (isset($transition['response']) ? '.' . $transition['response'] : '');
                    $newTransitions[] = ['on' => $on] + array_without($transition, ['action', 'response']);
                }
            }
        }

        $state['transitions'] = $newTransitions;

        return $state;
    }


    /**
     * Convert data from pretty JSON to standardized format.
     *
     * @param array $values
     * @return array
     */
    protected function normalize(array $values): array
    {
        foreach ($values['states'] as &$state) {
            $this->normalizeState($state);
        }

        return $values;
    }

    /**
     * Convert data from pretty JSON to standardized format for state.
     *
     * @param array $values
     * @return array
     */
    protected function normalizeState(array $values): array
    {
        if (isset($values['on']) && isset($values['goto'])) {
            $values['transitions'] = [
                ['on' => $values['on'], 'goto' => $values['goto']]
            ];
            unset($values['on'], $values['goto']);
        }

        return $values;
    }
}
