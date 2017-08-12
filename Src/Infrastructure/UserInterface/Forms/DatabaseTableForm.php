<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms;

use It_All\FormFormer\Fields\InputField;
use It_All\FormFormer\Form;
use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseColumnModel;
use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;

class DatabaseTableForm extends Form
{
    const TEXTAREA_COLS = 50;
    const TEXTAREA_ROWS = 5;

    public function __construct(DatabaseTableModel $databaseTableModel, string $formAction, string $csrfNameKey, string $csrfNameValue, string $csrfValueKey, string $csrfValueValue, string $databaseAction = 'insert', array $fieldData = null)
    {
        $this->validateDatabaseActionString($databaseAction);

        $fields = [];
//        $defaultValues = [];

        foreach ($databaseTableModel->getColumns() as $column) {
            if ($this->includeFieldForColumn($column, $databaseAction)) {
                $fields[] = $this->getFieldFromDatabaseColumn($column);
            }
        }

        if ($databaseAction == 'update') {
            // override post method
            $fields[] = FormHelper::getPutMethodField();
        }

        $fields[] = FormHelper::getCsrfNameField($csrfNameKey, $csrfNameValue);
        $fields[] = FormHelper::getCsrfValueField($csrfValueKey, $csrfValueValue);

        $fields[] = FormHelper::getSubmitField();

        parent::__construct($fields, ['method' => 'post', 'action' => $formAction, 'novalidate' => 'novalidate'], FormHelper::getGeneralError());

//        $fieldValues = ($databaseAction == 'insert') ? $defaultValues : $fieldData;
//        $this->insertValuesErrors($fieldValues);

    }

    protected function validateDatabaseActionString(string $databaseAction)
    {
        if ($databaseAction != 'insert' && $databaseAction != 'update') {
            throw new \Exception("databaseAction must be insert or update ".$databaseAction);
        }
    }

   /**
     * conditions for returning false:
     * - primary column
     */
    protected function includeFieldForColumn(DatabaseColumnModel $column): bool
    {
        if ($column->isPrimaryKey()) {
            return false;
        }

        return true;
    }

    public static function getFieldFromDatabaseColumn(
        DatabaseColumnModel $column,
        bool $addRequiredAttribute = false,
        string $labelOverride = '',
        string $inputTypeOverride = '',
        string $nameOverride = '',
        string $idOverride = ''
    )
    {
        $columnName = $column->getName();
        $columnDefaultValue = $column->getDefaultValue();

        // set label
        if ($inputTypeOverride == 'hidden') {
            $label = '';
        } elseif (strlen($labelOverride) > 0) {
            $label = $labelOverride;
        } else {
            $label = ucwords(str_replace('_', ' ', $columnName));
        }

        $field = [
            'label' => $label,
            'attributes' => [
                'name' => ($nameOverride) ? $nameOverride : $columnName,
                'id' => ($idOverride) ? $idOverride : $columnName
            ]
        ];
        if ($addRequiredAttribute) {
            $field['attributes']['required'] = 'required';
        }

        // the rest of $formField is derived in the switch statement
        switch ($column->getType()) {

            case 'text':
                $field['tag'] = 'textarea';
                $field['attributes']['cols'] = self::TEXTAREA_COLS;
                $field['attributes']['rows'] = self::TEXTAREA_ROWS;
                break;

            // input fields of various types

            case 'date':
                $field['tag'] = 'input';
                $field['attributes']['type'] = 'date';
                break;


            case 'character varying':
                $field['tag'] = 'input';
                $field['attributes']['type'] = self::getInputType($inputTypeOverride);
                // must have max defined
                $field['attributes']['maxlength'] = $column->getCharacterMaximumLength();
                // value
                if (strlen($columnDefaultValue) > 0) {
                    $field['attributes']['value'] = $columnDefaultValue;
                }

                $formField = new InputField($field['label'], FormHelper::getInputFieldAttributes($field['attributes']['name'], $field['attributes']), FormHelper::getFieldError($field['attributes']['name']));
                break;

            case 'USER-DEFINED':
                self::getSelectField($field, $column->getEnumOptions(), $columnDefaultValue);
                break;

            case 'boolean':
                $field['tag'] = 'input';
                $field['attributes']['type'] = 'checkbox';
                $field['isBoolean'] = true; // throw some column info for help with checking the box
                break;

            case 'numeric':
            case 'smallint':
            case 'bigint':
            case 'integer':
                $field['tag'] = 'input';
                $field['attributes']['type'] = 'number';
                $formField = new InputField($field['label'], FormHelper::getInputFieldAttributes($field['attributes']['name'], $field['attributes']), FormHelper::getFieldError($field['attributes']['name']));
                break;

            default:
                $field['tag'] = 'input';
                $field['attributes']['type'] = self::getInputType($inputTypeOverride);
        }



        return $formField;
    }

    public static function getInputType(string $inputTypeOverride = '')
    {
        return (strlen($inputTypeOverride) > 0) ? $inputTypeOverride : 'text';
    }
}
