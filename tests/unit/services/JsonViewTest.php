<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @covers JsonView
 */
class JsonViewTest extends \Codeception\Test\Unit
{
    use Jasny\TestHelper;

    /**
     * Test 'withDecorator' method
     */
    public function testWithDecorator()
    {
        $view = new JsonView(['foo' => 'bar', 'baz' => 'bad']);
        $result = $view->withDecorator('baz');

        $this->assertInstanceOf(JsonView::class, $result);

        $result2 = $result->withDecorator('baz');
        $this->assertSame($result, $result2);
    }

    /**
     * Test 'withDecorator' method, when decorator is not available
     *
     * @expectedException DomainException
     * @expectedExceptionMessage Unknown decorator 'baz' for JSON encoder
     */
    public function testWithDecoratorNotAvailable()
    {
        $view = new JsonView(['foo' => 'bar']);
        $view->withDecorator('baz');
    }

    /**
     * Test 'withoutDecorator' method
     */
    public function testWithoutDecorator()
    {
        $view = new JsonView(['foo' => 'bar', 'baz' => 'bad']);        
        $this->setPrivateProperty($view, 'decorators', ['foo' => 'bar', 'baz' => 'bad']);

        $result = $view->withoutDecorator('baz');

        $this->assertInstanceOf(JsonView::class, $result);
        $this->assertAttributeEquals(['foo' => 'bar', 'baz' => 'bad'], 'decorators', $view);

        $result2 = $result->withoutDecorator('baz');

        $this->assertSame($result, $result2);
    }

    /**
     * Test 'withoutDecorator' method, when decorator is not available
     *
     * @expectedException DomainException
     * @expectedExceptionMessage Unknown decorator 'baz' for JSON encoder
     */
    public function testWithoutDecoratorNotAvailable()
    {
        $view = new JsonView(['foo' => 'bar']);        
        $this->setPrivateProperty($view, 'decorators', ['foo' => 'bar']);

        $result = $view->withoutDecorator('baz');
    }

    /**
     * Provide data for testing 'output' method
     *
     * @return array
     */
    public function outputProvider()
    {
        list($iterator, $object, $serializable) = $this->getData();        

        return [
            [
                ['zoo' => '&baz "bar"'], 
                ['zoo' => '&baz "bar"'], 
                json_encode(
                    ['zoo' => '&baz "bar"', 'dec1' => true, 'dec3' => true, 'dec4' => true],
                    JSON_HEX_AMP | JSON_HEX_QUOT
                )
            ],
            [
                (object)['zoo' => '&baz "bar"'], 
                (object)['zoo' => '&baz "bar"'], 
                json_encode(
                    ['zoo' => '&baz "bar"', 'dec1' => true, 'dec3' => true, 'dec4' => true],
                    JSON_HEX_AMP | JSON_HEX_QUOT
                )
            ],
            [
                $iterator, 
                ['key1' => 'value1', 'key2' => 'value2'], 
                json_encode(
                    ['key1' => 'value1', 'key2' => 'value2', 'dec1' => true, 'dec3' => true, 'dec4' => true]
                )
            ],
            [
                $object,
                (object)['test' => 'a', 'test2' => 'b'],
                json_encode(
                    ['test' => 'a', 'test2' => 'b', 'dec1' => true, 'dec3' => true, 'dec4' => true]
                )
            ],
            [
                $serializable,
                [
                    'zoo' => '&baz "bar"', 
                    'iterator' => ['key1' => 'value1', 'key2' => 'value2'],
                    'nested' => (object)['object' => (object)['test' => 'a', 'test2' => 'b']]
                ],
                json_encode(
                    [
                        'zoo' => '&baz "bar"', 
                        'iterator' => ['key1' => 'value1', 'key2' => 'value2'],
                        'nested' => ['object' => ['test' => 'a', 'test2' => 'b']],
                        'dec1' => true, 
                        'dec3' => true,
                        'dec4' => true
                    ],
                    JSON_HEX_AMP | JSON_HEX_QUOT
                ),
                '12345'
            ]
        ];
    }

