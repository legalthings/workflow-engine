<?php

use Jasny\ValidationResult;

/**
 * @covers Action
 */
class ActionTest extends \Codeception\Test\Unit
{
    /**
     * @var Action
     **/
    protected $action;

    /**
     * Execute before each test case
     */
    public function _before()
    {
        $this->action = new Action();
    }

    /**
     * Test '__construct' method
     */
    public function testConstruct()
    {
        $this->assertInstanceOf(AssocEntitySet::class, $this->action->responses);
        $this->assertCount(1, $this->action->responses);
        $this->assertInstanceOf(AvailableResponse::class, $this->action->responses['ok']);
    }

    /**
     * Test 'cast' method
     */
    public function testCast()
    {
        $this->action->actors = 'foo';

        $result = $this->action->cast();

        $this->assertSame($this->action, $result);
        $this->assertEquals(['foo'], $result->actors);

        $result2 = $result->cast();
        $this->assertEquals(['foo'], $result2->actors);
    }

    /**
     * Test 'validate' method
     */
    public function testValidate()
    {
        $responses = [
            'ok' => $this->createMock(AvailableResponse::class),
            'error' => $this->createMock(AvailableResponse::class)
        ];

        $responses['ok']->expects($this->once())->method('validate')->willReturn(ValidationResult::success());
        $responses['error']->expects($this->once())->method('validate')->willReturn(ValidationResult::success());

        $this->action->responses = $responses;
        $result = $this->action->validate();

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertTrue($result->succeeded());
    }

    /**
     * Test 'validate' method, if there's no default response
     */
    public function testValidateNoDefaultResponse()
    {
        $responses = [
            'success' => $this->createMock(AvailableResponse::class),
            'error' => $this->createMock(AvailableResponse::class)
        ];

        $responses['success']->expects($this->once())->method('validate')->willReturn(ValidationResult::success());
        $responses['error']->expects($this->once())->method('validate')->willReturn(ValidationResult::success());

        $this->action->responses = $responses;
        $result = $this->action->validate();

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertTrue($result->failed());
        $this->assertSame("Action doesn't have a 'ok' response.", $result->getError());
    }

    /**
     * Test 'validate' method, if there's an error while validating response
     */
    public function testValidateResponseValidationFailed()
    {
        $responses = [
            'ok' => $this->createMock(AvailableResponse::class),
            'error' => $this->createMock(AvailableResponse::class),
            'foo' => $this->createMock(AvailableResponse::class)
        ];

        $responses['ok']->expects($this->once())->method('validate')->willReturn(ValidationResult::error('is wrong'));
        $responses['error']->expects($this->once())->method('validate')->willReturn(ValidationResult::success());
        $responses['foo']->expects($this->once())->method('validate')->willReturn(ValidationResult::error('is really wrong'));

        $this->action->responses = $responses;
        $result = $this->action->validate();

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertTrue($result->failed());
        $this->assertSame(["'ok' response is wrong", "'foo' response is really wrong"], $result->getErrors());
    }

    /**
     * Provide data for testing 'isValidResponse' method
     *
     * @return array
     */
    public function isValidResponseProvider()
    {
        return [
            ['ok', true],
            ['error', true],
            ['foo', false]
        ];
    }

    /**
     * Test 'isValidResponse' method
     *
     * @dataProvider isValidResponseProvider
     */
    public function testIsValidResponse($key, $expected)
    {
        $responses = [
            'ok' => $this->createMock(AvailableResponse::class),
            'error' => $this->createMock(AvailableResponse::class)
        ];

        $this->action->responses = $responses;
        $result = $this->action->isValidResponse($key);

        $this->assertSame($expected, $result);
    }

    /**
     * Provide data for testing 'isAllowedBy' method
     *
     * @return array
     */
    public function isAllowedByProvider()
    {
        return [
            ['user', true],
            ['system', true],
            ['foo', false]
        ];
    }

    /**
     * Test 'isAllowedBy' method
     *
     * @dataProvider isAllowedByProvider
     */
    public function testIsAllowedBy($key, $expected)
    {
        $actor = $this->createMock(Actor::class);
        $actor->key = $key;

        $this->action->actors = ['user', 'system'];

        $result = $this->action->isAllowedBy($actor);

        $this->assertSame($expected, $result);
    }

    /**
     * Provide data for testing 'isAllowedBy' method, if exception should be thrown
     *
     * @return array
     */
    public function isAllowedByExceptionProvider()
    {
        $actor = $this->createMock(Actor::class);
        $actor->key = null;

        return [
            [$this->createMock(Actor::class)],
            [$actor]
        ];
    }

