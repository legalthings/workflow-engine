<?php

/**
 * @covers Response
 */
class ResponseTest extends \Codeception\Test\Unit
{
    /**
     * @var Response
     **/
    protected $response;

    /**
     * Perform actions before each test case
     */
    public function _before()
    {
        $this->response = new Response();
    }

    /**
     * Test `display` property default value   
     */
    public function testDisplayDefault()
    {
        $this->assertSame('always', $this->response->display);
    }

    /**
     * Test 'cast' method for 'action' property
     */
    public function testCastAction()
    {
        $this->response->action = 'foo';
        $this->response->cast();

        $this->assertInstanceOf(Action::class, $this->response->action);
        $this->assertSame('foo', $this->response->action->key);
    }

    /**
     * Test 'cast' method for 'actor' property
     */
    public function testCastActor()
    {
        $this->response->actor = 'foo';
        $this->response->cast();

        $this->assertInstanceOf(Actor::class, $this->response->actor);
        $this->assertSame('foo', $this->response->actor->key);
    }

    /**
     * Provide data for testing 'getRef' method
     *
     * @return array
     */
    public function getRefProvider()
    {
        return [
            ['bar', 'foo.bar'],
            [null, 'foo']
        ];
    }

    /**
     * Test 'getRef' method
     *
     * @dataProvider getRefProvider
     */
    public function testGetRef($key, $expected)
    {
        $this->response->action = (object)['key' => 'foo'];
        $this->response->key = $key;

        $result = $this->response->getRef();

        $this->assertSame($expected, $result);
    }
}
