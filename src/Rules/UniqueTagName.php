<?php

namespace Tapp\FilamentLibrary\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Tapp\FilamentLibrary\Models\LibraryItemTag;

class UniqueTagName implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $slug = \Illuminate\Support\Str::slug($value);
        $existingTag = LibraryItemTag::where('slug', $slug)->first();

        if ($existingTag) {
            $fail('A tag with this name already exists.');
        }
    }
}
