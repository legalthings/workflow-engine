<?php

use Jasny\EventDispatcher\EventDispatcher;

/**
 * @covers ProcessInstantiator
 */
class ProcessInstantiatorTest extends \Codeception\Test\Unit
{
    protected function createScenario(): Scenario
    {
        $scenario = new Scenario();
        $scenario->title = 'Do the test';

        $scenario->actors['manager'] = new JsonSchema([
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'title' => 'Manager',
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'name' => ['type' => 'string'],
                'email' => ['type' => 'string', 'format' => 'email'],
            ]
        ]);
        $scenario->actors['client'] = new JsonSchema([
            'title' => 'Client', 
            'type' => 'object'
        ]);

        $scenario->states['initial'] = new State();
        $scenario->states['step1'] = new State();
        $scenario->states['step2'] = new State();

        $scenario->assets['doc'] = new JsonSchema([
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'title' => 'Document',
            'description' => 'The final document',
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'content' => ['type' => 'string'],
            ],
        ]);
        $scenario->assets['book'] = new JsonSchema(['title' => 'Book', 'type' => 'object']);
        $scenario->assets['attachments'] = new JsonSchema([
            'title' => 'Attachments',
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'files' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'url' => ['type' => 'string', 'format' => 'uri'],
                        ],
                    ],
                ],
            ],
        ]);

        $scenario->definitions['d1'] = Asset::fromData(['I' => 'one', 'II' => 'two']);
        $scenario->definitions['d2'] = Asset::fromData(['A' => 'alpha', 'B' => 'Beta']);

        return $scenario;
    }

    public function testInstantiate(): void
    {
        $scenario = $this->createScenario();

        $dispatcher = $this->createMock(EventDispatcher::class);
        $process = new Process();
        $process->setDispatcher($dispatcher);

        $processGateway = $this->createMock(ProcessGateway::class);
        $processGateway->expects($this->once())->method('create')->willReturn($process);

        $currentState = $this->createMock(CurrentState::class);
        $stateInstatiator = $this->createMock(StateInstantiator::class);
        $stateInstatiator->expects($this->once())->method('instantiate')
            ->with($scenario->states['initial'])->willReturn($currentState);

        $dispatcher->expects($this->once())->method('trigger')
            ->with('instantiate', $this->identicalTo($process));

        $instantiator = new ProcessInstantiator($processGateway, $stateInstatiator);

        $process = $instantiator->instantiate($scenario);

        $this->assertSame($scenario, $process->scenario);
        $this->assertEquals('Do the test', $process->title);

        $this->assertAssets($process->assets);

        // Definitions should be a clone from scenario
        $this->assertEquals($scenario->definitions, $process->definitions);
        $this->assertNotSame($scenario->definitions->getArrayCopy(), $process->definitions->getArrayCopy());

        $this->assertSame($currentState, $process->current);
    }

    /**
     * @param AssocEntitySet&iterable<Actor> $actors
     */
    protected function assertActors($actors, Identity $identity): void
    {
        $this->assertInstanceOf(AssocEntitySet::class, $actors);
        $this->assertEquals(Actor::class, $actors->getEntityClass());
        $this->assertCount(2, $actors);

        $this->assertArrayHasKey('manager', $actors->getArrayCopy());
        $this->assertInstanceOf(Actor::class, $actors['manager']);
        $this->assertEquals('Manager', $actors['manager']->title);
        $this->assertSame(null, $actors['manager']->identity);

        $this->assertArrayHasKey('client', $actors->getArrayCopy());
        $this->assertInstanceOf(Actor::class, $actors['client']);
        $this->assertEquals('Client', $actors['client']->title);
        $this->assertSame(null, $actors['client']->identity);
    }

    /**
     * @param AssetSet&iterable<Asset> $assets
     */
    protected function assertAssets($assets): void
    {
        $this->assertInstanceOf(AssocEntitySet::class, $assets);
        $this->assertEquals(Asset::class, $assets->getEntityClass());
        $this->assertCount(3, $assets);

        $this->assertArrayHasKey('doc', $assets->getArrayCopy());
        $this->assertInstanceOf(Asset::class, $assets['doc']);
        $this->assertEquals('Document', $assets['doc']->title);
        $this->assertEquals('The final document', $assets['doc']->description);
        $this->assertSame(null, $assets['doc']->content);

        $this->assertArrayHasKey('book', $assets->getArrayCopy());
        $this->assertInstanceOf(Asset::class, $assets['book']);
        $this->assertEquals('Book', $assets['book']->title);

        $this->assertArrayHasKey('attachments', $assets->getArrayCopy());
        $this->assertInstanceOf(Asset::class, $assets['attachments']);
        $this->assertEquals('Attachments', $assets['attachments']->title);
        $this->assertSame([], $assets['attachments']->files);
    }
}
