<?php

namespace FormControl;

use App\Model\UserManager;
use App\Service\UserService;

abstract class AbstractFormControl
{
    const MAX_CHARACTERS_NAME       = 45;
    const MAX_CHARACTERS_PSEUDO     = 26;
    const MAX_CHARACTERS_OTHER_NAME = 35;
    const MAX_CHARACTERS_EMAIL      = 80;
    const MIN_CHARACTERS_PASSWORD   = 8;
    const MAX_CHARACTERS_PASSWORD   = 45;
    const MIN_CHARACTERS_DESC       = 30;
    const MAX_CHARACTERS_DESC       = 200;
    const MAX_CHARACTERS_URL        = 2083;

    /**
     * <p>Store errors.</p>
     * @var array
     */
    protected $errors = [];

    /**
     * <p>AbstractFormControl constructor.</p>
     * @param array $data
     * <p>Take in entry <b>$_POST</b>.</p>
     */
    abstract public function __construct(array $data);

    /**
     * <p>Return all errors in the property $errors of the actual class.</p>
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * <p>Return the requested property</p>
     * @param string $propertyName
     * <p>Name of the property</p>
     * @return mixed
     */
    public function getProperty(string $propertyName)
    {
        $result = null;
        if (isset($this->$propertyName)) {
            $result = $this->$propertyName;
        }
        return $result;
    }

    /**
     * <p>Return all properties of a object.</p>
     * @return array
     */
    public function getAllProperty(): array
    {
        return get_object_vars($this);
    }

    /**
     * <p>Verify last name, first name, city, country.</p>
     * @param string $value
     * <p>$value is a string retrieved from $data['key']</p>
     * @param string $propertyName
     * <p>$propertyName is the name of the property of type 'name' created in the child class. <b>In camelCase</b></p>
     * @param string $key
     * <p>$key is the complement of the error key if is several words used a <b>underscore</b> as separator,
     * in <b>snake_case</b>.<br>
     * Example : city, last_name.<br>
     * Also used to complete the error message for empty values, underscore is replaced with whitespace.</p>
     * @return AbstractFormControl
     */
    protected function verifyName(string $value, string $propertyName, string $key): AbstractFormControl
    {
        // Set the property in object.
        $this->$propertyName = ucwords(mb_strtolower(trim(htmlspecialchars($value))));
        // Replace underscore by whitespace.
        $word = str_replace('_', ' ', $key);
        /**
         * Check if the input value is empty.
         * Check if the number of characters in the input is greater than 45.
         * Check if the input is composed only of letter, number, common accent and whitespace.
         */
        if (empty($value)) {
            $this->errors['error_' . $key] = 'Please enter a ' . $word . ', thank you.';
        } elseif (strlen($value) > self::MAX_CHARACTERS_NAME) {
            $this->errors['error_' . $key] = 'Must be a maximum of 45 characters. Current : ' . strlen($value);
        } elseif (preg_match(
            '/[^-\'A-Za-záàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ\s]/',
            $value
        )) {
            $this->errors['error_' . $key] = 'Special characters are prohibited.';
        }

        return $this;
    }

    /**
     * <p>Verify pseudo, pseudo github.</p>
     * @param string $value
     * <p>$value is a string retrieved from $data['key']</p>
     * @param string $propertyName
     * <p>$propertyName is the name of the property of type 'name' created in the child class. <b>In camelCase</b></p>
     * @param string $key
     * <p>$key is the complement of the error key if is several words used a <b>underscore</b> as separator,
     * in <b>snake_case</b>.<br>
     * Example : pseudo, pseudo_github.<br>
     * Also used to complete the error message for empty values, underscore is replaced with whitespace.</p>
     * @return AbstractFormControl
     */
    protected function verifyPseudo(string $value, string $propertyName, string $key): AbstractFormControl
    {
        // Set the property in object.
        $this->$propertyName = trim(htmlspecialchars($value));
        // Replace underscore by whitespace.
        $word = str_replace('_', ' ', $key);
        /**
         * Check if the input value is empty.
         * Check if the number of characters in the input is greater than 26.
         * Check if the input is composed only of letter, number, common accent and whitespace.
         */
        if (empty($value)) {
            $this->errors['error_' . $key] = 'Please enter a ' . $word . ', thank you.';
        } elseif (strlen($value) > self::MAX_CHARACTERS_PSEUDO) {
            $this->errors['error_' . $key] = 'Must be a maximum of 26 characters. Current : ' . strlen($value);
        } elseif (!preg_match(
            '/^([a-zA-ZáàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ0-9-_]{2,26})$/',
            $value
        )
        ) {
            $this->errors['error_' . $key] = 'Special characters are prohibited.';
        }

        return $this;
    }

