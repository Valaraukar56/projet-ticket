<?php

namespace App\Rules;

use App\Services\ProfanityService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoProfanity implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ((new ProfanityService())->contains((string) $value)) {
            $fail(__('messages.profanity_detected'));
        }
    }
}
