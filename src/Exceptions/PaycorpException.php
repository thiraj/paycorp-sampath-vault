<?php

namespace createch\PaycorpSampathVault\Exceptions;

use RuntimeException;

/**
 * Base type for every exception raised by this package.
 *
 * Catching this single type is enough to isolate gateway problems from the
 * rest of an application. Messages produced by subclasses are always passed
 * through SensitiveDataRedactor first, so they are safe to log.
 */
class PaycorpException extends RuntimeException
{
}
