<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms;

class Form
{
    protected $fields;
    protected $focusField;

    function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * for each field with a validation error, this adds the 'error' key and message and an error class attribute to fieldName
     */
    private function insertErrors()
    {
        if (isset($_SESSION['validationErrors'])) {
            $eCount = 0;
            foreach ($_SESSION['validationErrors'] as $fieldName => $errorMessage) {
                $eCount++;
                if ($eCount == 1) {
                    // sets to the first error field
                    $this->focusField = $fieldName;
                }
                if (array_key_exists($fieldName, $this->fields)) {
                    if (isset($this->fields[$fieldName]['attributes']['class'])) {
                        $this->fields[$fieldName]['attributes']['class'] .= " formFieldError";
                    } else {
                        $this->fields[$fieldName]['attributes']['class'] = "formFieldError";
                    }
                    $this->fields[$fieldName]['error'] = $errorMessage;
                }
            }
            unset($_SESSION['validationErrors']);
        }
    }

    /** this may need to be overridden in DatabaseTableForm because of the isBoolean stuff for checkboxes */
    private function insertValues(array $values)
    {
        foreach ($this->fields as $fieldName => $fieldInfo) {

            if (isset($values[$fieldName]) && ((!array_key_exists('persist', $fieldInfo) || $fieldInfo['persist']))) {

                $value = $values[$fieldName];

                switch ($fieldInfo['tag']) {

                    case 'textarea':
                        $this->fields[$fieldName]['value'] = $value;
                        break;

                    case 'select':
                        // leave unchanged if no value was submitted so top (disabled) option remains selected by default
                        if (strlen($value) > 0) {
                            $this->fields[$fieldName]['selected'] = $value;
                        }
                        break;

                    default:
                        if ($fieldInfo['attributes']['type'] == 'checkbox') {
                            if (isset($fieldInfo['isBoolean']) && $fieldInfo['isBoolean'] && $value == 't') {
                                $this->fields[$fieldName]['attributes']['checked'] = 'checked';
                            }
                        } else {
                            $this->fields[$fieldName]['attributes']['value'] = $value;
                        }
                }
            }
        }
    }

    /**
     * @param array|null $values (could be db record)
     * if values input is array use that to insert values, if not and values are in session (ie form was submitted), use that then unset the session var
     * also sets the focus field to either the first field, if no errors (done in setFocusField()), or the first field with an error (done in insertErrors())
     */
    public function insertValuesErrors(array $values = null)
    {
        if (is_array($values)) {
            $this->insertValues($values);

        } elseif (isset($_SESSION['formInput']) && is_array($_SESSION['formInput'])) {
            $this->insertValues($_SESSION['formInput']);
            unset($_SESSION['formInput']);
        }

        $this->setFocusField();
        $this->insertErrors();
    }

    // sets to the first field
    private function setFocusField()
    {
        foreach ($this->fields as $fieldName => $fieldInfo) {
            $this->focusField = $fieldName;
            break;
        }
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getGeneralError()
    {
        $generalFormError = $_SESSION['generalFormError'] ?? '';
        unset($_SESSION['generalFormError']);
        return $generalFormError;
    }

    public function getFocusField()
    {
        if (isset($this->focusField)) {
            return $this->focusField;
        }
        return '';
    }

    public static function getSubmitField(string $value = 'Go!', string $name = 'submit')
    {
        return [
            'tag' => 'input',
            'attributes' => [
                'type' => 'submit',
                'name' => $name,
                'value' => $value
            ]
        ];
    }

    public static function getPutMethodField()
    {
        return [
            'tag' => 'input',
            'attributes' => [
                'type' => 'hidden',
                'name' => '_METHOD',
                'value' => 'PUT'
            ]
        ];
    }

    public function getValidationRules(): array
    {
        $rules = [];
        foreach ($this->fields as $fieldName => $fieldInfo) {
            if (isset($fieldInfo['validation'])) {
                $rules[$fieldName] = $fieldInfo['validation'];
            }
        }

        return $rules;
    }

    public static function getSelectField(array &$formField, array $options, $defaultValue)
    {
        $formField['tag'] = 'select';
        $formField['options']['-- select --'] = 'disabled';
        $optionValues = []; // to use in validation
        foreach ($options as $optionValue => $optionText) {
            $formField['options'][$optionText] = $optionValue;
            $optionValues[] = $optionValue;
        }
        // set initial value to default else to top option (-- select --)
        $formField['selected'] = ($defaultValue != null && strlen($defaultValue) > 0) ? $defaultValue : 'disabled';
        $formField['validation']['enum'] = $optionValues;
    }
}
