<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Origin\Html;

use DOMNode;
use DOMXPath;
use DOMDocument;

class Html
{
    /**
     * Converts text to html
     *
     * @param string $text
     * @param array $options (tag)
     *   -tag: default:p tag to wrap lines ines e.g. ['tag'=>'div']
     *   -escape: default is true. Escapes text before converting it to html.
     * @return string
     */
    public static function fromText(string $text, array $options = []): string
    {
        $options += ['tag' => 'p','escape' => true];
        if ($options['escape']) {
            $text = static::escape($text);
        }
        $out = [];
        $text = str_replace("\r\n", "\n", $text); // Standarize line endings
        $lines = explode("\n\n", $text);
        foreach ($lines as $line) {
            $line = str_replace("\n", '<br>', $line);
            $out[] = sprintf('<%s>%s</%s>', $options['tag'], $line, $options['tag']);
        }

        return implode("\n", $out);
    }

    /**
     * When saving HTML from the DomDocument it adds a wrapper and
     * LIBXML_HTML_NOIMPLIED is not stable
     *
     * @internal This is only suppose to remove wrapper added by DOMDocument
     *
     * @param string $original
     * @param string $html
     * @return string
     */
    private static function removeWrapper(string $original, string $html): string
    {
        $html = trim($html);

        if (! preg_match('/<html[^>]*>/i', $original) && substr($html, 0, 6) === '<html>' && substr($html, -7) === '</html>') {
            $html = substr($html, 6, -7);
        }
        if (! preg_match('/<body[^>]*>/i', $original) && substr($html, 0, 6) === '<body>' && substr($html, -7) === '</body>') {
            $html = substr($html, 6, -7);
        }

        return $html;
    }

