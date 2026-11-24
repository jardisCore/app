<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Repeat;

use Closure;
use JardisCore\App\Exception\RepeatAborted;
use JardisSupport\Contract\Kernel\DomainResponseInterface;

/**
 * Repeats one call as long as the integer field of its answer says "open items left" (greater than zero).
 *
 * Per round: status 400 or above -> that answer is returned, no further call; below 400 the field must be an `int`
 * ({@see RepeatAborted} otherwise); a value of zero or less -> that answer is returned; a value not smaller than the
 * one of the previous round -> {@see RepeatAborted} (no progress); otherwise the next call. No maximum number of
 * rounds: the strictly falling whole number ends the loop on its own.
 *
 * The one public entry point is `__invoke()`. The answer of the last call is returned unchanged.
 */
final class RepeatWhileOpen
{
    /**
     * @param Closure(): DomainResponseInterface $call the call to repeat, without argument
     * @param string $field name of the integer field in `getData()` of the answer
     */
    public function __invoke(Closure $call, string $field): DomainResponseInterface
    {
        $previous = null;
        while (true) {
            $response = $call();
            if ($response->getStatus() >= 400) {
                return $response;
            }

            $open = $response->getData()[$field] ?? null;
            if (!is_int($open)) {
                throw new RepeatAborted(
                    'the answer carries no integer in the field "' . $field . '"; '
                    . 'remove the cause in the call and start again',
                );
            }
            if ($open <= 0) {
                return $response;
            }
            if ($previous !== null && $open >= $previous) {
                throw new RepeatAborted(
                    'the number of open items did not fall; remove the cause in the call and start again',
                    $open,
                );
            }
            $previous = $open;
        }
    }
}
