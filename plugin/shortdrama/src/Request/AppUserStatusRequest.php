<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Request;

use Hyperf\Validation\Request\FormRequest;

final class AppUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['status' => 'required|integer|in:0,1'];
    }
}
