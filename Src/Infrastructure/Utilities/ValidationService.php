<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Utilities;

/**
 * Class Validator
 * @package It_All\Spaghettify\Services
 * Inspired by https://github.com/cangelis/simple-validator
 */
class ValidationService
{
    private $errors;
    private $inputData;

    public function __construct()
    {
        $this->errors = [];
    }

    private function isFieldRequired(string $fieldName, array $rules): bool
    {
        return array_key_exists('required', $rules[$fieldName]);
    }

    private function willProcessRule(
        string $fieldName,
        $fieldValue,
        string $rule,
        $ruleContext,
        array $rules
    ): bool
    {
        // if ruleContext is false do not process
        if ($ruleContext === false) {
            return false;
        }

        // if not a required field and has empty value do not process, unless this is a confirm rule
        if (!$this->isFieldRequired($fieldName, $rules) && self::isBlankOrNull($fieldValue) && $rule != 'confirm') {
            return false;
        }

        // if there's an error on the confirm field then do not validate the confirmation
        if ($rule == 'confirm' && isset($this->errors[$this->getFieldNameToConfirm($fieldName)])) {
            return false;
        }

        return true;
    }

    // convention prepends 'confirm_' to the second field for confirmation... remove to find other field to compare with
    private function getFieldNameToConfirm(string $confirmFieldName): string
    {
        if (substr($confirmFieldName, 0, 8) != 'confirm_') {
            throw new \Exception('Invalid confirm field name '.$confirmFieldName);
        }

        return substr($confirmFieldName, 8);
    }

    // note that inputData keys without associated rules will not be validated and will not cause exceptions or errors
    public function validate(array $inputData, array $rules): bool
    {
        // save for special case where confirming two fields match, ex. password creation confirmation field
        $this->inputData = $inputData;

        foreach ($rules as $fieldName => $fieldRules) {

            // use empty string instead of null in order to use fns like strlen
            $fieldValue = isset($this->inputData[$fieldName]) ? $this->inputData[$fieldName] : '';

            foreach ($fieldRules as $rule => $ruleContext) {
                if ($this->willProcessRule($fieldName, $fieldValue, $rule, $ruleContext, $rules)) {
                    if (!$this->validateRule($fieldName, $fieldValue, $rule, $ruleContext)) {
                        break; // stop validating further rules for this field upon error
                    }
                }
            }
        }

        return empty($this->errors);
    }

    // note regex delimiter must be %.
    // note, do not use fieldName in error message, as the field label may be different and cause confusion
    private function validateRule(string $fieldName, $fieldValue, string $rule, $context = null): bool
    {
        // special case, regex ie [a-z]
        if (substr($rule, 0, 1) == '%') {
            $regex = $rule;
            $rule = 'regex';
        }

        switch ($rule) {

            case 'enum':
                if (!is_array($context)) {
                    throw new \Exception("Context must be array for enum validation");
                }
                if (!in_array($fieldValue, $context)) {
                    $this->setError($fieldName, $rule, "Invalid option selected.");
                    return false;
                }
                break;

            case 'minlength':
                if (strlen($fieldValue) < $context) {
                    $this->setError($fieldName, $rule, "Must be $context characters or more");
                    return false;
                }
                break;

            case 'maxlength':
                if (strlen($fieldValue) > $context) {
                    $this->setError($fieldName, $rule, "Must be $context characters or less");
                    return false;
                }
                break;

            case 'regex':
                if (!filter_var(
                        $fieldValue,
                        FILTER_VALIDATE_REGEXP,
                        array(
                            "options"=>array("regexp" => "$regex")
                        )
                )) {
                    $this->setError($fieldName, $context);
                    return false;
                }
                break;

            case 'alpha':
                if (!filter_var(
                    $fieldValue,
                    FILTER_VALIDATE_REGEXP,
                    array(
                        "options"=>array("regexp" => "%^[a-zA-ZW]+$%")
                    )
                )) {
                    $this->setError($fieldName, $rule, 'Only letters allowed');
                    return false;
                }
                break;

            case 'alphaspace':
                if (!filter_var(
                    $fieldValue,
                    FILTER_VALIDATE_REGEXP,
                    array(
                        "options"=>array("regexp" => "%^[a-zA-Z\s]+$%")
                    )
                )) {
                    $this->setError($fieldName, $rule, 'Only letters and spaces allowed');
                    return false;
                }
                break;

            case 'required':
                if (self::isBlankOrNull($fieldValue)) {
                    $this->setError($fieldName, $rule, $rule);
                    return false;
                }
                break;

            case 'numeric':
                if (!is_numeric($fieldValue)) {
                    $this->setError($fieldName, $rule);
                    return false;
                }
                break;

            case 'integer':
                if (!self::isInteger($fieldValue)) {
                    $this->setError($fieldName, $rule);
                    return false;
                }
                break;

            case 'positiveInteger':
                if (!self::isInteger($fieldValue) || $fieldValue <= 0) {
                    $this->setError($fieldName, 'positive integer');
                    return false;
                }
                break;

            case 'confirm':
                $fieldNameToConfirm = $this->getFieldNameToConfirm($fieldName);
                if (isset($this->errors[$fieldNameToConfirm])) {
                    break;
                }
                $fieldValueToConfirm = $this->inputData[$fieldNameToConfirm];

                if ($fieldValue !== $fieldValueToConfirm) {
                    $confirmErrorMessage = 'must match';
                    $this->setError($fieldNameToConfirm, $rule, $confirmErrorMessage);
                    $this->setError($fieldName, $rule, $confirmErrorMessage);
                    return false;
                }
                break;

            default:
                if(is_object($context) && get_class($context) == 'Closure') {
                    $refl_func = new \ReflectionFunction($context);
//                    die('here');
                    if (!$refl_func->invokeArgs([1])) {
                        $this->setError($fieldName, 'fn', 'fail');
                        return false;
                    }
                } else {
                    throw new \Exception('Unknown rule '.$rule);
                }
        }
        return true;
    }

