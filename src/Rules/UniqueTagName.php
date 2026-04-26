<?php

namespace Tapp\FilamentLibrary\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;
use Tapp\FilamentLibrary\Models\LibraryItemTag;

class UniqueTagName implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $slug = Str::slug($value);
        $existingTag = LibraryItemTag::where('slug', $slug)->first();

        if ($existingTag) {
            $fail('A tag with this name already exists.');
        }
    }
}
