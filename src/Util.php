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
        \DB::unprepared("
            CREATE OR REPLACE FUNCTION get_columns_except(input_table_name TEXT, except_columns TEXT[] = ARRAY []::TEXT[], prefix TEXT = 't') RETURNS TEXT AS $$
            BEGIN
                RETURN array_to_string(ARRAY(SELECT prefix || '.' || c.column_name
                    FROM information_schema.columns As c
                        WHERE table_name = input_table_name 
                        AND c.table_schema = current_schema()
                        AND  c.column_name != all(except_columns)
                ), ',');
            END
            $$ LANGUAGE plpgsql;
        ");
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
}