    private function setError(string $fieldName, string $errorType, string $customMessage = null)
    {
        $this->errors[$fieldName] = (!is_null($customMessage)) ? $customMessage : "Must be $errorType";
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getError(string $fieldName): string
    {
        return (array_key_exists($fieldName, $this->errors)) ? $this->errors[$fieldName] : '';
    }


    // VALIDATION FUNCTIONS

    public static function isEmail(string $check): bool
    {
        return filter_var($check, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Check input for being an integer
     * either type int or the string equivalent of an integer
     * @param $in any type
     * note empty string returns false
     * note 0 or "0" returns true with the 0 fix implemented https://www.w3schools.com/php/filter_validate_int.asp
     * note 4.00 returns true but "4.00" returns false
     * @return bool
     */
    public static function isInteger($check): bool
    {
        return filter_var($check, FILTER_VALIDATE_INT) === 0 || filter_var($check, FILTER_VALIDATE_INT);
    }

    public static function isWholeNumber($check): bool
    {
        return self::isInteger($check) && $check >= 0;
    }

    /**
     * checks if string is blank or null
     * this can be helpful for validating required form fields
     * @param string $check
     * @return bool
     */
    public static function isBlankOrNull($check): bool
    {
        return $check === null || strlen($check) == 0;
    }

    /**
     * checks if string is blank or zero
     * this can be helpful for validating numeric/integer form fields
     * @param string $check
     * @return bool
     */
    public static function isBlankOrZero(string $check): bool
    {
        return strlen($check) == 0 || $check === '0';
    }

    /**
     * checks if string is a positive integer
     * @param string $check
     * @return bool
     */
    public static function isPositiveInteger(string $check): bool
    {
        return self::isInteger($check) && $check > 0;
    }


    public static function isNumericPositive($check): bool
    {
        return is_numeric($check) && $check > 0;
    }

    public static function isDigit($check)
    {
        return strlen($check) == 1 && self::isInteger($check);
    }

    /**
     * @param $d1
     * @param $d2 if null compare d1 to today
     * d1, d2 already verified to be isDbDate()
     * @return int
     */
    public static function dbDateCompare($d1, $d2 = null): int
    {
        // inputs 2 mysql dates and returns d1 - d2 in seconds
        if ($d2 === null) {
            $d2 = date('Y-m-d');
        }
        return convertDateMktime($d1) - convertDateMktime($d2);
    }

    /**
     * @param $dbDate already been verified to be isDbDate()
     * @return int
     */
    public static function convertDateMktime($dbDate): int
    {
        return mktime(0, 0, 0, substr($dbDate, 5, 2), substr($dbDate, 8, 2), substr($dbDate, 0, 4));
    }

    // END VALIDATION FUNCTIONS
}