    /**
     * Minifies HTML
     *
     * @see https://www.w3.org/TR/REC-html40/struct/text.html#h-9.1
     *
     * @param string $html
     * @param array $options The following options keys are supported:
     *  - collapseWhitespace: default:true. Collapse whitespace in the text nodes
     *  - conservativeCollapse: default:false. Always collapse whitespace to at least 1 space
     *  - collapseInlineTagWhitespace: default:false. Don't leave any spaces between inline elements.
     *  - minifyJs: default:false minifies inline Javascript (beta)
     *  - minifyCss: default:false minifies inline CSS (beta)
     *  into problems.
     * @return string
     */
    public static function minify(string $html, array $options = []): string
    {
        $options += [
            'collapseWhitespace' => true,
            'conservativeCollapse' => false,
            'collapseInlineTagWhitespace' => false,
            'minifyJs' => false,
            'minifyCss' => false
        
        ];

        $keepWhitespace = ['address', 'pre', 'script', 'style'];
        /**
         * Many lists are incomplete, this list is merge from multiple
         * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Inline_elements
         */
        $inlineElements = [
            'a','abbr','acronym','audio','b','bdi','bdo','big','br','button','canvas','cite','code','data','datalist','del','dfn','em','embed','i','iframe','img','input','ins','kbd','label','map','mark','meter','noscript','object','output','picture','progress','q','ruby','s','samp','script','select','small','span','strong','sub','sup','textarea','time','tt','var'
        ];

        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;
        @$doc->loadHTML($html, LIBXML_HTML_NODEFDTD);
        $doc->normalizeDocument();
             
        // remove comments respecting pre
        foreach ((new DOMXPath($doc))->query('//comment()') as $comment) {
            $comment->parentNode->removeChild($comment);
        }

        foreach ((new DOMXPath($doc))->query('//text()') as $node) {
            if (($options['minifyJs'] && $node->parentNode->nodeName === 'script') || $options['minifyCss'] && $node->parentNode->nodeName === 'style') {
                // remove multiline comments
                $node->nodeValue = preg_replace('/\/\*[\s\S]*?\*\//', '', $node->nodeValue);
                // single line comment (safe) there must be a space after the //
                $node->nodeValue = preg_replace('/\/\/ .*/', '', $node->nodeValue);
                $node->nodeValue = preg_replace('/[^\S ]+/s', $options['conservativeCollapse'] ? ' ' : '', $node->nodeValue);
                // convert multiple spaces into single space
                $node->nodeValue = preg_replace('/(\s)+/s', '\\1', $node->nodeValue);
            }

            // check parent and parent plus 1
            if (in_array($node->parentNode->nodeName, $keepWhitespace) || in_array($node->parentNode->parentNode->nodeName, $keepWhitespace)) {
                continue;
            }

            $previousIsInline = $node->previousSibling && in_array($node->previousSibling->nodeName, $inlineElements);
            $nextIsInline = $node->nextSibling && in_array($node->nextSibling->nodeName, $inlineElements);
            $betweenInline = $previousIsInline && $nextIsInline;

            /**
             * Whitespace between block elements are ignored and whitespace between inline elements
             * are transformed into a space
             */
            $replace = ($options['conservativeCollapse'] || $betweenInline) ? ' '  : '';

            // replace whitespace characters
            $node->nodeValue = preg_replace('/[^\S ]+/s', $replace, $node->nodeValue);
            // convert multiple spaces into single space
            $node->nodeValue = preg_replace('/(\s)+/s', '\\1', $node->nodeValue);

            /**
             * conservativeCollapse always leaves at least one space, so no trimming here
             */
            if ($options['conservativeCollapse']) {
                continue;
            }
            /**
             * Clean up in tag values, this needs to be done carefully hence the ltrim & rtrim
             * cleans up things like <h1> heading </h1>.
             */
            if ($options['collapseWhitespace'] && $node->nodeValue !== ' ') {
                if ($node->previousSibling && ! $previousIsInline) {
                    $node->nodeValue = ltrim($node->nodeValue);
                }
                if ($node->nextSibling && ! $nextIsInline) {
                    $node->nodeValue = rtrim($node->nodeValue);
                }
                /**
                 * I have added this for when there are no siblings and its not in an inline element.
                 */
                if (! $node->previousSibling && ! $node->nextSibling && ! in_array($node->parentNode->nodeName, $inlineElements)) {
                    $node->nodeValue = trim($node->nodeValue);
                }
                continue;
            }

            /**
             * How to handle empty text nodes (can be spaces between tags)
             */
            if ($node->nodeValue === ' ' && ($options['collapseInlineTagWhitespace'] || (! $options['conservativeCollapse'] && ! $betweenInline))) {
                $node->nodeValue = '';
            }
        }
       
        return static::removeWrapper($html, $doc->saveHTML() ?: 'An error occured');
    }

    /**
     * A Simple Html To Text Function.
     *
     * @param string $html
     * @param array $options The options keys are
     *  - format: default:true formats output. If false then it will provide a cleaner
     * @return string
     */

    public static function toText(string $html, array $options = []): string
    {
        $options += ['format' => true];
        $html = static::stripTags($html, ['script', 'style', 'iframe']);
        $html = static::minify($html);

        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;

        $html = str_replace(["\r\n", "\n"], PHP_EOL, $html); // Standardize line endings

        /**
         * Create a text version without formatting, just adds new lines, indents for lists, and list type, e.g number
         * or *
         */
        if ($options['format'] === false) {
            // ul/li needs to be formatted to work with sublists
            $html = preg_replace('/^ +/m', '', $html); // remove whitespaces from start of each line
            $html = preg_replace('/(<\/(h1|h2|h3|h4|h5|h6|tr|blockquote|dt|dd|table|p)>)/', '$1' . PHP_EOL, $html);
            $html = preg_replace('/(<(h1|h2|h3|h4|h5|h6|table|blockquote|p[^re])[^>]*>)/', PHP_EOL . '$1', $html);
            $html = str_replace("</tr>\n</table>", '</tr></table>', $html);
            $html = preg_replace('/(<br>)/', '$1' . PHP_EOL, $html);
            $html = preg_replace('/(<\/(th|td)>)/', '$1 ', $html); //Add space
        }

        @$doc->loadHTML($html, LIBXML_HTML_NODEFDTD);
        $process = ['a', 'img', 'br', 'code', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'table','li','ul', 'ol', 'blockquote'];

        if ($options['format'] === false) {
            $process = ['img','a','ul', 'ol']; // li?
        }
    
        /**
         * Do not sort. The order is important. Certain elements need to be adjusted first including links, images
         */
        foreach ($process as $needle) {
            $nodes = $doc->getElementsByTagName($needle);
            foreach ($nodes as $node) {
                static::processTag($node, $doc);
            }
        }
 
        return trim($doc->textContent);
    }

