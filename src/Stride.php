<?php
namespace SilverLeague\Stride;

use DirectoryIterator;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Object;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * Class SilverStride
 *
 * @author Reece Alexander <reece@steadlane.com.au>
 * @package SilverLeague\SilverStride
 */
class Stride extends Object
{
    /**
     * Defaults, this can be modified through the `updateWatchedFileTypes` decorator
     *
     * @see ::getWatchedFileTypes()
     * @var array
     */
    public $watchedFileTypes = [
        'css',
        'js',
        'ss'
    ];

    /**
     * Retrieve the watched file types, has an extension method to update the list
     *
     * @return array
     */
    public function getWatchedFileTypes()
    {
        $fileTypes = $this->watchedFileTypes;

        $this->extend('updateWatchedFileTypes', $fileTypes);

        return $fileTypes;
    }

    /**
     * Gets the complete path for the theme directory
     *
     * @return string
     */
    public function getThemeDir()
    {
        return Controller::join_links(Director::baseFolder(), THEMES_DIR);
    }

    /**
     * @return DBHTMLText
     */
    public function fileTreeAsUL()
    {
        $tree = $this->removeEmptyDirs($this->generateTree($this->getThemeDir()));
        $htmlText = DBHTMLText::create();
        $htmlText->setValue($this->listItemsFromArray($tree));
        return $htmlText;
    }

    /**
     * @param array $array
     * @param bool $skipOpening
     * @return string
     */
    public function listItemsFromArray(array $array, $skipOpening = true)
    {
        $output = !$skipOpening ? "<ul>" : "";

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $output .= "<li data-is-folder='true' data-folder='$key'><div class='stride-tree-folder'>$key</div>" . $this->listItemsFromArray($value, false) . "</li>";
            } else {
                $output .= "<li data-is-file='true' data-filename='$value'><div class='stride-tree-file'>$value</li>";
            }
        }

        $output .= !$skipOpening ? "</ul>" : "";

        return $output;
    }

    /**
     * @param $dir
     * @param string $regex
     * @param bool $ignoreEmpty
     * @return array
     */
    public function generateTree($dir, $regex = '', $ignoreEmpty = false)
    {
        $watched = $this->getWatchedFileTypes();

        if (!$dir instanceof DirectoryIterator) {
            $dir = new DirectoryIterator((string)$dir);
        }

        $dirs = array();
        $files = array();

        foreach ($dir as $node) {
            if ($node->isDir() && !$node->isDot()) {
                $tree = $this->generateTree($node->getPathname(), $regex, $ignoreEmpty);
                if (!$ignoreEmpty || count($tree)) {
                    $dirs[$node->getFilename()] = $tree;
                }
            } elseif ($node->isFile()) {
                $name = $node->getFilename();
                if (in_array(pathinfo($name, PATHINFO_EXTENSION), $watched)) {
                    if ('' == $regex || preg_match($regex, $name)) {
                        $files[] = $name;
                    }
                }
            }
        }
        asort($dirs);
        sort($files);

        return array_merge($dirs, $files);
    }

    /**
     * Strips any empty arrays from the generateTree output to ensure we're not displaying folders with nothing in them
     *
     * @param $tree
     * @return mixed
     */
    public function removeEmptyDirs($tree)
    {
        foreach ($tree as $key => $value) {
            if (is_array($value)) {
                $tree[$key] = $this->removeEmptyDirs($tree[$key]);
            }

            if (empty($tree[$key])) {
                unset($tree[$key]);
            }
        }

        return $tree;
    }

    /**
     * Config accessor for this class
     *
     * {@inheritdoc}
     */
    public static function config()
    {
        return Config::inst()->forClass(self::class);
    }

}