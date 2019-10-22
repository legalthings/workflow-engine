<?php

use Jasny\ValidationResult;
use Jasny\DB\EntitySet;

/**
 * @covers AvailableResponse
 */
class AvailableResponseTest extends \Codeception\Test\Unit
{
    /**
     * Provide data for testing 'cast' method for multiple records
     *
     * @return array
     */
    public function castMultiProvider()
    {
        $update = [
            ['select' => 'foo', 'patch' => false, 'data' => 'foo_data'],
            ['select' => 'bar', 'data' => 'bar_data'],
            'zoo'
        ];

        $updateObj = $update;
        $updateObj[0] = (object)$updateObj[0];
        $updateObj[1] = (object)$updateObj[1];

        return [
            [$update],
            [$updateObj],
        ];
    }

    /**
     * Test 'cast' method for multiple records
     *
     * @dataProvider castMultiProvider
     */
    public function testCastMulti($update)
    {
        $response = new AvailableResponse();

        $response->update = $update;
        $response->cast();

        $update = $response->update;

        $this->assertInstanceOf(EntitySet::class, $update);
        $this->assertEquals(UpdateInstruction::class, $update->getEntityClass());

        $this->assertCount(3, $update);
        
        $this->assertSame('foo', $update[0]->select);
        $this->assertSame(false, $update[0]->patch);
        $this->assertSame('foo_data', $update[0]->data);

        $this->assertSame('bar', $update[1]->select);
        $this->assertSame(true, $update[1]->patch);
        $this->assertSame('bar_data', $update[1]->data);

        $this->assertSame('zoo', $update[2]->select);
        $this->assertSame(true, $update[2]->patch);
        $this->assertSame(null, $update[2]->data);
    }

    /**
     * Provide data for testing 'cast' method for single record
     *
     * @return array
     */
    public function castSingleProvider()
    {
        $update = ['select' => 'foo', 'patch' => true, 'data' => 'foo_data'];

        return [
            [$update],
            [(object)$update],
        ];
    }

    /**
     * Test 'cast' method in case of single record
     *
     * @dataProvider castSingleProvider
     */
    public function testCastSingle($update)
    {
        $response = new AvailableResponse();

        $response->update = $update;
        $response->cast();

        $update = $response->update;

        $this->assertInstanceOf(EntitySet::class, $update);
        $this->assertEquals(UpdateInstruction::class, $update->getEntityClass());

        $this->assertCount(1, $update);
        
        $this->assertSame('foo', $update[0]->select);
        $this->assertSame(true, $update[0]->patch);
        $this->assertSame('foo_data', $update[0]->data);
    }

    /**
     * Test 'cast' method in case of multi pretty input
     */
    public function testCastMultiPretty()
    {
        $response = new AvailableResponse();

        $response->update = ['foo', 'bar'];
        $response->cast();

        $update = $response->update;

        $this->assertInstanceOf(EntitySet::class, $update);
        $this->assertEquals(UpdateInstruction::class, $update->getEntityClass());

        $this->assertCount(2, $update);
        
        $this->assertSame('foo', $update[0]->select);
        $this->assertSame(true, $update[0]->patch);
        $this->assertSame(null, $update[0]->data);
        $this->assertSame(null, $update[0]->projection);

        $this->assertSame('bar', $update[1]->select);
        $this->assertSame(true, $update[1]->patch);
        $this->assertSame(null, $update[1]->data);
        $this->assertSame(null, $update[1]->projection);
    }

    /**
     * Test 'cast' method in case of single pretty input
     */
    public function testCastSinglePretty()
    {
        $response = new AvailableResponse();

        $response->update = 'foo';
        $response->cast();

        $update = $response->update;

        $this->assertInstanceOf(EntitySet::class, $update);
        $this->assertEquals(UpdateInstruction::class, $update->getEntityClass());

        $this->assertCount(1, $update);
        
        $this->assertSame('foo', $update[0]->select);
        $this->assertSame(true, $update[0]->patch);
        $this->assertSame(null, $update[0]->data);
        $this->assertSame(null, $update[0]->projection);
    }

    /**
     * Test 'validate' method
     */
    public function testValidate()
    {
        $update = [
            $this->createMock(UpdateInstruction::class),
            $this->createMock(UpdateInstruction::class)
        ];

        $validation1 = $this->createMock(ValidationResult::class);
        $validation2 = $this->createMock(ValidationResult::class);

        $update[0]->expects($this->once())->method('validate')->willReturn($validation1);
        $update[1]->expects($this->once())->method('validate')->willReturn($validation2);
        $validation1->expects($this->once())->method('getErrors')->willReturn(['error1', 'error2']);
        $validation2->expects($this->once())->method('getErrors')->willReturn(['error3']);

        $response = new AvailableResponse();
        $response->update = new EntitySet($update, null, EntitySet::ALLOW_DUPLICATES);

        $result = $response->validate();

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertEquals(['update error1', 'update error2', 'update error3'], $result->getErrors());
    }

    /**
     * Test 'jsonSerialize' method
     */
    public function testJsonSerialize()
    {
        $response = new AvailableResponse();

        $response->key = 'foo';
        $response->title = 'Foo title';
        $response->display = 'Foo display';
        $response->update = ['bar'];

        $result = $response->jsonSerialize();

        $expected = (object)[
            'title' => 'Foo title',
            'display' => 'Foo display',
            'update' => ['bar']
        ];

        $this->assertEquals($expected, $result);
    }
}
