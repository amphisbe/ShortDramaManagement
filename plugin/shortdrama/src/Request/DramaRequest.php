<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Request;

use Hyperf\Validation\Request\FormRequest;

class DramaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presence = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'external_drama_id' => "{$presence}|string|max:24",
            'title' => "{$presence}|string|max:255",
            'display_author_name' => "{$presence}|string|max:100",
            'author_user_id' => "{$presence}|integer|min:1",
            'total_episodes' => "{$presence}|integer|min:1",
            'cover_url' => "{$presence}|string|max:1024",
            'vip_free' => "{$presence}|integer|in:0,1",
            'status' => "{$presence}|integer|in:0,1,2",
            'description' => 'nullable|string',
            'category' => "{$presence}|string|max:50",
            'tags' => 'nullable|string',
        ];
    }
}
