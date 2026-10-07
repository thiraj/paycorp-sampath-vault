<?php

namespace createch\PaycorpSampathVault\Test\Support;

use createch\PaycorpSampathVault\Contracts\MessageIdGeneratorInterface;

/**
 * Returns a predetermined msgId so request bodies are deterministic.
 */
final class FixedMessageIdGenerator implements MessageIdGeneratorInterface
{
    /** @var string */
    private $messageId;

    /**
     * @param  string  $messageId
     */
    public function __construct($messageId = 'AAAAAAAA-BBBB-4CCC-8DDD-EEEEEEEEEEEE')
    {
        $this->messageId = $messageId;
    }

    /**
     * @return string
     */
    public function generate()
    {
        return $this->messageId;
    }
}
