<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms;

use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseColumnModel;
use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;

class DatabaseTableForm extends Form
{
    const TEXTAREA_COLS = 50;
    const TEXTAREA_ROWS = 5;

    public function __construct(DatabaseTableModel $databaseTableModel, string $databaseAction = 'insert', array $fieldData = null)
    {
        // todo, decide whether to use commented code below (and remove code in model), or leave as is

//        $this->validateDatabaseActionString($databaseAction);
//
//        $fields = [];
//        $defaultValues = [];
//
//        foreach ($databaseTableModel->getColumns() as $column) {
//            if ($this->includeFieldForColumn($column, $databaseAction)) {
//                $fields[$column->getName()] = $this->getFieldFromDatabaseColumn($column);
//                $defaultValues[$column->getName()] = $column->getDefaultValue();
//            }
//        }
//
//        if ($databaseAction == 'update') {
//            // override post method
//            $fields['_METHOD'] = $this->getPutMethodField();
//        }
//
//        $fields['submit'] = $this->getSubmitField();
//        parent::__construct($fields);
//
//        $fieldValues = ($databaseAction == 'insert') ? $defaultValues : $fieldData;
//        $this->insertValuesErrors($fieldValues);

        parent::__construct($databaseTableModel->getFormFields($databaseAction, $fieldData));

        $fieldValues = ($databaseAction == 'insert') ? $databaseTableModel->getDefaultFormFieldValues() : $fieldData;
        $this->insertValuesErrors($fieldValues);
    }

    protected function validateDatabaseActionString(string $databaseAction)
    {
        if ($databaseAction != 'insert' && $databaseAction != 'update') {
            throw new \Exception("databaseAction must be insert or update ".$databaseAction);
        }
    }

   /**
     * conditions for returning false:
     * - primary column and skip
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
        string $labelOverride = null,
        string $inputTypeOverride = null,
        array $validationOverride = null,
        string $nameOverride = null,
        string $idOverride = null,
        bool $persist = true
    ): array
    {
        $columnName = $column->getName();
        $columnDefaultValue = $column->getDefaultValue();

        // set label
        if ($inputTypeOverride == 'hidden') {
            $label = null;
        } elseif ($labelOverride !== null) {
            $label = $labelOverride;
        } else {
            $label = ucwords(str_replace('_', ' ', $columnName));
        }

        $formField = [
            'label' => $label,
            'attributes' => [
                'name' => ($nameOverride) ? $nameOverride : $columnName,
                'id' => ($idOverride) ? $idOverride : $columnName
            ],
            'validation' => (is_array($validationOverride)) ? $validationOverride : $column->getValidation()
        ];

        // the rest of $formField is derived in the switch statement
        switch ($column->getType()) {

            case 'text':
                $formField['tag'] = 'textarea';
                $formField['attributes']['cols'] = self::TEXTAREA_COLS;
                $formField['attributes']['rows'] = self::TEXTAREA_ROWS;
                break;

            // input fields of various types

            case 'date':
                $formField['tag'] = 'input';
                $formField['attributes']['type'] = 'date';
                break;


            case 'character varying':
                $formField['tag'] = 'input';
                $formField['attributes']['type'] = self::getInputType($inputTypeOverride);
                // must have max defined
                $formField['attributes']['maxlength'] = $column->getCharacterMaximumLength();
                $formField['validation']['maxlength'] = $column->getCharacterMaximumLength();
                break;

            case 'USER-DEFINED':
                self::getSelectField($formField, $column->getEnumOptions(), $columnDefaultValue);
                break;

            case 'boolean':
                $formField['tag'] = 'input';
                $formField['attributes']['type'] = 'checkbox';
                $formField['validation'] = [];
                $formField['isBoolean'] = true; // throw some column info for help with checking the box
                break;

            case 'numeric':
            case 'smallint':
            case 'bigint':
            case 'integer':
                $formField['tag'] = 'input';
                $formField['attributes']['type'] = 'number';
                break;

            default:
                $formField['tag'] = 'input';
                $formField['attributes']['type'] = self::getInputType($inputTypeOverride);
        }

        $formField['persist'] = $persist;

        // todo - look into field class for flexibility
//        $field = new Field($formField['tag'], )
        return $formField;
    }

    public static function getInputType(string $inputTypeOverride = null)
    {
        return ($inputTypeOverride) ? $inputTypeOverride : 'text';
    }
}
