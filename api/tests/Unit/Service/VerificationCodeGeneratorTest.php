<?php

namespace App\Tests\Unit\Service;

use App\Service\VerificationCodeGenerator;
use PHPUnit\Framework\TestCase;

class VerificationCodeGeneratorTest extends TestCase
{
    private VerificationCodeGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new VerificationCodeGenerator();
    }

    public function testGenerateReturns6DigitCode(): void
    {
        $code = $this->generator->generate();

        $this->assertIsString($code);
        $this->assertEquals(6, strlen($code));
    }

    public function testGenerateReturnsNumericCode(): void
    {
        $code = $this->generator->generate();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function testGenerateReturnsPaddedCode(): void
    {
        // Generate multiple codes to increase probability of getting a small number
        $codes = [];
        for ($i = 0; $i < 100; $i++) {
            $codes[] = $this->generator->generate();
        }

        foreach ($codes as $code) {
            $this->assertEquals(6, strlen($code), "Code '{$code}' should be 6 digits");
        }
    }

    public function testGenerateProducesDifferentCodes(): void
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = $this->generator->generate();
        }

        // With random generation, it's extremely unlikely to get all the same codes
        $uniqueCodes = array_unique($codes);
        $this->assertGreaterThan(1, count($uniqueCodes), 'Should generate different codes');
    }

    public function testGetExpirationTimeReturns15MinutesInFuture(): void
    {
        $now = new \DateTimeImmutable();
        $expirationTime = $this->generator->getExpirationTime();

        $this->assertInstanceOf(\DateTimeImmutable::class, $expirationTime);

        // Check that expiration is between 14 and 16 minutes from now (with some tolerance)
        $diff = $expirationTime->getTimestamp() - $now->getTimestamp();
        $this->assertGreaterThanOrEqual(14 * 60, $diff);
        $this->assertLessThanOrEqual(16 * 60, $diff);
    }

    public function testGetExpirationMinutesReturns15(): void
    {
        $this->assertEquals(15, $this->generator->getExpirationMinutes());
    }

    public function testExpirationTimeIsConsistentWithMinutes(): void
    {
        $expectedMinutes = $this->generator->getExpirationMinutes();
        $expirationTime = $this->generator->getExpirationTime();
        $now = new \DateTimeImmutable();

        $diffInMinutes = ($expirationTime->getTimestamp() - $now->getTimestamp()) / 60;
        $this->assertEqualsWithDelta($expectedMinutes, $diffInMinutes, 1);
    }
}
