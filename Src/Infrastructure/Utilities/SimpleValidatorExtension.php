<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Utilities;

use SimpleValidator\Validator;

class SimpleValidatorExtension extends Validator
{
    // alters to return array of field => errorMsg with only the first error per field
    public function getErrors($lang = null): array
    {
        $errors = [];

        foreach (parent::getErrors($lang) as $errorMessage) {
            echo $errorMessage;
            $fieldName = explode(" field ", $errorMessage)[0];
            if (!array_key_exists($fieldName, $errors)) {
                $errors[$fieldName] = explode(" field is ", $errorMessage)[1];
            }
        }

        return $errors;
    }

    protected function getErrorFilePath($lang)
    {
        return APP_ROOT . 'Infrastructure/Utilities/SimpleValidatorErrors.php';
    }
}
