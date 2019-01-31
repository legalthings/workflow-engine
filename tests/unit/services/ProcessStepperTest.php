<?php

use PHPUnit\Framework\MockObject\MockObject;
use Jasny\ValidationResult;
use Jasny\ValidationException;
use Jasny\EventDispatcher\EventDispatcher;

/**
 * @covers ProcessStepper
 */
class ProcessStepperTest extends \Codeception\Test\Unit
{
    /**
     * @var ProcessUpdater&MockObject
     */
    protected $updater;

    /**
     * @var ProcessStepper
     */
    protected $stepper;

    public function _before()
    {
        $this->updater = $this->createMock(ProcessUpdater::class);
        $this->stepper = new ProcessStepper($this->updater);
    }

    protected function createScenario(): Scenario
    {
        $scenario = new Scenario();
        $scenario->title = 'Do the test';

        $scenario->states[':initial'] = State::fromData([
            'title' => 'Initial state',
            'actions' => ['first'],
            'transitions' => [
                ['transition' => 'basic_step'],
            ],
        ]);
        $scenario->states['basic_step'] = State::fromData([
            'title' => 'Step 1',
            'description' => 'Describe this step',
            'timeout' => '6h',
            'actions' => ['second', 'alt'],
            'transitions' => [
                [
                    'action' => 'second',
                    'transition' => ':success',
                ],
                [
                    'action' => 'alt',
                    'transition' => 'alt_step',
                ],
                [
                    'action' => ':timeout',
                    'transition' => ':failed'
                ]
            ]
        ]);
        $scenario->states['alt_step'] = State::fromData([
            'title' => 'Alternative route',
            'actions' => ['alt', 'skip'],
            'transitions' => [
                [
                    'action' => 'alt',
                    'response' => 'cancel',
                    'transition' => ':cancelled',
                ],
                [
                    'action' => 'alt',
                    'response' => 'retry',
                    'transition' => 'basic_step',
                ],
                [
                    'action' => 'skip',
                    'transition' => ':success',
                ],
            ]
        ]);

        $scenario->actions['first'] = Action::fromData([
            'title' => 'First action',
            'actors' => ['client'],
        ]);
        $scenario->actions['second'] = Action::fromData([
            'actors' => ['manager'],
        ]);
        $scenario->actions['alt'] = Action::fromData([
            'actors' => ['client', 'manager'],
        ]);
        $scenario->actions['skip'] = Action::fromData([
            'actors' => ['manager'],
        ]);

        return $scenario;
    }

    protected function createProcess(): Process
    {
        $process = new Process();
        $process->id = '00000000-0000-0000-0000-000000000000';
        $process->scenario = $this->createScenario();

        $process->actors['client'] = Actor::fromData(['title' => 'Client']);
        $process->actors['manager'] = Actor::fromData(['title' => 'Manager']);

        $process->current = new CurrentState();
        $process->current->key = ':initial';

        $process->current->actions['first'] = clone $process->scenario->actions['first'];

        return $process;
    }

    public function testStep()
    {
        $process = $this->createProcess();

        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher->expects($this->once())->method('trigger')
            ->with('step', $this->identicalTo($process));

        $process->setDispatcher($dispatcher);

        $this->updater->expects($this->once())->method('update')
            ->with($this->identicalTo($process))
            ->willReturn(ValidationResult::success());

        $response = (new Response)->setValues([
            '$schema' => 'https://specs.livecontracts.io/v1.0.0/response/schema.json#',
            'action' => 'first',
            'key' => 'ok',
            'actor' => 'client',
            'data' => [
                'one' => 'uno',
                'two' => 'dos',
            ],
        ]);

        $this->stepper->step($process, $response);
    }

    public function validationProvider()
    {
        return [
            ['non_existent', 'ok', 'client', ["Unknown action 'non_existent'"]],
            ['first', 'ok', 'robot', ["Unknown actor 'robot'"]],
            ['alt', 'ok', 'client', ["Action 'alt' isn't allowed in state ':initial'"]],
            ['first', 'yo', 'client', ["Invalid response 'yo' for action 'first'"]],
            ['first', 'ok', 'manager', ["Manager isn't allowed to perform action 'first'"]],
            ['first', 'yo', 'manager', [
                "Invalid response 'yo' for action 'first'",
                "Manager isn't allowed to perform action 'first'",
            ]],
        ];
    }

    /**
     * @dataProvider validationProvider
     */
    public function testStepValidation(string $action, string $response, string $actor, array $errors)
    {
        $process = $this->createProcess();

        $response = (new Response)->setValues([
            '$schema' => 'https://specs.livecontracts.io/v1.0.0/response/schema.json#',
            'action' => $action,
            'key' => $response,
            'actor' => $actor,
        ]);

        try {
            $this->stepper->step($process, $response);
        } catch (ValidationException $exception) {
            $this->assertEquals($errors, $exception->getErrors());
            return;
        }

        $this->fail('Validation exception not thrown');
    }
}
