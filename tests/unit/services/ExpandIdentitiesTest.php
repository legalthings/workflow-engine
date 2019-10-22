<?php

use Improved as i;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * @covers ExpandIdentities
 */
class ExpandIdentitiesTest extends \Codeception\Test\Unit
{
    /**
     * Test '__construct' method
     */
    public function testConstruct()
    {
        $identityGateway = $this->createMock(IdentityGateway::class);
        $service = new ExpandIdentities($identityGateway);

        $this->assertEquals($identityGateway, $service->getGateway());
    }

    /**
     * Test '__invoke' method
     */
    public function testInvoke()
    {
        $process = $this->createMock(Process::class);

        $identities = $this->getIdentities();
        $process->actors = $this->getActors($identities);

        $identityById = $this->createMock(Identity::class);

        $gateway = $this->createMock(IdentityGateway::class);
        $gateway->expects($this->exactly(2))->method('fetch')
            ->withConsecutive(['a'], ['b'])
            ->willReturnOnConsecutiveCalls($process->actors[3]->identity, $identityById);

        $service = new ExpandIdentities($gateway);
        $service($process);

        $this->assertEquals(null, $process->actors[0]->identity);        
        $this->assertEquals(null, $process->actors[1]->identity);        
        $this->assertEquals($identities[0], $process->actors[2]->identity);        
        $this->assertEquals($identities[1], $process->actors[3]->identity);        
        $this->assertEquals($identityById, $process->actors[4]->identity);        
    }

    /**
     * Provide data for testing 'invoke' method, if identity is not found
     *
     * @return array
     */
    public function invokeNotFoundProvider()
    {
        $identity = $this->createMock(Identity::class);
        $identity->expects($this->once())->method('isGhost')->willReturn(true);
        $identity->expects($this->once())->method('getId')->willReturn('a');

        $lazy = Identity::lazyload(['id' => 'a']);

        return [
            [$identity, $identity],
            ['a', $lazy],
        ];
    }

    /**
     * Test '__invoke' method, if identity is not found
     *
     * @dataProvider invokeNotFoundProvider
     */
    public function testInvokeNotFound($identity, $expectedIdentity)
    {
        $process = $this->createMock(Process::class);
        $process->actors = [
            $this->createMock(Actor::class)
        ];

        $process->actors[0]->identity = $identity;

        $gateway = $this->createMock(IdentityGateway::class);
        $gateway->expects($this->once())->method('fetch')->with('a')->will($this->returnCallback(function() {
            throw new EntityNotFoundException('Foo entity not found');
        }));

        $service = new ExpandIdentities($gateway);
        $service($process);

        $this->assertEquals($expectedIdentity, $process->actors[0]->identity);        
    }

    /**
     * Mock identities
     *
     * @return array
     */
    protected function getIdentities()
    {
        $identity1 = $this->createMock(Identity::class);
        $identity1->expects($this->once())->method('isGhost')->willReturn(false);

        $identity2 = $this->createMock(Identity::class);
        $identity2->expects($this->once())->method('isGhost')->willReturn(true);
        $identity2->expects($this->once())->method('getId')->willReturn('a');        

        return [$identity1, $identity2];
    }

    /**
     * Mock actors set
     *
     * @return array
     */
    protected function getActors(array $identities)
    {
        $actors = [
            $this->createMock(Actor::class),
            $this->createMock(Actor::class),
            $this->createMock(Actor::class),
            $this->createMock(Actor::class),
            $this->createMock(Actor::class)
        ];

        $actors[1]->identity = null;
        $actors[2]->identity = clone $identities[0];
        $actors[3]->identity = clone $identities[1];
        $actors[4]->identity = 'b';

        return $actors;
    }
}
