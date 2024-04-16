<?php

declare(strict_types = 1);

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
        $date = Carbon::createFromFormat(self::DATE_FORMAT, $value);
        if (!$date instanceof Carbon) {
            $fail('Invalid datetime format, it does not match ISO8601-ZULU: ' . self::DATE_FORMAT);
            return;
        }
        if ($date->isFuture()) {
            $fail('Time travel alert! The ' . $attribute . ' is in the future.');
        }
    }
}
