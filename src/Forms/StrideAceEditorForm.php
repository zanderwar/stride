<?php
namespace SilverLeague\Stride\Forms;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;

/**
 * Class StrideAceEditorForm
 *
 * @author Reece Alexander <reece@steadlane.com.au>
 * @package SilverLeague\Stride\Forms
 */
class StrideAceEditorForm extends Form
{
    /**
     * StrideAceEditorForm constructor.
     *
     * @param Controller $controller
     * @param string $name Will be used as the identifier as this form is created dynamically depending on the input file
     */
    public function __construct(Controller $controller, $name)
    {
        $fields = FieldList::create(
            AceEditorField::create($name, $name)->setTitle(null)
        );

        $actions = FieldList::create(

        );

        parent::__construct($controller, $name, $fields, $actions);
    }
}