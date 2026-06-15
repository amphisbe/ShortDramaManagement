<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Request;

use Hyperf\Validation\Request\FormRequest;

final class MediaCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'files' => 'required|array|min:1|max:200',
            'files.*.name' => 'required|string|max:255',
            'files.*.size' => 'required|integer|min:1|max:524288000',
            'files.*.mime_type' => 'required|string|in:video/mp4,application/mp4',
            'files.*.sha256' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/i',
        ];
    }
}
