<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Request;

use Hyperf\Validation\Request\FormRequest;

final class ImagePresignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'external_drama_id' => 'required|string|max:24|regex:/^[A-Za-z0-9_-]+$/',
            'size' => 'required|integer|min:1|max:10485760',
            'mime_type' => 'required|string|in:image/jpeg,image/png,image/webp',
        ];
    }
}
