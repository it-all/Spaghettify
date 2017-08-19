<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms;

use It_All\FormFormer\Fields\InputField;
use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseColumnModel;
use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;

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

        if ($databaseColumnModel->isPrimaryKey()) {
            return $columnValidation; // no validation for primary key as it is not a form field
        }

        if (!$databaseColumnModel->getIsNullable()) {
            $columnValidation[] = 'required';
        }

        if ($databaseColumnModel->getCharacterMaximumLength() != null) {
            $columnValidation[] = 'max_length('.$databaseColumnModel->getCharacterMaximumLength().')';
        }

        if ($databaseColumnModel->getIsUnique()) {
//            $columnValidation['unique'] = function($input) {
//                return 1 == 2;
//            };
            $compare = 'abc';
            $columnValidation['unique'] = function($input) use ($databaseColumnModel) {
                return !$databaseColumnModel->recordExistsForValue($input);
//                return $compare != $input;
            };
        }

        return $columnValidation;
    }

    public static function getDatabaseTableValidation(DatabaseTableModel $databaseTableModel): array
    {
        $validation = [];
        foreach ($databaseTableModel->getColumns() as $column) {
            $columnValidation = self::getDatabaseColumnValidation($column);
            if (count($columnValidation) > 0) {
                $validation[$column->getName()] = $columnValidation;
            }
        }
        return $validation;
    }
}
