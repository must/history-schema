<?php

namespace Visionerp\HistorySchema;

class HistorySchema
{
    /**
     * Create trigger function
     *
     * @return void
     * @author Mustapha Ben Chaaben
     **/
    public function createHistoryTriggerFunction()
    {
        // The procedure to execture on trigger
        \DB::unprepared("
            CREATE OR REPLACE FUNCTION view_insert_update_delete() RETURNS trigger AS \$view_insert_update_delete\$
            DECLARE
                insert_id bigint;
                original_id bigint;
            BEGIN
                --
                -- Insert, update or delete a record from the view by updating the history table
                -- make use of the special variable TG_OP to work out the operation.
                --
                IF (TG_OP = 'DELETE') THEN
                    EXECUTE 'INSERT INTO ' || quote_ident(TG_TABLE_NAME || '_trash')
                        || ' SELECT $1.* '
                    USING OLD;

                    return OLD;
                ELSIF (TG_OP = 'UPDATE') THEN
                    -- The id of the old record will be the next value on the history table sequence
                    OLD.id = NEXTVAL(quote_ident(TG_TABLE_NAME || '_history' || '_id_seq'));
                    
                    -- First time entring the history table? 
                    IF (OLD.previous_id IS NULL) THEN
                        -- Let's get the original_id the same as the new OLD ID (Go figure :d)
                        original_id := OLD.id;
                    ELSE
                        -- Let's figure out the original id from the previous relevant record on the history table
                        EXECUTE 'SELECT original_id ' ||
                        'FROM ' || quote_ident(TG_TABLE_NAME || '_history') || ' ' ||
                        'WHERE id = $1.previous_id'
                        INTO original_id
                        USING OLD;
                    END IF;
                    
                    -- Insert it into the history table
                    EXECUTE 'INSERT INTO ' || quote_ident(TG_TABLE_NAME || '_history')
                        || ' SELECT $1.*, NULL, $2 RETURNING id'
                    INTO insert_id
                    USING OLD, original_id;
                    
                    -- Update the next_id on the previous record
                    EXECUTE 'UPDATE ' || quote_ident(TG_TABLE_NAME || '_history')
                        || ' SET next_id = $1'
                        || ' WHERE id = $2'
                    USING insert_id, OLD.previous_id;
                    
                    -- Set the previous id to the new insert_id
                    NEW.previous_id := insert_id;

                    return NEW;
                ELSEIF (TG_OP = 'INSERT') THEN
                    return NEW;
                END IF;

                RETURN NULL; -- result is ignored since this is an AFTER trigger
            END;
            \$view_insert_update_delete\$ LANGUAGE plpgsql;
        ");
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
     * @return void
     * @author Mustapha Ben Chaaben
     **/
    public function createHistoryTable($view, callable $callable = NULL, $timestamped = true)
    {
        \DB::transaction(function () use ($view, $callable, $timestamped) {
            // First of all we create the history table
            // It will be named following the views name (viewName_history)
            \Schema::create($view . '_history', function (\Illuminate\Database\Schema\Blueprint $table)  use ($view, $callable, $timestamped) {
                $table->increments('id');

                if(! is_null($callable)) {
                    call_user_func($callable, $table);
                }
                
                if($timestamped) {
                    $table->timestamps();
                }

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
            });

            // Second of all we create the archived records table
            // It will be named following the views name (viewName_archived)
            \Schema::create($view . '_trash', function (\Illuminate\Database\Schema\Blueprint $table)  use ($view, $callable, $timestamped) {
                $table->increments('id');

                if(! is_null($callable)) {
                    call_user_func($callable, $table);
                }

                if($timestamped) {
                    $table->timestamps();
                }

                $this->createHistoryReference($table, $view);
            });

            // We create the actual view of data
            \Schema::create($view, function (\Illuminate\Database\Schema\Blueprint $table)  use ($view, $callable, $timestamped) {
                $table->increments('id');
                
                if(! is_null($callable)) {
                    call_user_func($callable, $table);
                }

                if($timestamped) {
                    $table->timestamps();
                }

                $this->createHistoryReference($table, $view);
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
}