    /**
     * Test 'output' method
     *
     * @dataProvider outputProvider
     */
    public function testOutput($data, $expectedSerialized, $expectedJson, $lastModified = null)
    {
        $decorators = $this->getDecorators();

        $view = $this->createPartialMock(JsonView::class, ['calculateEtag']);        
        $this->setPrivateProperty($view, 'availableDecorators', $decorators);

        $view = $view->withDecorator('foo')->withDecorator('zoo')->withDecorator('baz');
        $view->expects($this->once())->method('calculateEtag')->with($expectedSerialized, 'W/')->willReturn('foo-e-tag');

        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())->method('withStatus')->with(200)->willReturn($response);

        if ($lastModified) {
            $response->expects($this->exactly(3))->method('withHeader')->withConsecutive(
                ['Content-Type', 'application/json; charset=utf-8'],
                ['ETag', 'foo-e-tag'],
                ['Last-Modified', $lastModified]
            )->willReturn($response);          
        } else {
            $response->expects($this->exactly(2))->method('withHeader')->withConsecutive(
                ['Content-Type', 'application/json; charset=utf-8'],
                ['ETag', 'foo-e-tag']
            )->willReturn($response);            
        }

        $requestBody = $this->createMock(StreamInterface::class);
        $response->expects($this->once())->method('getBody')->willReturn($requestBody);
        $requestBody->expects($this->once())->method('write')->with($expectedJson);

        $view->output($response, $data);
    }

    /**
     * Test 'encode' method
     *
     * @dataProvider outputProvider
     */
    public function testEncode($data, $expectedSerialized, $expected)
    {
        $decorators = $this->getDecorators();

        $view = new JsonView($decorators);   
        $view = $view->withDecorator('foo')->withDecorator('zoo')->withDecorator('baz');     

        $result = $view->encode($data);

        $this->assertSame($expected, $result);
    }

    /**
     * Get data that needs to be processed
     *
     * @return array
     */
    protected function getData()
    {
        $iterator = new class() implements IteratorAggregate {
            public function getIterator()
            {
                foreach (['key1' => 'value1', 'key2' => 'value2'] as $key => $value) {
                    yield $key => $value;
                }
            }
        };

        $object = new class() {
            public $test = 'a';
            public $test2 = 'b';
            protected $test3 = 'c';
            private $test4 = 'd';
        };

        $serializable = new class($iterator, $object) implements JsonSerializable {
            public function __construct($iterator, $object) {
                $this->iterator = $iterator;
                $this->object = $object;
            }

            public function jsonSerialize()
            {
                return [
                    'zoo' => '&baz "bar"',
                    'iterator' => $this->iterator,
                    'nested' => (object)['object' => $this->object]
                ];
            }

            public function getLastModified()
            {
                return '12345';
            }
        };

        return [$iterator, $object, $serializable];
    }

    /**
     * Get test decorators
     *
     * @return array
     */
    protected function getDecorators()
    {
        $decorator1 = new class() {
            public function __invoke($subject, $data)
            {
                $data = (array)$data;
                $data['dec1'] = true;

                return $data;
            }

            public function getJsonOptions()
            {
                return JSON_HEX_AMP;
            }
        };

        $decorator2 = function($subject, $data) {
            $data = (array)$data;
            $data['dec2'] = true;

            return $data;
        };

        $decorator3 = function($subject, $data) {
            $data = (array)$data;
            $data['dec3'] = true;

            return $data;
        };

        $decorator4 = new class() {
            public function __invoke($subject, $data)
            {
                $data = (array)$data;
                $data['dec4'] = true;

                return $data;
            }

            public function getJsonOptions()
            {
                return JSON_HEX_QUOT;
            }
        };

        return [
            'foo' => $decorator1, 
            'bar' => $decorator2, 
            'zoo' => $decorator3, 
            'baz' => $decorator4
        ];
    }
}
