<?php

use Visionerp\HistorySchema\HistorySchema;



class HistorySchemaTest extends \Orchestra\Testbench\TestCase

{

    protected function getEnvironmentSetUp($app)

    {

        $app['config']->set('database.default', 'testpackage');

        $app['config']->set('database.connections.testpackage', [

            'driver'   => 'pgsql',

            'host'     => env('TEST_DB_HOST', 'localhost'),
            
            'database' => env('TEST_DB_DATABASE', 'test'),
            'username' => env('TEST_DB_USERNAME', 'homestead'),
            'password' => env('TEST_DB_PASSWORD', 'secret'),
            
            'charset'  => 'utf8',
            'prefix'   => '',
            'schema'   => 'public',

        ]);

    }



    public function testCreate()

    {



    }

}