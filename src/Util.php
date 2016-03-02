<?php

namespace Visionerp\HistorySchema;

class Util
{
    /**
     * Create the get all columns except procedure
     *
     * @return void
     * @author Mustapha Ben Chaaben
     **/
    public function createGetAllColumnsExceptProcedure()
    {
        \DB::unprepared(file_get_contents(realpath(dirname ( __FILE__ ) . '/SQL/get_columns_except_function.sql')));
    }

    /**
     * Drop get all columns except procedure
     *
     * @return void
     * @author Mustapha Ben Chaaben
     **/
    public function dropGetAllColumnsExceptProcedure()
    {
        \DB::unprepared("DROP FUNCTION IF EXISTS get_columns_except(TEXT, TEXT[], TEXT);");
    }

    /**
     * Create the get all columns except procedure
     *
     * @return void
     * @author Mustapha Ben Chaaben
     **/
    public function createGetAllColumnsWithCutinProcedure()
    {
        $this->createGetAllColumnsExceptProcedure();

        \DB::unprepared(file_get_contents(realpath(dirname ( __FILE__ ) . '/SQL/get_columns_with_cutin_function.sql')));
    }

    /**
     * Drop get all columns except procedure
     *
     * @return void
     * @author Mustapha Ben Chaaben
     **/
    public function dropGetAllColumnsWithCutinProcedure()
    {
        \DB::unprepared("DROP FUNCTION IF EXISTS get_columns_with_cutin(TEXT, TEXT, TEXT[], TEXT);");
    }
}
