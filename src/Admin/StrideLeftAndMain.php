<?php
namespace SilverLeague\Stride\Admin;

use SilverLeague\Stride\Forms\StrideAceEditorForm;
use SilverLeague\Stride\Stride;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\View\Requirements;


class StrideLeftAndMain extends LeftAndMain
{
    private static $url_segment = "stride";

    private static $menu_title = "Stride";

    public function init()
    {
        parent::init();

        Requirements::css(Controller::join_links(Director::baseURL(), basename(SILVERSTRIDE_DIR), 'css/style.min.css'));
        Requirements::javascript(Controller::join_links(Director::baseURL(), basename(SILVERSTRIDE_DIR), 'javascript/stride.js'));
    }

    public function AceEditorForm()
    {
        return StrideAceEditorForm::create($this, 'none');
    }

    public function FileTreeAsUL() {
        return Stride::singleton()->fileTreeAsUL();
    }
}