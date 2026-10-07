<?php

namespace createch\PaycorpSampathVault\Contracts;

/**
 * Supplies the per-request msgId the gateway uses for idempotency and tracing.
 *
 * An injectable seam so tests can assert on an exact outbound request body.
 */
interface MessageIdGeneratorInterface
{
    /**
     * @return string A 36-character 8-4-4-4-12 upper-case hexadecimal identifier.
     */
    public function generate();
}
