<?php

namespace Tests\Feature;

use App\Payment\PatreonManager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PatreonTest extends TestCase
{
    use refreshDatabase;

    /**
     * Tests the basically functionality for patreon.
     *
     * @return void
     */
    public function testLoadingFromDatabaseWorks()
    {
        $this->seed();
        $patreonManager = resolve(PatreonManager::class);
        $patrons = $patreonManager->getPatrons();
        $this->assertNotEmpty($patrons);
    }
}
