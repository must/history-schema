<?php

namespace Visionerp\HistorySchema;

class HistorySchema
{
    /**
     * Call callback if not null
     * 
     * @param callable $callback The callback function
     * @param Object $table Laravel schema table object reference
     * 
     * @return void
     * @author Mustapha Ben Chaaben
     **/
    private function callIfNotNullWithTable(callable $callback, $table)
    {
        if(! is_null($callback)) {
            call_user_func($callback, $table);
        }
    }

    /**
     * Exec callback function and create timestamp
     *
     * @param Object $table Laravel schema table object reference
     * @param callable $callback The callback function
     * @param boolean $timestamped Wether or not to put timestamp fields (created_at and updated_at)
     * 
     * @return void
     * @author Mustapha Ben Chaaben
     **/
    private function execCallBackFunctionAndCreateTimestamp($table, callable $callback, $timestamped)
    {
        $this->callIfNotNullWithTable($callback, $table);

        if($timestamped) {
            $table->timestamps();
        }
    }
    /**
     * Create trigger function
     *
     * @return void
     * @author Mustapha Ben Chaaben
     **/
    public function createHistoryTriggerFunction($automaticallyCreateGetColumnsFunction = true)
    {
        if($automaticallyCreateGetColumnsFunction) {
            (new Util())->createGetAllColumnsWithCutinProcedure();
        } 

        // The procedure to execture on trigger
        \DB::unprepared(file_get_contents('SQL/view_insert_update_delete_trigger.sql'));
    }

    /**
     * Drop history trigger function
     *
     * @return void
     * @author Mustapha Ben Chaaben
     **/
    public function dropHistoryTriggerFunction()
    {
        \DB::unprepared("DROP FUNCTION IF EXISTS view_history_insert();");
    }

    /**
     * Create History reference
     * 
     * @param Object References table to modify
     * @param String view name
     * 
     * @return void
     * 
     * @author Mustapha Ben Chaaben
     */
    private function createHistoryReference($table, $view) {
        $table->integer('previous_id')->unsigned()->nullable();

        $table->foreign('previous_id')
            ->references('id')->on($view . '_history')
            ->onDelete('cascade')
            ->onUpdate('cascade');
    }

    /**
     * Create History table for table
     *
     * @param String $view The table name
     * @param callable $callback The callback function
     * @param boolean $timestamped Wether or not to put timestamp fields (created_at and updated_at)
     * 
     * @return void
     * @author Mustapha Ben Chaaben
     **/
    public function createHistoryTable($view, callable $callback = NULL, $timestamped = true)
    {
        \DB::transaction(function () use ($view, $callback, $timestamped) {
            // First of all we create the history table
            // It will be named following the views name (viewName_history)
            \Schema::create($view . '_history', function (\Illuminate\Database\Schema\Blueprint $table)  use ($view, $callback, $timestamped) {
                $table->increments('id');

                $this->createHistoryReference($table, $view);
                $table->integer('next_id')->unsigned()->nullable();
                $table->integer('original_id')->unsigned()->nullable();

                $table->foreign('next_id')
                    ->references('id')->on($view . '_history')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
                $table->foreign('original_id')
                    ->references('id')->on($view . '_history')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

                $table->index('next_id');
                $table->index('original_id');

                $this->execCallBackFunctionAndCreateTimestamp($table, $callback, $timestamped);
            });

            // Second of all we create the archived records table
            // It will be named following the views name (viewName_archived)
            \Schema::create($view . '_trash', function (\Illuminate\Database\Schema\Blueprint $table)  use ($view, $callback, $timestamped) {
                $table->increments('id');
                $this->createHistoryReference($table, $view);

                $this->execCallBackFunctionAndCreateTimestamp($table, $callback, $timestamped);

            });

            // We create the actual view of data
            \Schema::create($view, function (\Illuminate\Database\Schema\Blueprint $table)  use ($view, $callback, $timestamped) {
                $table->increments('id');
                
                $this->createHistoryReference($table, $view);

                $this->execCallBackFunctionAndCreateTimestamp($table, $callback, $timestamped);

            });

            // Create the trigger
            \DB::unprepared("
                CREATE TRIGGER ${view}_view_insert
                BEFORE INSERT OR UPDATE OR DELETE ON $view
                FOR EACH ROW
                EXECUTE PROCEDURE view_insert_update_delete();
            ");
        });
    }

    /**
     * Drop history table
     *
     * @return void
     * @author Mustapha Ben Chaaben
     **/
    public function dropHistoryTable($view)
    {
        \DB::transaction(function () use ($view) {
            \DB::unprepared("DROP TRIGGER IF EXISTS ${view}_view_insert on $view;");
            \Schema::drop("${view}");
            \Schema::drop("${view}_trash");
            \Schema::drop("${view}_history");
        });
    }

    /**
     * Alter history table
     *
     * @param String $view The table name
     * @param callable $callback The callback function
     * 
     * @return void
     * @author Mustapha Ben Chaaben
     **/
    public function table($view, callable $callback = NULL)
    {
        \DB::transaction(function () use ($view, $callback) {
            // First of all we create the history table
            // It will be named following the views name (viewName_history)
            \Schema::table($view . '_history', function (\Illuminate\Database\Schema\Blueprint $table)  use ($view, $callback) {
                $this->callIfNotNullWithTable($callback, $table);
            });

            // Second of all we create the archived records table
            // It will be named following the views name (viewName_archived)
            \Schema::table($view . '_trash', function (\Illuminate\Database\Schema\Blueprint $table)  use ($view, $callback) {
                $this->callIfNotNullWithTable($callback, $table);
            });

            // We create the actual view of data
            \Schema::table($view, function (\Illuminate\Database\Schema\Blueprint $table)  use ($view, $callback) {
                $this->callIfNotNullWithTable($callback, $table);
            });
        });
    }

}