    /**
     * Check if value needs converting and convert
     *
     * @param string $value
     * @return mixed
     */
    private static function htmlspecialchars(string $value)
    {
        if (strpos($value, '&') !== false) {
            $value = htmlspecialchars($value);
        }

        return $value;
    }
    /**
     * Processes a tag from a DOMDocument
     *
     * @internal Attempting to modify the dom causes strange issues and even recursion
     * @param \DOMNode $tag
     * @param \DOMDocument $doc
     * @param boolean $format
     * @return void
     */

    private static function processTag(DOMNode $tag, DOMDocument $doc): void
    {
        $value = static::htmlspecialchars($tag->nodeValue);

        switch ($tag->tagName) {
            case 'a':
                $tag->nodeValue = "{$value} [" . static::htmlspecialchars($tag->getAttribute('href'))  . ']';
                break;
            case 'br':
                $tag->nodeValue = PHP_EOL;
                break;
            case 'code':
                // indent multi line
                if (strpos($tag->nodeValue, PHP_EOL) !== false) {
                    $tag->nodeValue = PHP_EOL . '   ' . str_replace(PHP_EOL, PHP_EOL . '   ', $value) . PHP_EOL;
                }
                break;
            case 'blockquote':
                $tag->nodeValue = PHP_EOL . '"' . $value . '"' . PHP_EOL;
                break;
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':

                $repeat = '=';
                if ($tag->tagName !== 'h1') {
                    $repeat = '-';
                }
                /**
                 * Use insertBefore instead of replace which causes issues even if you
                 * use array to loop
                 */
                $u = str_repeat($repeat, mb_strlen($tag->nodeValue));
                $div = $doc->createElement('div', "\n{$value}\n{$u}\n");
                $tag->parentNode->insertBefore($div, $tag);
                $tag->nodeValue = null;

                break;
                case 'li':
                    if ($tag->hasChildNodes()) {
                        foreach ($tag->childNodes as $child) {
                            if (in_array($child->nodeName, ['ul','ol'])) {
                                static::processTag($child, $doc);
                            }
                        }
                    }
                break;

            case 'img':
   
                $alt = '';
                if ($tag->hasAttribute('alt')) {
                    $alt = $tag->getAttribute('alt') . ' ';
                }
                $alt = htmlspecialchars($alt);
                $tag->nodeValue = "[image: {$alt}" . static::htmlspecialchars($tag->getAttribute('src')) . ']';
                break;
            case 'ol':
                $count = 1;
                $lineBreak = PHP_EOL;
                $indent = static::getIndentLevel($tag);
                $pre = str_repeat(' ', $indent);
                foreach ($tag->childNodes as $child) {
                    if (isset($child->tagName) && $child->tagName === 'li') {
                        $child->nodeValue = $lineBreak . $pre .  $count . '. ' . static::htmlspecialchars($child->nodeValue);
                        $child->nodeValue = rtrim($child->nodeValue) . PHP_EOL; // friendly with nested lists
                        $count++;
                        $lineBreak = null;
                    }
                }
                break;
            case 'p':
                $tag->nodeValue = PHP_EOL . $value . PHP_EOL;
                break;

            case 'table':
                $data = [];
                $headers = false;
                foreach ($tag->getElementsByTagName('tr') as $node) {
                    $row = [];
                    foreach ($node->childNodes as $child) {
                        if (isset($child->tagName) && ($child->tagName === 'td' || $child->tagName === 'th')) {
                            if ($child->tagName === 'th') {
                                $headers = true;
                            }
                            $row[] = $child->nodeValue;
                        }
                    }
                    $data[] = $row;
                }
                if ($data) {
                    $data = static::arrayToTable($data, $headers);
                }
                
                // Replacing can cause issues
                $div = $doc->createElement('div', PHP_EOL . implode(PHP_EOL, $data) . PHP_EOL);
                $tag->parentNode->insertBefore($div, $tag);
                $tag->nodeValue = null;

                break;
            case 'ul':
         
                $lineBreak = PHP_EOL;
                $indent = static::getIndentLevel($tag);
                $pre = str_repeat(' ', $indent);

                foreach ($tag->childNodes as $child) {
                    if (isset($child->tagName) && $child->tagName === 'li') {
                        $child->nodeValue = $lineBreak . $pre . '* ' .   static::htmlspecialchars($child->nodeValue);
                        $child->nodeValue = rtrim($child->nodeValue) . PHP_EOL; // friendly with nested lists
                        $lineBreak = null;
                    }
                }
                break;
        }
        // Remove all attributes
        foreach ($tag->attributes as $attr) {
            $tag->removeAttribute($attr->nodeName);
        }
    }

