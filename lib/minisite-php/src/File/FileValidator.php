<?php

/**
 * @file FileValidator.php
 */

namespace Minisite\File;

/**
 * Class FileValidator
 * @package Minisite\File
 */
class FileValidator
{
    /**
     * Checks that the filename ends with an allowed extension.
     * @param array $files
     * @param array $extensions
     * @return array
     */
    public static function checkInvalidExtension($files = [], $extensions = [])
    {
        $invalid_files = [];

        foreach ($files as $file_name) {
            if (substr($file_name, -1) == '/') {
                continue;
            }
            $regex = '/\.('.preg_replace('/ +/', '|', preg_quote($extensions)).')$/i';
            if (!preg_match($regex, $file_name)) {
                $invalid_files[] = $file_name;
            }
        }

        return $invalid_files;
    }
}
