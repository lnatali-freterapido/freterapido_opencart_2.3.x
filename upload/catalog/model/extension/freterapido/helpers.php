<?php

if (!function_exists("fix_zip_code")) {
    function fix_zip_code($zip) {
        $fixed = preg_replace('([^0-9])', '', $zip);

        return $fixed;
    }
}

if (!function_exists('array_order_by')) {
    /**
     * example: array_order_by($data, 'column_name', SORT_DESC, 'other_column_name', SORT_ASC);
     * @return mixed
     */
    function array_order_by() {
        $args = func_get_args();
        $data = array_shift($args);

        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
            }
        }

        $args[] = &$data;

        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }
}