    /**
     * Test 'isAllowedBy' method, if exception should be thrown
     *
     * @dataProvider isAllowedByExceptionProvider
     * @expectedException UnexpectedValueException
     * @expectedExceptionMessage Actor key not set
     */
    public function testIsAllowedByException($actor)
    {
        $this->action->actors = ['user', 'system'];

        $this->action->isAllowedBy($actor);
    }

    /**
     * Test 'getResponse' method
     */
    public function testGetResponse()
    {
        $responses = [
            'ok' => $this->createMock(AvailableResponse::class),
            'error' => $this->createMock(AvailableResponse::class)
        ];

        $this->action->responses = $responses;
        $result = $this->action->getResponse('error');

        $this->assertInstanceOf(AvailableResponse::class, $result);
        $this->assertSame($responses['error'], $result);        
    }

    /**
     * Test 'getResponse' method, if key is empty string
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Key should not be empty
     */
    public function testGetResponseEmptyKey()
    {
        $responses = [
            'ok' => $this->createMock(AvailableResponse::class),
            'error' => $this->createMock(AvailableResponse::class)
        ];

        $this->action->responses = $responses;
        $this->action->getResponse('');
    }

    /**
     * Test 'getResponse' method, if response with this key is not set
     *
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Action 'bar' doesn't have a 'foo' response
     */
    public function testGetResponseWrongKey()
    {
        $responses = [
            'ok' => $this->createMock(AvailableResponse::class),
            'error' => $this->createMock(AvailableResponse::class)
        ];

        $this->action->key = 'bar';
        $this->action->responses = $responses;

        $this->action->getResponse('foo');
    }

    /**
     * Provide data for testing '__toString' method
     *
     * @return array
     */
    public function toStringProvider()
    {
        return [
            ['foo', 'foo'],
            ['', ''],
            [null, '[action object]']
        ];
    }

    /**
     * Test '__toString' method
     *
     * @dataProvider toStringProvider
     */
    public function testToString($key, $expected)
    {
        $this->action->key = $key;

        $result = (string)$this->action;

        $this->assertSame($expected, $result);
    }

    /**
     * Test 'fromData' method
     */
    public function testFromData()
    {
        $data = [
            'key' => 'foo',
            'title' => 'Foo action',
            'label' => 'Foo label',
            'description' => 'Foo description',
            'actors' => ['user', 'system'],
            'display' => 'always',
            'update' => [
                [
                    'select' => 'test.foo.bar',
                    'data' => ['foo' => 'bar'],
                    'patch' => true,
                    'projection' => 'foo.bar.baz'
                ],
                [
                    'select' => 'test2.foo.zoo',
                    'data' => ['zoo' => 'baz'],
                    'projection' => 'foo.bar.zoo'
                ],
            ],
            'responses' => [
                [
                    'key' => 'ok'
                ],
                [
                    'key' => 'error'
                ]
            ]
        ];

        $result = Action::fromData($data);

        $this->assertInstanceOf(Action::class, $result);

        $values = $result->getValues();                
        $simpleValues = array_without($values, ['responses']);
        $simpleExpected = array_without($data, ['responses', 'display', 'update']);
        $simpleExpected = array_merge($simpleExpected, [
            'schema' => 'https://specs.livecontracts.io/v1.0.0/action/schema.json#',
            'condition' => true,
            'default_response' => 'ok'
        ]);

        $responses = $values['responses'];

        $this->assertEquals($simpleExpected, $simpleValues);
        $this->assertCount(2, $responses);
        $this->assertInstanceOf(AssocEntitySet::class, $responses);
        $this->assertInstanceOf(AvailableResponse::class, $responses['ok']);
        $this->assertInstanceOf(AvailableResponse::class, $responses['error']);
        $this->assertSame('ok', $responses['ok']->key);
        $this->assertSame('error', $responses['error']->key);

        $this->assertInstanceOf(Jasny\DB\EntitySet::class, $responses['ok']->update);
        $this->assertEquals($data['update'][0], $responses['ok']->update[0]->getValues());
        $this->assertEquals($data['update'][1] + ['patch' => false], $responses['ok']->update[1]->getValues());

        $this->assertEquals($responses['ok']->update, $responses['error']->update);        
    }

    /**
     * Test 'fromData' method, when converting 'actor' property to 'actors'
     */
    public function testFromDataActor()
    {
        $data = [
            'actor' => 'user'
        ];

        $result = Action::fromData($data);

        $this->assertSame(['user'], $result->actors);
        $this->assertFalse(isset($result->actor));
    }
}
