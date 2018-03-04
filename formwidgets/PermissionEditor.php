<?php namespace KEERill\Users\FormWidgets;

use Lang;
use ApplicationException;
use Backend\Classes\FormWidgetBase;


/**
 * User/group permission editor
 * This widget is used by the system internally on the System / Administrators pages.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class PermissionEditor extends FormWidgetBase
{
    public $mode;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->fillFromConfig([
            'mode'
        ]);
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('permissioneditor');
    }

    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        $permissionsData = $this->formField->getValueFromData($this->model);
        if (!is_array($permissionsData)) {
            $permissionsData = [];
        }

        $this->vars['checkboxMode'] = $this->getControlMode() === 'checkbox';
        $this->vars['permissions'] = $this->getOptionsFromModel($this->fieldName);
        $this->vars['baseFieldName'] = $this->getFieldName();
        $this->vars['permissionsData'] = $permissionsData;
        $this->vars['field'] = $this->formField;
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        if (is_array($value)) {
            return $value;
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    protected function loadAssets()
    {
        $this->addCss('css/permissioneditor.css', 'core');
        $this->addJs('js/permissioneditor.js', 'core');
    }

    protected function getControlMode()
    {
        return strlen($this->mode) ? $this->mode : 'radio';
    }


    /**
     * Looks at the model for defined options.
     *
     * @param $field
     * @param $fieldOptions
     * @return mixed
     */
    protected function getOptionsFromModel($attribute)
    {
        $methodName = 'get'.studly_case($attribute).'Options';
        if (
            !$this->objectMethodExists($this->model, $methodName)
        ) {
            throw new ApplicationException(Lang::get('backend::lang.field.options_method_not_exists', [
                'model'  => get_class($this->model),
                'method' => $methodName,
                'field' => $attribute
            ]));
        }

        return $fieldOptions = $this->model->$methodName($this->data);
    }

    /**
     * Internal helper for method existence checks.
     *
     * @param  object $object
     * @param  string $method
     * @return boolean
     */
    protected function objectMethodExists($object, $method)
    {
        if (method_exists($object, 'methodExists')) {
            return $object->methodExists($method);
        }

        return method_exists($object, $method);
    }
}