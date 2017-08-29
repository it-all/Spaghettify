<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms;

use It_All\FormFormer\Fields\InputField;
use It_All\FormFormer\Form;
use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseColumnModel;
use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;
use It_All\Spaghettify\Src\Infrastructure\Database\Postgres;

class DatabaseTableForm extends Form
{
    const TEXTAREA_COLS = 50;
    const TEXTAREA_ROWS = 5;

    public function __construct(DatabaseTableModel $databaseTableModel, string $formAction, string $csrfNameKey, string $csrfNameValue, string $csrfValueKey, string $csrfValueValue, string $databaseAction = 'insert', array $fieldData = null)
    {
        $this->validateDatabaseActionString($databaseAction);

        $fields = [];

        foreach ($databaseTableModel->getColumns() as $column) {
            if ($this->includeFieldForColumn($column, $databaseAction)) {
                $columnValue = (isset($fieldData[$column->getName()])) ? $fieldData[$column->getName()] : null;
                $fields[] = $this->getFieldFromDatabaseColumn($column, null, $columnValue);
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
        bool $isRequiredOverride = null,
        string $valueOverride = null,
        string $labelOverride = '',
        string $inputTypeOverride = '',
        string $nameOverride = '',
        string $idOverride = ''
    )
    {
        $columnValidation = FormHelper::getDatabaseColumnValidation($column);
        $columnName = $column->getName();
        $value = ($valueOverride !== null) ? $valueOverride : $column->getDefaultValue();

        // set label
        if ($inputTypeOverride == 'hidden') {
            $label = '';
        } elseif (strlen($labelOverride) > 0) {
            $label = $labelOverride;
        } else {
            $label = ucwords(str_replace('_', ' ', $columnName));
        }

        $fieldInfo = [
            'label' => $label,
            'attributes' => [
                'name' => ($nameOverride) ? $nameOverride : $columnName,
                'id' => ($idOverride) ? $idOverride : $columnName
            ]
        ];

        if ( ($isRequiredOverride !== null && $isRequiredOverride) || FormHelper::getDatabaseColumnValidationValue($column, 'required')) {
            $fieldInfo['attributes']['required'] = 'required';
        }

        // the rest of $formField is derived below based on postgres column type

        if ($column->isNumericType()) {

            $fieldInfo['tag'] = 'input';
            $fieldInfo['attributes']['type'] = 'number';

            if ($column->isIntegerType()) {
                switch ($column->getType()) {
                    case 'smallint':
                        $fieldInfo['attributes']['min'] = FormHelper::getDatabaseColumnValidationValue($column,'min');
                        $fieldInfo['attributes']['max'] = FormHelper::getDatabaseColumnValidationValue($column,'max');
                        break;

                    case 'integer':
                        $fieldInfo['attributes']['min'] = FormHelper::getDatabaseColumnValidationValue($column,'min');
                        $fieldInfo['attributes']['max'] = FormHelper::getDatabaseColumnValidationValue($column,'max');
                        break;

                    case 'bigint':
                        $fieldInfo['attributes']['min'] = FormHelper::getDatabaseColumnValidationValue($column,'min');
                        $fieldInfo['attributes']['max'] = FormHelper::getDatabaseColumnValidationValue($column,'max');
                        break;

                    case 'smallserial':
                        $fieldInfo['attributes']['min'] = FormHelper::getDatabaseColumnValidationValue($column,'min');
                        $fieldInfo['attributes']['max'] = FormHelper::getDatabaseColumnValidationValue($column,'max');
                        break;

                    case 'serial':
                        $fieldInfo['attributes']['min'] = FormHelper::getDatabaseColumnValidationValue($column,'min');
                        $fieldInfo['attributes']['max'] = FormHelper::getDatabaseColumnValidationValue($column,'max');
                        break;

                    case 'bigserial':
                        $fieldInfo['attributes']['min'] = FormHelper::getDatabaseColumnValidationValue($column,'min');
                        $fieldInfo['attributes']['max'] = FormHelper::getDatabaseColumnValidationValue($column,'max');
                        break;

                    default:
                        throw new \Exception("Undefined postgres integer type ".$column->getType());

                }
            } else {
                // default for the remaining numeric fields.
                $fieldInfo['attributes']['step'] = '.01';
            }

            // value
            if (strlen($value) > 0) {
                $fieldInfo['attributes']['value'] = $value;
            }
            $formField = new InputField($fieldInfo['label'], FormHelper::getInputFieldAttributes($fieldInfo['attributes']['name'], $fieldInfo['attributes']), FormHelper::getFieldError($fieldInfo['attributes']['name']));

        } else {

            switch ($column->getType()) {

                case 'text':
                    $fieldInfo['tag'] = 'textarea';
                    $fieldInfo['attributes']['cols'] = self::TEXTAREA_COLS;
                    $fieldInfo['attributes']['rows'] = self::TEXTAREA_ROWS;
                    break;

                // input fields of various types

                case 'date':
                    $fieldInfo['tag'] = 'input';
                    $fieldInfo['attributes']['type'] = 'date';
                    break;


                case 'character varying':
                    $fieldInfo['tag'] = 'input';
                    $fieldInfo['attributes']['type'] = self::getInputType($inputTypeOverride);
                    // must have max defined
                    $fieldInfo['attributes']['maxlength'] = $column->getCharacterMaximumLength();
                    // value
                    if (strlen($value) > 0) {
                        $fieldInfo['attributes']['value'] = $value;
                    }

                    $formField = new InputField($fieldInfo['label'], FormHelper::getInputFieldAttributes($fieldInfo['attributes']['name'], $fieldInfo['attributes']), FormHelper::getFieldError($fieldInfo['attributes']['name']));
                    break;

                case 'USER-DEFINED':
                    self::getSelectField($fieldInfo, $column->getEnumOptions(), $value);
                    break;

                case 'boolean':
                    $fieldInfo['tag'] = 'input';
                    $fieldInfo['attributes']['type'] = 'checkbox';
                    $fieldInfo['isBoolean'] = true; // throw some column info for help with checking the box
                    break;

                default:
                    throw new \Exception('Undefined form field for postgres column type '.$column->getType());
            }
        }

        return $formField;
    }

    public static function getInputType(string $inputTypeOverride = '')
    {
        return (strlen($inputTypeOverride) > 0) ? $inputTypeOverride : 'text';
    }
}
