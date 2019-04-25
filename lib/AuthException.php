<?php declare(strict_types=1);

/**
 * Authentication or authorization exception.
 *
 * @codeCoverageIgnore
 */
class AuthException extends RuntimeException
{
    /**
     * AuthException constructor.
     *
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 403, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
