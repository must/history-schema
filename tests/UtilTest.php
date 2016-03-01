<?php

use Visionerp\HistorySchema\HistorySchema;
use Visionerp\HistorySchema\Util;



class UtilTest extends \Orchestra\Testbench\TestCase
{
    private $historySchema;
    private $util;

    private $fields = ['id', 'text1_field', 'text2_field', 'integer_field', 'created_at', 'updated_at', 'previous_id'];

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

        $this->util = new Util();

        $this->util->createGetAllColumnsExceptProcedure();
    }

    public function tearDown()
    {
        $this->historySchema->dropHistoryTriggerFunction();
        
        $this->historySchema->dropHistoryTable('test');

        $this->util->dropGetAllColumnsExceptProcedure();

        parent::tearDown();
    }

    /**
     * Prefix array with prefix
     *
     * @return Array prefixed array
     * @author Mustapha Ben Chaaben
     **/
    private function prefixArray($array, $prefix)
    {
        foreach ($array as &$value)
            $value = $prefix.$value;

        return $array;
    }

    /**
     * Prepare Array for matching by converting into a string
     *
     * @return String
     * @author Mustapha Ben Chaaben
     **/
    private function prepArrayForMatching($array, $prefix = '')
    {
        return implode(', ', $this->prefixArray($array, $prefix));
    }

    public function testColumnsMatchInitialTableStructure()
    {
        $columns = DB::select('select get_columns_except(\'test\', ARRAY []::TEXT[], \'t\') as s;');
        $columns = explode(',', $columns[0]->s);

        $this->assertEquals(
            $this->prepArrayForMatching($this->fields, 't.'),
            $this->prepArrayForMatching($columns),
            'Columns expected and delivered don\'t match'
        );
    }

    public function testColumnsMatchInitialTableStructureExceptId()
    {
        $columns = DB::select('select get_columns_except(\'test\', ARRAY [\'id\'], \'t\') as s;');
        $columns = explode(',', $columns[0]->s);

        $this->assertEquals(
            $this->prepArrayForMatching(array_slice($this->fields, 1), 't.'),
            $this->prepArrayForMatching($columns),
            'Columns selected with exception expected and delivered don\'t match'
        );
    }

    public function testColumnsMatchInitialTableStructureExceptTwoIds()
    {
        $columns = DB::select('select get_columns_except(\'test\', ARRAY [\'id\', \'text1_field\'], \'t\') as s;');
        $columns = explode(',', $columns[0]->s);

        $this->assertEquals(
            $this->prepArrayForMatching(array_slice($this->fields, 2), 't.'),
            $this->prepArrayForMatching($columns),
            'Columns selected with exception expected and delivered don\'t match'
        );
    }

    public function testColumnsMatchInitialTableStructureWithoutPrefix()
    {
        $columns = DB::select('select get_columns_except(\'test\', ARRAY []::TEXT[], \'\') as s;');
        $columns = explode(',', $columns[0]->s);
        
        $this->assertEquals(
            $this->prepArrayForMatching($this->fields, ''),
            $this->prepArrayForMatching($columns),
            'Columns selected with exception expected and delivered don\'t match. This is with no prefix!'
        );
    }

    public function testColumnsMatchInitialTableStructureWithCutin()
    {
        $columns = DB::select('select get_columns_with_cutin(\'test\', \'integer_field\', ARRAY [\'test1\', \'test2\'], \'\') as s;');
        $columns = explode(',', $columns[0]->s);
        $fields = $this->fields;
        array_splice($fields, 4, 0, [ 'test1', 'test2' ]);
        
        $this->assertEquals(
            $this->prepArrayForMatching($fields),
            $this->prepArrayForMatching($columns),
            'Columns selected with exception expected and delivered don\'t match. This is with no prefix!'
        );
    }

    public function testColumnsMatchInitialTableStructureWithCutin2()
    {
        $columns = DB::select('select get_columns_with_cutin(\'test\', \'integer_field\', ARRAY [\'test1\', \'test2\', \'test3\'], \'\') as s;');
        $columns = explode(',', $columns[0]->s);
        $fields = $this->fields;
        array_splice($fields, 4, 0, [ 'test1', 'test2', 'test3' ]);
        
        $this->assertEquals(
            $this->prepArrayForMatching($fields),
            $this->prepArrayForMatching($columns),
            'Columns selected with exception expected and delivered don\'t match. This is with no prefix!'
        );
    }

    public function testColumnsMatchInitialTableStructureWithCutinWithPrefix()
    {
        $columns = DB::select('select get_columns_with_cutin(\'test\', \'integer_field\', ARRAY [\'pref.test1\', \'pref.test2\', \'pref.test3\'], \'pref\') as s;');
        $columns = explode(',', $columns[0]->s);
        $fields = $this->fields;
        array_splice($fields, 4, 0, [ 'test1', 'test2', 'test3' ]);
        
        $this->assertEquals(
            $this->prepArrayForMatching($fields, 'pref.'),
            $this->prepArrayForMatching($columns),
            'Columns selected with exception expected and delivered don\'t match. This is with no prefix!'
        );
    }

}