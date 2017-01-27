<?php
namespace SilverLeague\Stride\Forms;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Forms\TextareaField;
use SilverStripe\View\Requirements;

/**
 * Class AceEditorField.
 *
 * @author Andrew Aitken-Fincham <andrew@silverstripe.com>
 * @package SilverLeague\SilverStride\Forms
 */
class AceEditorField extends TextareaField
{
    /** @var array */
    private static $allowed_actions = array(
        'iframe'
    );

    /**
     * @var string default_mode
     */
    private static $default_mode = 'html';

    /**
     * @var string default_theme
     */
    private static $default_theme = null;

    /**
     * @var string default_dark_theme
     */
    private static $default_dark_theme = 'monokai';

    /**
     * @var string default_light_theme
     */
    private static $default_light_theme = 'github';

    /**
     * @var string mode
     */
    protected $mode;

    /**
     * @var string dark_theme
     */
    protected $dark_theme;

    /**
     * @var string light_theme
     */
    protected $light_theme;

    /**
     * @var string theme
     */
    protected $theme;

    /**
     * @var int Visible number of text lines.
     */
    protected $rows = 8;

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return array_merge(
            parent::getAttributes(),
            array(
                'data-mode' => $this->getMode(),
                'data-ace-path' => $this->getAceEditorPath(),
                'data-theme' => $this->getTheme(),
                'data-dark' => $this->getDarkTheme(),
                'data-light' => $this->getLightTheme()
            )
        );
    }

    /**
     * @param array $properties
     * @return \SilverStripe\ORM\FieldType\DBHTMLText
     */
    public function Field($properties = array())
    {

        $acePath = $this->getAceEditorPath();

        Requirements::javascript($acePath . "ace.js");
        Requirements::javascript($acePath . "mode-" . $this->getMode() . ".js");
        Requirements::javascript(
            Controller::join_links(Director::baseURL(), basename(SILVERSTRIDE_DIR), "javascript/AceEditorField.js")
        );

        return parent::Field($properties);
    }

    /**
     * Set mode
     *
     * @param $mode
     * @return $this
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Get mode
     *
     * @return mixed|string
     */
    public function getMode()
    {
        return $this->mode ? $this->mode : $this->config()->get('default_mode');
    }

    /**
     * Set the active theme for Ace Editor
     *
     * @param $theme
     * @return $this
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;
        return $this;
    }

    /**
     * Get the active theme for Ace Editor
     *
     * @return mixed|string
     */
    public function getTheme()
    {
        if ($this->getDefaultTheme()) {
            return $this->theme ? $this->theme : $this->config()->get('default_theme');
        }

        return $this->theme ? $this->theme : $this->config()->get('default_dark_theme');
    }

    /**
     * Get the default theme
     *
     * @return mixed
     */
    public function getDefaultTheme()
    {
        return $this->config()->get('default_theme');
    }

    /**
     * Get the dark theme
     *
     * @return mixed|string
     */
    public function getDarkTheme()
    {
        return $this->dark_theme ? $this->dark_theme : $this->config()->get('default_dark_theme');
    }

    /**
     * Get the light theme
     *
     * @return mixed|string
     */
    public function getLightTheme()
    {
        return $this->light_theme ? $this->light_theme : $this->config()->get('default_light_theme');
    }

    /**
     * @return string
     */
    public function getAceEditorPath()
    {
        return Controller::join_links(Director::baseURL(), basename(SILVERSTRIDE_DIR), 'thirdparty/ace/');
    }
}