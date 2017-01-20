<?php

/**
 * @file FileAbstract.php
 */

namespace Minisite\File;

use \ZipArchive;
use Minisite\Exception\RuntimeException;
use Minisite\Exception\InvalidArgumentException;

/**
 * Class FileAbstract
 * @package Minisite\File
 */
abstract class FileAbstract implements FileInterface
{
    private $_file;
    private $_archive;
    private $_ignored = [];
    private $_invalid_extensions = [];

    /**
     * FileAbstract constructor.
     * @param $file
     * @param null $flags
     */
    public function __construct($file, $flags = null)
    {
        if (!empty($file)) {
            self::setFile($file);
            self::open($file, $flags);
        } else {
            throw new InvalidArgumentException(FileStatus::getStatus(ZipArchive::ER_NOENT));
        }
    }

    /**
     * Open archive file.
     * @param $file
     */
    public function open($file, $flags = null)
    {
        if (empty($file)) {
            throw new InvalidArgumentException(FileStatus::getStatus(ZipArchive::ER_NOENT));
        }

        $archive = new ZipArchive();
        $open = $archive->open($file, $flags);

        if ($open !== true) {
            throw new RuntimeException(FileStatus::getStatus($open));
        }

        $this->setArchive($archive);

        // Set ignored.
        $this->setIgnored();

        return $archive;
    }

    /**
     * @param $file
     */
    public function validate($file)
    {
        if (empty($file)) {
            throw new InvalidArgumentException(FileStatus::getStatus(ZipArchive::ER_NOENT));
        }

        try {
            $archive = new ZipArchive();
            $open = $archive->open($file, ZipArchive::CHECKCONS);


        } catch (RuntimeException $exception) {
            throw $exception;
        }

        return true;
    }

    /**
     * Exact files to given path.
     */
    public function extract($path, array $files = array())
    {
        if (empty($path)) {
            throw new InvalidArgumentException('Invalid destination path');
        }

        if ($files) {
            $this->getArchive()->extractTo($path, $files);
        } else {
            $this->getArchive()->extractTo($path);
        }

        return $this;
    }

    /**
     * Remove file from archive file.
     * @param $file
     * @return $this
     */
    public function remove($file)
    {
        $this->getArchive()->deleteName($file);

        return $this;
    }

    /**
     * Get a list of files in archive (array).
     * @return array
     */
    public function lists()
    {
        $list = [];

        for ($i = 0; $i < $this->_archive->numFiles; $i++) {
            $name = $this->_archive->getNameIndex($i);
            if ($name === false) {
                throw new RuntimeException(FileStatus::getStatus($this->_archive->status));
            }

            // Handle ignore item.
            if (!empty($this->_ignored) && $this->checkIgnore($name) === true) {
                // Remove this file from archive.
                $this->remove($name);
                continue;
            }

            // Add file into lists.
            array_push($list, $name);
        }

        return $list;
    }

    /**
     * Return lists as tree.
     * @return array
     */
    public function listsTree()
    {
        $list = $this->lists();
        $tree = [];

        foreach ($list as $file_path) {
            $parts = explode('/', $file_path);
            // Files in archive end in / if a directory.
            if (substr($file_path, -1) === '/') {
                $parts = array_slice($parts, 0, -1);
                \Minisite\minisite_array_set_nested_value($tree, $parts, array('.' => $file_path));
            } else {
                \Minisite\minisite_array_set_nested_value($tree, $parts, $file_path);
            }
        }

        return $tree;
    }

    /**
     * Close the archive.
     * @return bool
     */
    function close()
    {
        if ($this->_archive->close() === false) {
            throw new RuntimeException(FileStatus::getStatus($this->_archive->status));
        } else {
            return true;
        }
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->_file;
    }

    /**
     * @param mixed $file
     */
    public function setFile($file)
    {
        $this->_file = $file;
    }

    /**
     * @return mixed
     */
    public function getArchive()
    {
        return $this->_archive;
    }

    /**
     * @param ZipArchive $archive
     */
    public function setArchive(ZipArchive $archive)
    {
        $this->_archive = $archive;
    }

    /**
     * @return array
     */
    public function getIgnored()
    {
        foreach ($this->_ignored as $key => $item) {
            $this->_ignored[$key] = preg_quote($item);
        }

        return $this->_ignored;
    }

    /**
     * @param array $ignored
     */
    public function setIgnored(array $ignored = [])
    {
        $ignored_default = [
            '.svn',
            '__macosx',
            '.DS_Store',
            '.dropbox',
            'thumbs.db',
            'desktop.ini',
        ];
        $this->_ignored = $ignored_default + $ignored;
    }

    /**
     * @param $file
     * @return bool
     */
    protected function checkIgnore($file)
    {
        // Handle ignored item.
        $pattern = '/('.implode('|', $this->_ignored).')/i';
        if (preg_match($pattern, $file)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return array
     */
    public function getInvalidExtensions()
    {
        return $this->_invalid_extensions;
    }

    /**
     * @param array $invalid_extensions
     */
    public function setInvalidExtensions(array $invalid_extensions = [])
    {
        $this->_invalid_extensions = $invalid_extensions;
    }
}
