<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms;

use It_All\FormFormer\Fields\InputField;
use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseColumnModel;
use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;
use It_All\Spaghettify\Src\Infrastructure\Database\Postgres;
use It_All\Spaghettify\Src\Infrastructure\Utilities\ValitronValidatorExtension;

class FormHelper
{
    const SESSION_ERRORS_KEY = 'formErrors';
    const FIELD_ERROR_CLASS = 'formFieldError';

    public static function setGeneralError(string $errorMessage)
    {
        $_SESSION[self::SESSION_ERRORS_KEY]['generalFormError'] = $errorMessage;
    }

    public static function setFieldErrors(array $fieldErrors)
    {
        $_SESSION[self::SESSION_ERRORS_KEY] = $fieldErrors;
    }

    public static function getGeneralError(): string
    {
        return (isset($_SESSION[self::SESSION_ERRORS_KEY]['generalFormError'])) ? $_SESSION[self::SESSION_ERRORS_KEY]['generalFormError'] : '';
    }

    public static function getFieldError(string $fieldName): string
    {
        return (isset($_SESSION[self::SESSION_ERRORS_KEY][$fieldName])) ? $_SESSION[self::SESSION_ERRORS_KEY][$fieldName] : '';
    }

    public static function getFieldValue(string $fieldName): string
    {
        return (isset($_SESSION[SESSION_REQUEST_INPUT_KEY][$fieldName])) ? $_SESSION[SESSION_REQUEST_INPUT_KEY][$fieldName] : '';
    }

    public static function getInputFieldAttributes(string $fieldName = '', array $addAttributes = []): array
    {
        $attributes = [];

        // name: use field name if supplied, otherwise addAttributes['name'] will be used if supplied
        if (strlen($fieldName) > 0) {
            $attributes['name'] = $fieldName;
            unset($addAttributes['name']);
        }

        // value - does not overwrite if in addAttributes
        if (!array_key_exists('value', $addAttributes)) {
            $attributes['value'] = self::getFieldValue($fieldName);
        }

        // error class
        if (strlen(self::getFieldError($fieldName)) > 0) {
            if (array_key_exists('class', $addAttributes)) {
                $attributes['class'] = $addAttributes['class'] . " " . self::FIELD_ERROR_CLASS;
                unset($addAttributes['name']);
            } else {
                $attributes['class'] = self::FIELD_ERROR_CLASS;
            }
        }

        return array_merge($attributes, $addAttributes);
    }

    public static function getCsrfNameField(string $csrfNameKey, string $csrfNameValue)
    {
        return new InputField('', ['type' => 'hidden', 'name' => $csrfNameKey, 'value' => $csrfNameValue]);
    }

    public static function getCsrfValueField(string $csrfValueKey, string $csrfValueValue)
    {
        return new InputField('', ['type' => 'hidden', 'name' => $csrfValueKey, 'value' => $csrfValueValue]);
    }

    public static function getPutMethodField()
    {
        return new InputField('', ['type' => 'hidden', 'name' => '_METHOD', 'value' => 'PUT']);
    }

    public static function getSubmitField(string $value = 'Enter')
    {
        return new InputField('', ['type' => 'submit', 'name' => 'submit', 'value' => $value]);
    }

    public static function unsetSessionVars()
    {
        unset($_SESSION[SESSION_REQUEST_INPUT_KEY]);
        unset($_SESSION[self::SESSION_ERRORS_KEY]);
    }

    public static function getDatabaseColumnValidation(DatabaseColumnModel $databaseColumnModel): array
    {
        $columnValidation = [];

        if (!$databaseColumnModel->getIsNullable()) {
            $columnValidation[] = 'required';
        }

        if ($databaseColumnModel->getCharacterMaximumLength() != null) {
            $columnValidation[] = ['lengthMax', $databaseColumnModel->getCharacterMaximumLength()];
        }

        if ($databaseColumnModel->isNumericType()) {
            if ($databaseColumnModel->isIntegerType()) {
                $columnValidation[] = 'integer';
                switch ($databaseColumnModel->getType()) {
                    case 'smallint':
                        $columnValidation[] = ['min', Postgres::SMALLINT_MIN];
                        $columnValidation[] = ['max', Postgres::SMALLINT_MAX];
                        break;

                    case 'integer':
                        $columnValidation[] = ['min', Postgres::INTEGER_MIN];
                        $columnValidation[] = ['max', Postgres::INTEGER_MAX];
                        break;

                    case 'bigint':
                        $columnValidation[] = ['min', Postgres::BIGINT_MIN];
                        $columnValidation[] = ['max', Postgres::BIGINT_MAX];
                        break;

                    case 'smallserial':
                        $columnValidation[] = ['min', Postgres::SMALLSERIAL_MIN];
                        $columnValidation[] = ['max', Postgres::SMALLSERIAL_MAX];
                        break;

                    case 'serial':
                        $columnValidation[] = ['min', Postgres::SERIAL_MIN];
                        $columnValidation[] = ['max', Postgres::SERIAL_MAX];
                        break;

                    case 'bigserial':
                        $columnValidation[] = ['min', Postgres::BIGSERIAL_MIN];
                        $columnValidation[] = ['max', Postgres::BIGSERIAL_MAX];
                        break;

                    default:
                        throw new \Exception("Undefined postgres integer type ".$column->getType());
                }
            } else {
                $columnValidation[] = 'numeric';
            }
        }

        return $columnValidation;
    }

    public static function getDatabaseTableValidation(DatabaseTableModel $databaseTableModel): array
    {
        $validation = [];
        foreach ($databaseTableModel->getColumns() as $column) {
            // primary key does not have validation
            $columnValidation = ($column->isPrimaryKey()) ? [] : self::getDatabaseColumnValidation($column);
            if (count($columnValidation) > 0) {
                $validation[$column->getName()] = $columnValidation;
            }
        }

        return $validation;
    }

    public static function getDatabaseTableValidationFields(DatabaseTableModel $databaseTableModel): array
    {
        $fields = [];
        foreach ($databaseTableModel->getColumns() as $column) {
            // primary key does not have validation
            if (!($column->isPrimaryKey())) {
                $fields[] = $column->getName();
            }
        }

        return $fields;
    }

    private static function setUniqueDatabaseColumnValidation(ValitronValidatorExtension $v, DatabaseTableModel $databaseTableModel) {
        foreach ($databaseTableModel->getUniqueColumns() as $databaseColumnModel) {
            $v->rule(function($field, $value, $params, $fields) {
                return $databaseColumnModel->recordExistsForValue($value);
            }, $databaseColumnModel->getName())->message("{field} must be unique");
        }
    }

    public static function setDatabaseTableValidation(ValitronValidatorExtension $v, DatabaseTableModel $databaseTableModel)
    {
        $v->mapFieldsRules(self::getDatabaseTableValidation($databaseTableModel));
        // unique rules is set after other rules
        self::setUniqueDatabaseColumnValidation($v, $databaseTableModel);
    }
}
