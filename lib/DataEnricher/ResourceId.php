<?php
declare(strict_types=1);

namespace DataEnricher;

use LegalThings\DataEnricher\Node;
use LegalThings\DataEnricher\Processor;
use LegalThings\DataEnricher\Processor\Helper;
use LTO\EventChain;
use function LTO\encode;
use function LTO\sha256;
use function sodium_crypto_generichash as blake2b;

/**
 * Create a resource id for a chain
 */
class ResourceId implements Processor
{
    use Processor\Implementation,
        Helper\GetByReference
    {
        Helper\GetByReference::withSourceAndTarget insteadof Processor\Implementation;
    }

    protected function createResourceId($chainId, $nonceSeed)
    {
        $nsHashed = sha256(blake2b($chainId, '', 32));
        $nonce = sha256($nonceSeed);

        $packed = pack('Ca20a20', EventChain::RESOURCE_ID, $nonce, $nsHashed);
        $chksum = sha256(blake2b($packed));

        $idBinary = pack('Ca20a20a4', EventChain::RESOURCE_ID, $nonce, $nsHashed, $chksum);

        return encode($idBinary, 'base58');
    }

    /**
     * Apply processing to a single node
     * 
     * @param Node $node
     */
    public function applyToNode(Node $node)
    {
        $instruction = $node->getInstruction($this);

        if (is_string($instruction)) {
            $seed = $instruction;
            $chain = $this->source->chain ?? null;
        } elseif (is_object($instruction)) {
            $seed = $instruction->seed ?? null;
            $chain = $instruction->chain ?? $this->source->chain ?? null;
        }

        $id = ($seed !== null && $chain !== null)
            ? $this->createResourceId($chain, $seed)
            : null;

        $node->setResult($id);
    }
}
