<?php

/**
 * @file Common functions.
 */

namespace Minisite;

/**
 * Project version.
 * @return string
 */
function version()
{
    return '1.0';
}

/**
 * @param array $array
 * @param array $parents
 * @param $value
 * @param bool $force
 */
function minisite_array_set_nested_value(array &$array, array $parents, $value, $force = false)
{
    $ref = &$array;
    foreach ($parents as $parent) {
        // PHP auto-creates container arrays and NULL entries without error if $ref
        // is NULL, but throws an error if $ref is set, but not an array.
        if ($force && isset($ref) && !is_array($ref)) {
            $ref = array();
        }
        $ref = &$ref[$parent];
    }
    $ref = $value;
}
