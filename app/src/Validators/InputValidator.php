<?php
namespace App\Validators;

use Psr\Http\Message\ServerRequestInterface as Request;

class InputValidator 
{
    protected array $values = [];
    protected array $errors = [];

    public function validate(Request $request, array $rules)
    {
        $data = $request->getParsedBody();
        $this->values = $data ?? [];
        $this->errors = [];

        foreach ($rules as $field => $constraints) {
            $value = $data[$field] ?? "";
            if (isset($constraints["required"]) && trim($value) === "") {
                $this->errors[$field] = $constraints["required"]["message"] ?? "The {$field} field is required";
                continue;
            }
            if (isset($constraints["pattern"]["match"]) && !preg_match($constraints["pattern"]["match"], $value)) {
                $this->errors[$field] = $constraints["required"]["message"] ?? "The {$field} field format is invalid";
                continue;
            }
            if (isset($constraints["minLength"]["value"]) && mb_strlen($value) < $constraints["minLength"]["value"]) {
                $this->errors[$field] = $constraints["minLength"]["message"] ?? "The {$field} field must be at least {$constraints["minLength"]["value"]} characters.";
                continue;
            }
            if (isset($constraints["maxLength"]["value"]) && mb_strlen($value) < $constraints["maxLength"]["value"]) {
                $this->errors[$field] = $constraints["maxLength"]["message"] ?? "The {$field} field must not exceed {$constraints["maxLength"]["value"]} characters.";
                continue;
            }
        }
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function values() 
    {
        return $this->values;
    }

    public function errors() 
    {
        return $this->errors;
    }
}
