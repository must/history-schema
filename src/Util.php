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
            CREATE OR REPLACE FUNCTION get_columns_except(input_table_name TEXT, except_columns TEXT[] = ARRAY []::TEXT[], prefix TEXT = '') RETURNS TEXT AS $$
            BEGIN
                RETURN array_to_string(ARRAY(SELECT prefix || CASE WHEN prefix = '' THEN '' ELSE '.' END || c.column_name
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

    /**
     * Create the get all columns except procedure
     *
     * @return void
     * @author Mustapha Ben Chaaben
     **/
    public function createGetAllColumnsWithCutinProcedure()
    {
        $this->createGetAllColumnsExceptProcedure();

        \DB::unprepared("
            CREATE OR REPLACE FUNCTION get_columns_with_cutin(input_table_name TEXT, cutoff_column TEXT = '', cutin_columns TEXT[] = ARRAY []::TEXT[], prefix TEXT = '') RETURNS TEXT AS $$
            DECLARE
                columns TEXT[];
                column_item TEXT;
                cutin_column TEXT;
                result_array TEXT[] := ARRAY []::TEXT[];
            BEGIN
                columns := string_to_array(get_columns_except(input_table_name, ARRAY []::TEXT[], prefix), ',');

                FOREACH column_item IN ARRAY columns
                LOOP
                    result_array := array_append(result_array, column_item);

                    IF(column_item = prefix || CASE WHEN prefix = '' THEN '' ELSE '.' END || cutoff_column) THEN
                        FOREACH cutin_column IN ARRAY cutin_columns
                        LOOP
                            result_array := array_append(result_array, cutin_column);
                        END LOOP;
                   END IF;
                END LOOP;

                RETURN array_to_string(result_array, ',');
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
    public function dropGetAllColumnsWithCutinProcedure()
    {
        \DB::unprepared("DROP FUNCTION IF EXISTS get_columns_with_cutin(TEXT, TEXT, TEXT[], TEXT);");
    }
}
