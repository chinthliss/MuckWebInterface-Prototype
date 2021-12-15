<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MuckApiTest extends TestCase
{
    use RefreshDatabase;

    public function testUnsignedIncomingRequestIsRejected()
    {
        $response = $this->json('POST', route('muck.test'));
        $response->assertForbidden();
    }

    public function testIncorrectlySignedIncomingRequestIsRejected()
    {
        $response = $this->json('POST', route('muck.test'),['test' => 'test'], ['Signature' => 'fake']);
        $response->assertForbidden();
    }

    public function testCorrectSignatureIsAccepted()
    {
        $content = '{"test":"test"}';
        $signature = sha1($content . config('muck.salt'));
        $response = $this->json('POST', route('muck.test'),['test' => 'test'], ['Signature' => $signature]);
        $response->assertSuccessful();
    }
}
