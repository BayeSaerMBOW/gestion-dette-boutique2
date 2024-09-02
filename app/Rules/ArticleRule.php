<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ArticleRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //
    }
    public function rules()
    {
        return [
            'libelle' => 'required|string|max:255',
            'prix' => 'required|numeric|min:0',
            'quantite_de_stock' => 'required|integer|min:0',
        ];
    }
}
