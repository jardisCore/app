<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\System;

use JardisCore\App\Handler\Session\AuthenticateToken;
use JardisCore\App\Handler\System\ReadAccessToken;
use JardisCore\App\Tests\Support\InMemoryTokenStore;
use JardisSupport\Auth\Data\Credential;
use JardisSupport\Auth\Data\Subject;
use JardisSupport\Auth\SessionManager;
use JardisSupport\Auth\SignedTokenAuthenticator;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * ReadAccessToken against the real SignedTokenAuthenticator: `data.access_token` (RFC 6749 section 5.1) or null.
 */
final class ReadAccessTokenTest extends TestCase
{
    private Psr17Factory $psr17;
    private SessionManager $manager;
    private ReadAccessToken $read;

    protected function setUp(): void
    {
        $this->psr17 = new Psr17Factory();
        $key = random_bytes(32);
        $this->manager = new SessionManager(new InMemoryTokenStore(), $key);
        $this->read = new ReadAccessToken(
            (new AuthenticateToken(new SignedTokenAuthenticator($key), Credential::token(...)))->__invoke(...),
        );
    }

    public function testReadsTheAcceptedAccessToken(): void
    {
        $session = $this->manager->create(Subject::from('sys'));

        $token = ($this->read)($this->body(json_encode([
            'status' => 200,
            'data' => [
                'access_token' => $session->accessToken,
                'token_type' => 'Bearer',
                'expires_in' => 900,
                'refresh_token' => $session->refreshToken,
            ],
        ], JSON_THROW_ON_ERROR)));

        $this->assertSame($session->accessToken, $token);
    }

    public function testAcceptedTokenOutsideTheFieldIsNotRead(): void
    {
        $session = $this->manager->create(Subject::from('sys'));

        $this->assertNull(($this->read)($this->body(json_encode([
            'data' => ['accessToken' => $session->accessToken],
        ], JSON_THROW_ON_ERROR))));
    }

    public function testAnswerThatIsNoJsonObjectGivesNull(): void
    {
        $this->assertNull(($this->read)($this->body('not json')));
        $this->assertNull(($this->read)($this->body('"text"')));
        $this->assertNull(($this->read)($this->body('')));
    }

    public function testAnswerWithoutObjectDataGivesNull(): void
    {
        $this->assertNull(($this->read)($this->body('{"status":200}')));
        $this->assertNull(($this->read)($this->body('{"data":"access_token"}')));
        $this->assertNull(($this->read)($this->body('{"data":null}')));
    }

    private function body(string $json): ResponseInterface
    {
        return $this->psr17->createResponse(200)->withBody($this->psr17->createStream($json));
    }
}
