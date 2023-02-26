<?php

namespace App\Rules;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\InvokableRule;

class InThePast implements InvokableRule
{
    private const DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail)
    {
        if (Carbon::createFromFormat(self::DATE_FORMAT, $value)->isFuture()) {
            $fail('Time travel alert! The ' . $attribute . ' is in the future.');
        }
    }
}