    /**
     * Gets the indent level for ul/ol
     *
     * @param DOMNode $node
     * @return integer
     */
    private static function getIndentLevel(DOMNode $node): int
    {
        $indent = 0;
        $checkLevelUp = true;
        $current = $node;
    
        while ($checkLevelUp) {
            if ($current->parentNode->nodeName === 'li') {
                $current = $current->parentNode;
                $indent = $indent + 3;
            } else {
                $checkLevelUp = false;
            }
        }

        return $indent;
    }

    /**
     * Internal for creating table
     *
     * @param array $array
     * @param boolean $headers
     * @return array
     */
    private static function arrayToTable(array $array, bool $headers = true): array
    {
        // Calculate width of each column
        $widths = [];
        foreach ($array as $row) {
            foreach ($row as $columnIndex => $cell) {
                if (! isset($widths[$columnIndex])) {
                    $widths[$columnIndex] = 0;
                }
                $width = strlen($cell) + 4;
                if ($width > $widths[$columnIndex]) {
                    $widths[$columnIndex] = $width;
                }
            }
        }

        $out = [];
        $seperator = '';

        foreach ($array[0] as $i => $cell) {
            $seperator .= str_pad('+', $widths[$i], '-', STR_PAD_RIGHT);
        }
        $seperator .= '+';
        $out[] = $seperator;

        if ($headers) {
            $headers = '|';
            foreach ($array[0] as $i => $cell) {
                $headers .= ' ' . str_pad($cell, $widths[$i] - 2, ' ', STR_PAD_RIGHT) . '|';
            }
            $out[] = $headers;
            $out[] = $seperator;
            array_shift($array);
        }

        foreach ($array as $row) {
            $cells = '|';
            foreach ($row as $i => $cell) {
                $cells .= ' ' . str_pad($cell, $widths[$i] - 2, ' ', STR_PAD_RIGHT) . '|';
            }
            $out[] = $cells;
        }
        $out[] = $seperator;

        return $out;
    }

