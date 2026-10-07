<?php

namespace createch\PaycorpSampathVault\Exceptions;

use LogicException;

/**
 * Raised when a gateway operation exists on the client surface but has no
 * verified wire format in this package.
 *
 * Extends LogicException rather than PaycorpException: it reports a
 * programming mistake, not a runtime gateway condition, and must not be
 * swallowed by a generic gateway-error handler.
 */
class UnsupportedOperationException extends LogicException
{
    /**
     * @param  string  $operation
     * @return self
     */
    public static function notImplemented($operation)
    {
        return new self(
            'The "' . $operation . '" operation is not implemented by this package. '
            . 'It was previously exposed but could never succeed, so it now fails '
            . 'immediately instead of producing an undefined request. Open an issue '
            . 'with the Paycorp specification for this operation if you need it.'
        );
    }
}
