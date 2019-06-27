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
     * Test default values   
     */
    public function testDefault()
    {
        $this->assertSame('always', $this->response->display);
        $this->assertSame('ok', $this->response->key);
    }

    /**
     * Test 'cast' method
     */
    public function testCastAction()
    {
        $this->response->action = 'foo';
        $this->response->actor = 'bar';
        $this->response->key = null;

        $this->response->cast();

        $this->assertInstanceOf(Action::class, $this->response->action);
        $this->assertInstanceOf(Actor::class, $this->response->actor);
        $this->assertSame('foo', $this->response->action->key);
        $this->assertSame('bar', $this->response->actor->key);
        $this->assertSame('ok', $this->response->key);
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
