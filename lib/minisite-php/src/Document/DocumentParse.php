<?php

/**
 * @file DocumentParse.php
 */

namespace Minisite\Document;

/**
 * Class DocumentParse
 * @package Minisite\Document
 */
class DocumentParse
{
    private $_document;
    private $_options = [];

    /**
     * DocumentParse constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        self::setOptions($options);
    }

    /**
     * Parse the document.
     */
    public function parse()
    {
        $options = $this->getOptions();

        if (!empty($options['base'] && !empty($options['base_href']))) {
            $document = $this->getDocument();
            $head = $document->getElementsByTagName('head')->item(0);
            if (!empty($head)) {
                // Update base href.
                $tag_base = $document->createElement('base');
                $tag_base->setAttribute('href', $options['base_href']);
                if ($head->hasChildNodes()) {
                    $head->insertBefore($tag_base, $head->firstChild);
                } else {
                    $head->appendChild($tag_base);
                }

                // Save the document.
                self::setDocument($document);
            }
        }

        if (!empty($options['absolute'] && !empty($options['absolute_path']))) {
            $document = $this->getDocument();

            // Update link tag.
            foreach ($document->getElementsByTagName('a') as $item) {
                $href = $item->getAttribute('href');

                // Keep absolute URL.
                if (parse_url($href, PHP_URL_SCHEME) != '') {
                    continue;
                }

                // If href starts with . or root link.
                if (substr($href, 0) == '.' || substr($href, 0) == '/') {
                    continue;
                }

                // If href starts with relative path.
                if (substr($href, 0, 2) == '..') {
                    $item->setAttribute('href', $options['absolute_path'].'/'.substr($href, 3));
                    continue;
                }

                // If href is marked not rewrite, then ignore it.
                //$regex = '/\.(' . preg_replace('/ +/', '|', preg_quote(MINISITE_EXTENSIONS_NOREWRITE)) . ')$/i';
                //if (preg_match($regex, $href)) {
                //    continue;
                //}

                // Default URL rewrite behaviours.
                $relative = empty($options['relative_path']) ? '' : $options['relative_path'].'/';
                $item->setAttribute('href', $options['absolute_path'].'/'.$relative.$href);
            }

            // Save the document.
            self::setDocument($document);

            // Get the document;
            $document = $this->getDocument();

            // Replace header link.
            foreach ($document->getElementsByTagName('link') as $item) {
                $href = $item->getAttribute('href');
                // Relative path.
                if (substr($href, 0, 2) == '..') {
                    $item->setAttribute('href', $options['absolute_path'].'/'.substr($href, 3));
                    continue;
                }

                // Default URL rewrite behaviours.
                $relative = empty($options['relative_path']) ? '' : $options['relative_path'].'/';
                $item->setAttribute('href', $options['absolute_path'].'/'.$relative.$href);
            }

            // Save the document.
            self::setDocument($document);

            // Get the document;
            $document = $this->getDocument();

            // Replace script.
            foreach ($document->getElementsByTagName('script') as $item) {
                $src = $item->getAttribute('src');
                // Relative path.
                if (substr($href, 0, 2) == '..') {
                    $item->setAttribute('src', $options['absolute_path'].'/'.substr($src, 3));
                    continue;
                }

                // Default URL rewrite behaviours.
                $relative = empty($options['relative_path']) ? '' : $options['relative_path'].'/';
                $item->setAttribute('src', $options['absolute_path'].'/'.$relative.$src);
            }

            // Save the document.
            self::setDocument($document);

            // Get the document;
            $document = $this->getDocument();

            // Replace image tag.
            foreach ($document->getElementsByTagName('img') as $item) {
                $src = $item->getAttribute('src');
                // Relative path.
                if (substr($href, 0, 2) == '..') {
                    $item->setAttribute('src', $options['absolute_path'].'/'.substr($src, 3));
                    continue;
                }

                // Default URL rewrite behaviours.
                $relative = empty($options['relative_path']) ? '' : $options['relative_path'].'/';
                $item->setAttribute('src', $options['absolute_path'].'/'.$relative.$src);
            }

            // Save the document.
            self::setDocument($document);
        }
    }

    /**
     * @return mixed
     */
    public function getDocument()
    {
        return $this->_document;
    }

    /**
     * @param mixed $document
     */
    public function setDocument($document)
    {
        $this->_document = $document;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options = [])
    {
        $options_default = [
            'absolute' => true,
            'absolute_path' => '',
            'base' => true,
            'base_href' => '',

        ];
        $this->_options = array_merge($options_default, $options);
    }
}