    /**
     * <p>Verify type, contest name, challenge name.</p>
     * @param string $value
     * <p>$value is a string retrieved from $data['key']</p>
     * @param string $propertyName
     * <p>$propertyName is the name of the property of type 'name' created in the child class. <b>In camelCase</b></p>
     * @param string $key
     * <p>$key is the complement of the error key if is several words used a <b>underscore</b> as separator,
     * in <b>snake_case</b>.<br>
     * Example : type, challenge_name, contest_name.<br>
     * Also used to complete the error message for empty values, underscore is replaced with whitespace.</p>
     * @return AbstractFormControl
     */
    protected function verifyOtherName(string $value, string $propertyName, string $key): AbstractFormControl
    {
        // Set the property in object.
        $this->$propertyName = trim(htmlspecialchars($value));
        // Replace underscore by whitespace.
        $word = str_replace('_', ' ', $key);
        /**
         * Check if the input value is empty.
         * Check if the number of characters in the input is greater than 26.
         * Check if the input is composed only of letter, number, common accent and whitespace.
         */
        if (empty($value)) {
            $this->errors['error_' . $key] = 'Please enter a ' . $word . ', thank you.';
        } elseif (strlen($value) > self::MAX_CHARACTERS_OTHER_NAME) {
            $this->errors['error_' . $key] = 'Must be a maximum of 35 characters. Current : ' . strlen($value);
        }

        return $this;
    }

    /**
     * <p>Verify email.</p>
     * @param string $value
     * <p>$value is a string retrieved from $data['key']</p>
     * @param string $propertyName <p>[optional] <b>Default : email</b>.<br>
     * <p>$propertyName is the name of the property of type 'email' created in the child class.</p>
     * @return AbstractFormControl
     */
    protected function verifyEmail(string $value, string $propertyName = 'email'): AbstractFormControl
    {
        // Set the property in object.
        $this->$propertyName = strtolower(trim(htmlspecialchars($value)));
        /**
         * Check if the input value is empty.
         * Check if the number of characters in the input is greater than 80.
         * Check if the email is in a format valid.
         */
        if (empty($value)) {
            $this->errors['error_email'] = 'Please enter a valid email, thank you.';
        } elseif (strlen($value) > self::MAX_CHARACTERS_EMAIL) {
            $this->errors['error_email'] = 'Must be a maximum of 80 characters. Current : ' . strlen($value);
        } elseif (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors['error_email'] = 'Please enter your email in a valid format. Example: john.doe@exemple.fr';
        } elseif (UserService::mailAlreadyExists($value)) {
            $this->errors['error_email'] = 'This email already exists';
        }

        return $this;
    }

    /**
     * <p>Verify password.</p>
     * @param string $value
     * <p>$value is a string retrieved from $data['key']</p>
     * @param string $propertyName <p>[optional] <b>Default : password</b>.<br>
     * <p>$propertyName is the name of the property of type 'password' created in the child class.</p>
     * @return AbstractFormControl
     */
    protected function verifyPassword(string $value, string $propertyName = 'password'): AbstractFormControl
    {
        // Set the property in object.
        $this->$propertyName = trim(htmlspecialchars($value));
        /**
         * Check if the input value is empty.
         * Check if the number of characters in the input is greater than 8 and lower than 45.
         * Check if the input is composed of one lower case, one upper case, one number and one special character.
         */
        $regex = '/^(?=.*[0-9])(?=.*[!@#$%^&*{}_])(?=.*[a-z])(?=.*[A-Z])[a-zA-Z0-9!@#$%^&*{}_]{8,}$/';
        if (empty($value)) {
            $this->errors['error_' . $propertyName] = 'Please enter a  password, thank you.';
        } elseif (strlen($value) < self::MIN_CHARACTERS_PASSWORD || strlen($value) > self::MAX_CHARACTERS_PASSWORD) {
            $errorMessage = 'Must be a minimum of 8 characters and a maximum of 45. Current : ';
            $this->errors['error_' . $propertyName] = $errorMessage . strlen($value);
        } elseif (!preg_match($regex, $value)) {
            $errorMessage = 'Your password must match with at least 1 uppercase, 1 number and 1 special character';
            $this->errors['error_password'] = $errorMessage;
        }

        return $this;
    }

