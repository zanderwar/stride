<?php
namespace SilverLeague\Stride\Models;

use DirectoryIterator;
use SilverLeague\Stride\Stride;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Flushable;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * Class FileManifest
 *
 * @author Reece Alexander <reece@steadlane.com.au>
 *
 * @property string Filename
 * @property string PathToFile
 * @package SilverLeague\Stride\Models
 */
class FileManifest extends DataObject implements Flushable
{
    private static $db = [
        'Filename' => 'Varchar(100)',
        'PathToFile' => 'Varchar(255)'
    ];

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
     * Builds the manifest, and returns the amount of files found/saved as an integer
     *
     * @param bool $truncate If true, all records will be deleted before scanning
     * @return int
     */
    public function buildManifest($truncate = true)
    {
        if ($truncate) {
            $this->truncate();
        }

        $this->addFiles($this->getFilesInDir($this->getThemeDir()));

        return self::get()->count();
    }

    /**
     * Adds a file. The reason we split out the path from the filename is for the folder structure in the File Browser
     * of the Stride UI and to reduce code bloat down the track
     *
     * @param string $fileOrPathTo If only a filename is provided, be sure to provide the second param with the path to that file
     * @param string|null $pathToFile Path to the filename provided in the first param
     * @return bool
     */
    public function addFile($fileOrPathTo, $pathToFile = null)
    {
        // allows you to pass the complete path including file name
        if (is_file($fileOrPathTo)) {
            $pieces = explode(DIRECTORY_SEPARATOR, $fileOrPathTo);
            $fileOrPathTo = array_pop($pieces);
            $pathToFile = implode(DIRECTORY_SEPARATOR, $pieces);
        }

        // is the file type supported?
        if (!in_array(pathinfo($fileOrPathTo, PATHINFO_EXTENSION), $this->watchedFileTypes)) {
            user_error(
                _t(
                    'STRIDE.FileNotSupported',
                    'The file ({file}) is not supported.',
                    'When a user attempts to add an unsupported file type to the watcher list',
                    [
                        'file' => $fileOrPathTo
                    ]
                ),
                E_USER_ERROR
            );
        }

        // does the directory exist?
        if (!is_dir($pathToFile)) {
            user_error(
                _t(
                    'STRIDE.PathIsNotDir',
                    'The provided path ({path}) is not a directory.',
                    'The message shown when a user tries to add a file where the containing folder does not exist',
                    [
                        'path' => $pathToFile
                    ]
                ),
                E_USER_ERROR
            );
        }

        // does the file exist?
        if (!is_file(Controller::join_links($pathToFile, $fileOrPathTo))) {
            user_error(
                _t(
                    'STRIDE.FileDoesNotExist',
                    'The file ({file}) was not found in ({path})',
                    'The message shown when a user tries to add a file where the file itself does not exist',
                    [
                        'file' => $fileOrPathTo,
                        'path' => $pathToFile
                    ]
                ),
                E_USER_ERROR
            );
        }

        // does the file already exist in DB?
        $record = self::get()->filter(
            [
                'Filename' => $fileOrPathTo,
                'PathToFile' => $pathToFile
            ]
        )->first();

        if ($record) {
            return true;
        }

        // checks pass, add file
        $record = self::create();
        $record->Filename = $fileOrPathTo;
        $record->PathToFile = $pathToFile;

        return ($record->write() > 0);
    }

    /**
     * Adds multiple files to the DB if they don't already exist
     *
     * @param array $files An array of full paths to a file
     *
     * @return void
     */
    public function addFiles(array $files)
    {
        foreach ($files as $file) {
            $this->addFile($file);
        }
    }

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
     * Retrieve all watched files in a particular directory, can scan recursively
     *
     * @param string $dir
     * @param bool $recursive If true this method will recursively search directories for watched files
     *
     * @return array
     */
    public function getFilesInDir($dir, $recursive = true)
    {
        $watched = $this->getWatchedFileTypes();

        $files = [];

        if (!is_dir($dir)) {
            user_error(
                _t(
                    'STRIDE.IsNotDirectory',
                    '{directory} is not a directory',
                    'Shown when a user attempts to get a list of files from a directory that does not exist',
                    [
                        'directory' => $dir
                    ]
                ),
                E_USER_ERROR
            );
        }

        $handle = opendir($dir);
        while ($handle && ($file = readdir($handle)) !== false) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }

            $file = Controller::join_links($dir, $file);

            if ($recursive && is_dir($file)) {
                $files = array_merge($files, $this->getFilesInDir($file));
                continue;
            }

            if (!in_array(pathinfo($file, PATHINFO_EXTENSION), $watched)) {
                continue;
            }

            $files[] = $file;
        }

        if (is_resource($dir)) {
            closedir($dir);
        }

        return $files;
    }

    /**
     * Truncate this DataObject, used when flushing / rebuilding the list
     *
     * @return void
     */
    public function truncate()
    {
        $records = self::get();
        foreach ($records as $record) {
            $record->delete();
        }
    }

    /**
     * This function is triggered early in the request if the "flush" query
     * parameter has been set. Each class that implements Flushable implements
     * this function which looks after it's own specific flushing functionality.
     *
     * @see FlushRequestFilter
     */
    public static function flush()
    {
        self::singleton()->buildManifest();
    }

    public function asUL()
    {
        $tree = $this->stripEmptyFromTree($this->dirtree($this->getThemeDir()));
        $htmlText = DBHTMLText::create();
        $htmlText->setValue($this->ulFromArray($tree));
        return $htmlText;
    }

    public function ulFromArray(array $array) {
        $output = "<ul>";
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $output .= "<li data-folder='$key'>$key" . $this->ulFromArray($value) . "</li>";
            } else {
                $output .= "<li data-file='$value'>$value</li>";
            }
        }
        $output .= "</ul>";

        return $output;
    }

    /**
     * @param $dir
     * @param string $regex
     * @param bool $ignoreEmpty
     * @return array
     */
    public function dirTree($dir, $regex = '', $ignoreEmpty = false)
    {
        $watched = $this->getWatchedFileTypes();

        if (!$dir instanceof DirectoryIterator) {
            $dir = new DirectoryIterator((string)$dir);
        }

        $dirs = array();
        $files = array();

        foreach ($dir as $node) {
            if ($node->isDir() && !$node->isDot()) {
                $tree = $this->dirTree($node->getPathname(), $regex, $ignoreEmpty);
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

    public function stripEmptyFromTree($tree)
    {
        foreach ($tree as $key => $value) {
            if (is_array($value)) {
                $tree[$key] = $this->stripEmptyFromTree($tree[$key]);
            }

            if (empty($tree[$key])) {
                unset($tree[$key]);
            }
        }

        return $tree;
    }

    /**
     * @param $path
     * @return string
     */
    public function getPathAfterThemesDir($path)
    {
        $pieces = explode($this->getThemeDir(), $path);

        foreach ($pieces as $key => $dir) {
            if (!strlen($dir)) {
                unset($pieces[$key]);
            }
        }

        return rtrim(ltrim(implode(DIRECTORY_SEPARATOR, $pieces), DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);
    }
}