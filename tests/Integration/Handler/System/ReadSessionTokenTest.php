<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\System;

use JardisCore\App\Handler\Session\AuthenticateToken;
use JardisCore\App\Handler\System\ReadSessionToken;
use JardisCore\App\Tests\Support\InMemoryTokenStore;
use JardisSupport\Auth\Data\Credential;
use JardisSupport\Auth\Data\Subject;
use JardisSupport\Auth\SessionManager;
use JardisSupport\Auth\SignedTokenAuthenticator;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * The deprecated probe (since 3.3.0 replaced by ReadAccessToken) keeps its behaviour until 4.0: the ONE distinct
 * string of `data` the real authenticator accepts, fail-closed otherwise. These are the probe cases that
 * SignedInRunTest carried before SignedInRun switched to `data.access_token`.
 */
final class ReadSessionTokenTest extends TestCase
{
    private Psr17Factory $psr17;
    private SessionManager $manager;
    private ReadSessionToken $probe;

    protected function setUp(): void
    {
        $this->psr17 = new Psr17Factory();
        $key = random_bytes(32);
        $this->manager = new SessionManager(new InMemoryTokenStore(), $key);
        $this->probe = new ReadSessionToken(
            (new AuthenticateToken(new SignedTokenAuthenticator($key), Credential::token(...)))->__invoke(...),
        );
    }

    public function testFindsTheOneAcceptedTokenAmongOtherStrings(): void
    {
        $session = $this->manager->create(Subject::from('sys'));

        $token = ($this->probe)($this->answer([
            'accessToken' => $session->accessToken,
            'refreshToken' => $session->refreshToken,
        ]));

        $this->assertSame($session->accessToken, $token);
    }

    public function testAnswerWithoutAnyAcceptedTokenGivesNull(): void
    {
        $session = $this->manager->create(Subject::from('sys'));

        $this->assertNull(($this->probe)($this->answer(['refreshToken' => $session->refreshToken, 'note' => 'ok'])));
    }

    public function testAnswerWithTwoDifferentAcceptedTokensGivesNull(): void
    {
        $session = $this->manager->create(Subject::from('sys'));
        $other = $this->manager->create(Subject::from('other'));

        $this->assertNull(($this->probe)($this->answer([
            'accessToken' => $session->accessToken,
            'again' => $other->accessToken,
        ])));
    }

    public function testTheSameTokenFoundTwiceCountsOnce(): void
    {
        $session = $this->manager->create(Subject::from('sys'));

        $token = ($this->probe)($this->answer([
            'accessToken' => $session->accessToken,
            'copy' => $session->accessToken,
        ]));

        $this->assertSame($session->accessToken, $token);
    }

    public function testTokenIsFoundInANestedDataValue(): void
    {
        $session = $this->manager->create(Subject::from('sys'));

        $token = ($this->probe)($this->answer([
            'session' => ['tokens' => ['access' => $session->accessToken]],
            'note' => 'ok',
        ]));

        $this->assertSame($session->accessToken, $token);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function answer(array $data): ResponseInterface
    {
        return $this->psr17->createResponse(200)->withBody($this->psr17->createStream(
            json_encode(['status' => 200, 'data' => $data, 'errors' => (object) [], 'meta' => (object) []], JSON_THROW_ON_ERROR),
        ));
    }
}
