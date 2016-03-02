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