<?php

use Jasny\ValidationResult;

/**
 * @covers Identity
 */
class IdentityTest extends \Codeception\Test\Unit
{
    /**
     * Test 'getIdProperty' method
     */
    public function testGetIdProperty()
    {
        $result = Identity::getIdProperty();

        $this->assertSame('id', $result);
    }
}
