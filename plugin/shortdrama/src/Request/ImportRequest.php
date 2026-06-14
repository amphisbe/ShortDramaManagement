<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Request;

use Hyperf\Validation\Request\FormRequest;

final class ImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file',
            'type' => 'required|string|in:drama,episode',
        ];
    }
}
