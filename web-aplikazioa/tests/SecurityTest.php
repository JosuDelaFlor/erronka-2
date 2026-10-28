<?php

use PHPUnit\Framework\TestCase;

final class SecurityTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testHtmlOutputIsEscaped(): void
    {
        $this->assertSame(
            '&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;',
            e("<script>alert('xss')</script>")
        );
    }

    public function testCsrfTokenIsRandomAndStoredInSession(): void
    {
        $token = csrf_token();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertSame($token, $_SESSION['csrf_token']);
    }

    public function testExistingCsrfTokenIsReusedForTheSession(): void
    {
        $_SESSION['csrf_token'] = str_repeat('a', 64);

        $this->assertSame($_SESSION['csrf_token'], csrf_token());
    }
}
