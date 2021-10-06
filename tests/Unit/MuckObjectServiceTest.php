<?php

namespace Tests\Unit;

use App\Muck\MuckConnection;
use App\Muck\MuckDbref;
use App\Muck\MuckObjectService;
use MuckObjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MuckObjectServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testGetByDbref()
    {
        $muckObjects = $this->app->make(MuckObjectService::class);

        //Test with valid object
        $object = $muckObjects->getByDbref(1234);
        $this->assertEquals('TestCharacter', $object->name(), "Name wasn't correct when retrieving the test character.");

        //Test with invalid object
        $object = $muckObjects->getByDbref(88234);
        $this->assertNull($object, "An object was returned when testing with invalid details");

    }

    public function testGetByName()
    {
        $muckObjects = $this->app->make(MuckObjectService::class);

        //Test with valid name
        $object = $muckObjects->getByPlayerName('TestCharacter');
        $this->assertEquals(1234, $object->dbref(), "Dbref wasn't correct when retrieving the test character.");

        //Test with invalid name
        $object = $muckObjects->getByPlayerName('NoSuchCharacterExists');
        $this->assertNull($object, "An object was returned when testing with invalid details");

    }

    public function testGetByMuckObjectId()
    {
        $this->seed(MuckObjectSeeder::class);
        $muckObjects = $this->app->make(MuckObjectService::class);
        $object = $muckObjects->getByMuckObjectId(1);
        $this->assertNotNull($object, "GetByMuckObjectId failed.");
    }

    public function testGetMuckObjectIdFor_OnExistingObject()
    {
        $muckObjects = $this->app->make(MuckObjectService::class);
        $muckConnection = $this->app->make(MuckConnection::class);

        // Using MuckConnection to skip caching
        $dbref = $muckConnection->getByDbref(1234);
        $id = $muckObjects->getMuckObjectIdFor($dbref);
        $this->assertEquals(1, $id, "Getting an existing object returned an unexpected Id.");
    }

    public function testGetMuckObjectIdFor_OnNewObject()
    {
        $muckObjects = $this->app->make(MuckObjectService::class);

        // Test with a new object
        $object = new MuckDbref(7655, 'New', 't', Carbon::now());
        $id = $muckObjects->getMuckObjectIdFor($object);
        $this->assertNotNull($id, "An ID wasn't returned for the new object");

        //Test repeating with the new object gets the same result
        $idSecond = $muckObjects->getMuckObjectIdFor($object);
        $this->assertEquals($id, $idSecond, "Second request didn't give same ID.");
    }

}
