CREATE OR REPLACE FUNCTION view_insert_update_delete() RETURNS trigger AS $view_insert_update_delete$
DECLARE
    insert_id bigint;
    original_id bigint;
    columns TEXT;
    values TEXT;
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
        
        SELECT get_columns_with_cutin(TG_TABLE_NAME, 'previous_id', ARRAY ['next_id', 'original_id']) INTO columns;
        SELECT get_columns_with_cutin(TG_TABLE_NAME, 'previous_id', ARRAY ['NULL', '$2'], '$1') INTO values;

        -- Insert it into the history table
        EXECUTE 'INSERT INTO ' || quote_ident(TG_TABLE_NAME || '_history') ||
        '(' ||
        columns ||
        ')' ||
        ' SELECT ' || values || ' RETURNING id'
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
$view_insert_update_delete$ LANGUAGE plpgsql;