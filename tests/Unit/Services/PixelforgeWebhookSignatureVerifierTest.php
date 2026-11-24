<?php

namespace Tests\Unit\Services;

use App\Services\Pixelforge\PixelforgeWebhookSignatureVerifier;
use Tests\TestCase;

class PixelforgeWebhookSignatureVerifierTest extends TestCase
{
    protected PixelforgeWebhookSignatureVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verifier = new PixelforgeWebhookSignatureVerifier;
    }

    public function test_valid_signature_within_tolerance_passes(): void
    {
        $secret = 'whsec_test_secret_123';
        $payload = '{"id":"evt_123","type":"ping"}';
        $now = 1795550500;
        $sig = hash_hmac('sha256', "{$now}.{$payload}", $secret);

        $this->assertTrue(
            $this->verifier->isValid($payload, "t={$now},v1={$sig}", $secret, $now + 10)
        );
    }

    public function test_tampered_payload_or_whitespace_fails_verification(): void
    {
        $secret = 'whsec_test_secret_123';
        $rawPayload = '{"id":"evt_123","type":"ping"}';
        $reformattedPayload = '{ "id": "evt_123", "type": "ping" }';
        $now = 1795550500;
        $sig = hash_hmac('sha256', "{$now}.{$rawPayload}", $secret);

        $this->assertFalse(
            $this->verifier->isValid($reformattedPayload, "t={$now},v1={$sig}", $secret, $now)
        );
    }

    public function test_expired_timestamp_outside_300s_window_is_rejected(): void
    {
        $secret = 'whsec_test_secret_123';
        $payload = '{"id":"evt_123","type":"ping"}';
        $timestamp = 1795550000;
        $sig = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        $this->assertFalse(
            $this->verifier->isValid($payload, "t={$timestamp},v1={$sig}", $secret, $timestamp + 301)
        );
    }

    public function test_missing_secret_or_malformed_header_is_rejected(): void
    {
        $payload = '{"id":"evt_123","type":"ping"}';

        $this->assertFalse($this->verifier->isValid($payload, null, 'whsec_test'));
        $this->assertFalse($this->verifier->isValid($payload, 'invalid-format', 'whsec_test'));
        $this->assertFalse($this->verifier->isValid($payload, 't=12345,v1=abc', ''));
    }
}