    /**
     * Cleans up user inputted html for saving to a database
     *
     * @param string $html
     * @param array $tags An array of tags to be allowed e.g. ['p','h1'] or to
     * only allow certain attributes on tags ['p'=>['class','style]];
     * The defaults are :
     * ['h1', 'h2', 'h3', 'h4', 'h5', 'h6','p','i', 'em', 'strong', 'b', 'del', 'blockquote' => ['cite']
     * 'a','ul', 'li', 'ol', 'br','code', 'pre', 'div', 'span']
     * @return string
     */
    public static function sanitize(string $html, array $tags = null): string
    {
        $defaults = [
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6','p','i', 'em', 'strong', 'b', 'del', 'blockquote' => ['cite'],'a','ul', 'li', 'ol', 'br','code', 'pre', 'div', 'span',
        ];

        if ($tags === null) {
            $tags = $defaults;
        }

        // Normalize tag options
        $options = [];
        foreach ($tags as $key => $value) {
            if (is_int($key)) {
                $key = $value;
                $value = [];
            }
            $options[$key] = $value;
        }

        $html = str_replace(["\r\n", "\n"], PHP_EOL, $html); // Standardize line endings
        /**
         * When document is imported it will have HTML and body tag.
         */
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;

        /**
         * Add html/body but not doctype. body will be removed later
         */
        @$doc->loadHTML($html, LIBXML_HTML_NODEFDTD);
        foreach ($doc->firstChild->childNodes as $node) {
            static::_sanitize($node, $options); // body
        }
        
        return static::removeWrapper($html, $doc->saveHTML() ?: 'An error occured');
    }

    /**
     * Workhorse
     *
     * @param \DOMNode $node
     * @param array $tags
     * @return void
     */
    private static function _sanitize(DOMNode $node, array $tags = []): void
    {
        if ($node->hasChildNodes()) {
            for ($i = 0; $i < $node->childNodes->length; $i++) {
                static::_sanitize($node->childNodes->item($i), $tags);
            }
        }
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return;
        }

        $remove = $change = $attributes = [];
        if (! isset($tags[$node->nodeName]) && $node->nodeName !== 'body') {
            $remove[] = $node;
            /* This is for keeping text between divs. Keep for now until committed
            foreach ($node->childNodes as $child) {
                     $change[] = [$child, $node];
                 }
             */
        }

        if ($node->attributes) {
            foreach ($node->attributes as $attr) {
                if (! isset($tags[$node->nodeName]) || ! in_array($attr->nodeName, $tags[$node->nodeName])) {
                    $attributes[] = $attr->nodeName;
                }
            }
        }

        /**
         * Remove attributes
         */
        foreach ($attributes as $attr) {
            $node->removeAttribute($attr);
        }

        /*
        # Add inserts first
        foreach ($change as list($a, $b)) {
            $b->parentNode->insertBefore($a, $b);
        }
        */

        # Now remove what we need
        foreach ($remove as $n) {
            if ($n->parentNode) {
                $n->parentNode->removeChild($n);
            }
        }
    }

    /**
     * Strips HTML tags and the content of those tags
     *
     * @param string $html
     * @param array $tags array of tags to strip, leave empty to strip all tags
     * @return string|null text or html
     */
    public static function stripTags(string $html, array $tags = []): ?string
    {
        $doc = new DOMDocument();
        /**
         * Html should not be modified in anyway
         */
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = false;
        @$doc->loadHTML($html, LIBXML_HTML_NODEFDTD);
        $remove = [];
        foreach ($tags as $tag) {
            $nodes = $doc->getElementsByTagName($tag);
            foreach ($nodes as $node) {
                $remove[] = $node;
            }
        }
        foreach ($remove as $node) {
            $node->parentNode->removeChild($node);
        }
      
        return static::removeWrapper($html, $doc->saveHTML() ?: 'An error occured');
    }

    /**
     * Escapes Html for output in a secure way
     *
     * @param string $html
     * @param string $encoding
     * @return string
     */
    public static function escape(string $html, string $encoding = 'UTF-8'): string
    {
        return htmlspecialchars($html, ENT_QUOTES, $encoding);
    }
}
