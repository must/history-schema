<?php

use Visionerp\HistorySchema\HistorySchema;



class HistorySchemaTest extends \Orchestra\Testbench\TestCase
{
    private $historySchema;
    private $data = [
        ['text1_field' => 'john1@example.com', 'text2_field' => 'test', 'integer_field' => 10],
        ['text1_field' => 'john2@example.com', 'text2_field' => 'test2', 'integer_field' => 20],
        ['text1_field' => 'john3@example.com', 'text2_field' => 'test3', 'integer_field' => 30]
    ];

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

    public function setUp()
    {
        parent::setUp();

        $this->historySchema = new HistorySchema();
        
        $this->historySchema->createHistoryTriggerFunction();

        $this->historySchema->createHistoryTable('test', function(\Illuminate\Database\Schema\Blueprint $table) {
            $table->string('text1_field');
            $table->string('text2_field');
            $table->integer('integer_field');
        });

        $this->insertThreeRecords('test');
    }

    public function tearDown()
    {
        $this->historySchema->dropHistoryTriggerFunction();
        
        $this->historySchema->dropHistoryTable('test');

        parent::tearDown();
    }

    /**
     * Insert three users into table
     *
     * @param String table
     * 
     * @return void
     * @author Mustapha Ben Chaaben
     **/
    private function insertThreeRecords()
    {
        DB::table('test')->insert($this->data);
    }

    /**
     * Update test records
     *
     * @param int number of times to update
     * 
     * @return void
     * @author Mustapha Ben Chaaben
     **/
    private function updateTestRecords($times = 1)
    {
        foreach (range(0, $times-1) as $i) {
            DB::table('test')
            ->update(['text2_field' => DB::raw('text2_field || \' updated\'')]);
        }
    }

    public function testRecordsCountOnInsert()
    {
        $count = DB::table('test')->count();

        $this->assertEquals(3, $count, 'User count doesn\'t match number of users created!');
    }

    public function TestLastRecordIdOnInsert()
    {
        $testRecords = DB::table('test')->get();

        $this->assertEquals(3, $testRecords[count($testRecords)-1]->id, 'User Id not right!');
    }

    public function testRecordsPrevIdIsNullOnInsert()
    {
        $testRecords = DB::table('test')->get();

        foreach($testRecords as $testRecord) {
            $this->assertNull($testRecord->prev_id, 'Prev_id is not null on record with Id: ' . $testRecord->id);
        }
    }

    public function testRecordsPrevIdIsNotNullOnUpdate()
    {
        DB::table('test')
            ->update(['text2_field' => DB::raw('text2_field || \' updated\'')]);

        $testRecords = DB::table('test')->get();

        foreach($testRecords as $testRecord) {
            $this->assertNotNull($testRecord->prev_id, 'Prev_id is null on record with Id: ' . $testRecord->id . ' text2_field: ' . $testRecord->text2_field);
        }
    }

    public function testRecordsPrevIdReferencesFirstOldOnUpdate()
    {
        $this->updateTestRecords();

        $testRecords = DB::table('test_history')->get();

        foreach($testRecords as $index => $testRecord) {
            $this->assertEquals($this->data[$index]['text2_field'], $testRecord->text2_field, 'History records don\'t match');
        }
    }

    public function testRecordsPrevIdReferencesFirstOldWithNullPrevIdAndNextIdOnUpdate()
    {
        $this->updateTestRecords();

        $testRecords = DB::table('test_history')->get();

        foreach($testRecords as $index => $testRecord) {
            $this->assertNull($testRecord->prev_id, 'Prev_id not null on first history item');
            $this->assertNull($testRecord->next_id, 'Next_id not null on first history item');
        }
    }

    public function testRecordsPrevIdReferencesFirstOldWithOriginalIdEqualsIdOnUpdate()
    {
        $this->updateTestRecords();

        $testRecords = DB::table('test_history')->get();

        foreach($testRecords as $index => $testRecord) {
            $this->assertEquals($testRecord->id, $testRecord->original_id, 'Original id of history record doesn\'t match the id');
        }
    }

    public function testRecordsPrevIdReferencesFirstOldWithNotNullPrevIdAndNextIdOnTwoUpdates()
    {
        $this->updateTestRecords(2);

        $testRecords = DB::table('test_history')
            ->where('id', '>=', 4)
            ->get();

        foreach($testRecords as $index => $testRecord) {
            $this->assertNotNull($testRecord->prev_id, 'Prev_id null on first history item');
            $this->assertNull($testRecord->next_id, 'Next_id not null on first history item');
        }
    }

    public function testRecordsOriginalIdReferencesFirstOldOnTwoUpdates()
    {
        $this->updateTestRecords(2);

        $testRecords = DB::table('test_history')
            ->where('id', '>=', 4)
            ->get();

        foreach($testRecords as $index => $testRecord) {
            $this->assertEquals($testRecord->id-3, $testRecord->original_id, 'Original id of history record doesn\'t match the id');
        }
    }

    public function testRecordsPrevIdReferencesFirstOldOnTwoUpdates()
    {
        $this->updateTestRecords(2);

        $testRecords = DB::table('test_history')
            ->where('id', '>=', 4)
            ->get();

        foreach($testRecords as $index => $testRecord) {
            $this->assertEquals($testRecord->id-3, $testRecord->prev_id, 'Prev id of history record doesn\'t match the id');
        }
    }

    public function testRecordsNextIdReferencesSecondOldOnTwoUpdates()
    {
        $this->updateTestRecords(2);

        $testRecords = DB::table('test_history')
            ->where('id', '<', 4)
            ->get();

        foreach($testRecords as $index => $testRecord) {
            $this->assertEquals($testRecord->id + 3, $testRecord->next_id, 'Next id of history record doesn\'t match the id of newer ones');
        }
    }

    public function testRecordsCountOnDelete()
    {
        DB::table('test')->delete();

        $count = DB::table('test')->count();

        $this->assertEquals(0, $count, 'Records count doesn\'t match number of users created!');
    }

    public function testTrashRecordsCountOnDelete()
    {
        DB::table('test')->delete();

        $count = DB::table('test_trash')->count();

        $this->assertEquals(3, $count, 'Trashed records count doesn\'t match number of users created!');
    }

}