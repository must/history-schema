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