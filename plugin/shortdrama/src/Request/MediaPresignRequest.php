<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Request;

use Hyperf\Validation\Request\FormRequest;

final class MediaPresignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'size' => 'required|integer|min:1|max:524288000',
            'mime_type' => 'required|string|in:video/mp4,application/mp4',
            'sha256' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/i',
        ];
    }
}