    /**
     * <p>Verify Description.</p>
     * @param string $value
     * <p>$value is a string retrieved from $data['key']</p>
     * @param string $propertyName <p>[optional] <b>Default : description</b>.<br>
     * <p>$propertyName is the name of the property of type 'text' created in the child class.</p>
     * @return AbstractFormControl
     */
    protected function verifyDescription(string $value, string $propertyName = 'description'): AbstractFormControl
    {
        // Set the property in object.
        $this->$propertyName = trim(htmlspecialchars($value));
        /**
         * Check if the input value is empty.
         * Check if the number of characters in the input is between 30 and 500 characters.
         * Check if the email is in a valid format.
         */
        if (empty($value)) {
            $this->errors['error_' . $propertyName] = 'Please enter a description. (Max: 200 characters)';
        } elseif (strlen($value) < self::MIN_CHARACTERS_DESC || strlen($value) > self::MAX_CHARACTERS_DESC) {
            $errorMessage = 'Your description must be between 30 characters and 500 characters. Current: ';
            $this->errors['error_' . $propertyName] = $errorMessage . strlen($value);
        }

        return $this;
    }

    /**
     * <p>Verify select or input type radio.</p>
     * @param int $value
     * <p>$value is a string retrieved from $data['key']</p>
     * @param string $propertyName
     * <p>$propertyName is the name of the property of type 'integer' created in the child class.</p>
     * @return AbstractFormControl
     */
    protected function verifySelected(int $value, string $propertyName): AbstractFormControl
    {
        // Set the property in object.
        $this->$propertyName = $value;
        // Check if the input value is empty and if the input is not a integer
        if (empty($value) || !is_int($value)) {
            $this->errors['error_' . $propertyName] = 'Please select an answer.';
        }

        return $this;
    }

    /**
     * <p>Verify select or input type radio.</p>
     * @param int $value
     * <p>$value is a string retrieved from $data['key']</p>
     * @param string $propertyName
     * <p>$propertyName is the name of the property of type 'integer' created in the child class.</p>
     * @return AbstractFormControl
     */
    protected function verifyInteger(int $value, string $propertyName): AbstractFormControl
    {
        // Set the property in object.
        $this->$propertyName = $value;
        // Check if the input value is empty and if the input is not a integer
        if (empty($value) || !is_int($value)) {
            $this->errors['error_' . $propertyName] = 'Please enter a valid number.';
        }

        return $this;
    }

    /**
     * <p>Verify url.</p>
     * @param string $value
     * <p>$value is a string retrieved from $data['key']</p>
     * @param string $propertyName <p>[optional] <b>Default : url</b>.<br>
     * <p>$propertyName is the name of the property of type 'text' created in the child class.</p>
     * @return AbstractFormControl
     */
    protected function verifyUrl(string $value, string $propertyName = 'url'): AbstractFormControl
    {
        // Set the property in object.
        $this->$propertyName = trim(htmlspecialchars($value));
        /**
         * Check if the input value is empty.
         * Check if the number of characters in the input is greater than 2083 characters
         * and check if the url is in a valid format.
         */
        if (empty($value)) {
            $this->errors['error_' . $propertyName] = 'Please enter a link.';
        } elseif (strlen($value) > self::MAX_CHARACTERS_URL || filter_var($value, FILTER_VALIDATE_URL) === false) {
            $this->errors['error_' . $propertyName] = 'Please enter a valid url';
        }

        return $this;
    }

    /**
     * <p>Verify flag.</p>
     * @param string $value
     * <p>$value is a string retrieved from $data['key']</p>
     * @param string $propertyName <p>[optional] <b>Default : flag</b>.<br>
     * <p>$propertyName is the name of the property of type 'text' created in the child class.</p>
     * @return AbstractFormControl
     */
    protected function verifyFlag(string $value, string $propertyName = 'flag'): AbstractFormControl
    {
        // Set the property in object.
        $this->$propertyName = trim(htmlspecialchars($value));
        /**
         * Check if the input value is empty.
         * Check if the number of characters in the input is greater than 2083 characters
         * and check if the url is in a valid format.
         */
        if (empty($value)) {
            $this->errors['error_' . $propertyName] = 'Please enter a flag.';
        } elseif (strlen($value) > self::MAX_CHARACTERS_URL) {
            $this->errors['error_' . $propertyName] = 'Please enter a valid flag';
        }

        return $this;
    }
}
