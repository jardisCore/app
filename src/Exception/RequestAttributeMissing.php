<?php

declare(strict_types=1);

namespace JardisCore\App\Exception;

/**
 * Thrown when a route reads a request attribute that the session middleware
 * should have stored and it is absent or empty: a wiring fault of the app, not
 * a client error. It is an {@see AppException} (a `RuntimeException`), so the
 * error boundary ({@see \JardisCore\App\Handler\Error\HandleThrowable}) answers it as the
 * generic 500 and logs the cause - the request never runs under an empty scope.
 */
final class RequestAttributeMissing extends AppException
{
}
