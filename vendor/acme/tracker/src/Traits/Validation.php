<?php

namespace Acme\Tracker\Traits;

/**
 * Trait Validation
 * @package Acme\Tracker\Traits
 */
trait Validation
{
    /**
     * @param array $createData
     * @return array
     */
    public function validateIssueCreateData($createData): array
    {
        if (
            !is_array($createData) ||
            count($createData) === 0) {
            return ['success' => false, 'message' => 'Submitted creation data is empty'];
        }

        $fields = ['title', 'body', 'client', 'priority', 'type'];
        $messages = [];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $createData)) {
                $messages[] = sprintf("%s is a required field, please enter a value for it", $field);
                continue;
            }

            if ((bool)$this->validateRequestValue($createData[$field]) === false) {
                $messages[] = sprintf("%s has an invalid value, please correct it and submit again", $field);
            }

            $_SESSION['form_data'][$field] = $createData[$field];
        }

        $isSuccessful = count($messages) === 0 ? true : false;

        if ((bool)$isSuccessful === true) {
            unset($_SESSION['form_data']);
        }

        return ['success' => $isSuccessful, 'messages' => $messages];
    }

    /**
     * @param string $value
     * @param int $dataType
     * @return bool
     */
    public function validateRequestValue($value = '', $dataType = FILTER_SANITIZE_STRING): bool
    {
        if (empty($value)) {
            return false;
        }

        $value = trim($value);
        $valueLength = strlen($value);

        if (
            (int)$valueLength !== (int)strlen(strip_tags($value)) ||
            (bool)filter_var($value, $dataType) === false
        ) {
            return false;
        }

        return true;
    }
}
