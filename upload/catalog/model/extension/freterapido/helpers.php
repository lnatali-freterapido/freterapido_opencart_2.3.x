<?php

if (!function_exists("fix_zip_code")) {
    function fix_zip_code($zip) {
        $fixed = preg_replace('([^0-9])', '', $zip);

        return $fixed;
    }
}
