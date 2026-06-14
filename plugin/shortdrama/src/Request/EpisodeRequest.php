<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Request;

use Hyperf\Validation\Request\FormRequest;

class EpisodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presence = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'drama_id' => "{$presence}|integer|min:1",
            'external_video_id' => "{$presence}|string|max:24",
            'episode_no' => "{$presence}|integer|min:1",
            'title' => "{$presence}|string|max:500",
            'play_url' => "{$presence}|string|max:1024",
            'poster_url' => "{$presence}|string|max:1024",
            'duration_seconds' => "{$presence}|integer|min:0",
            'sort_order' => "{$presence}|integer|min:0",
            'status' => "{$presence}|integer|in:0,1",
            'display_nickname' => "{$presence}|string|max:255",
            'loop' => "{$presence}|integer|in:0,1",
            'play_ing' => "{$presence}|integer|in:0,1",
            'muted' => "{$presence}|integer|in:0,1",
            'is_playing' => "{$presence}|integer|in:0,1",
            'show_title_arrow' => "{$presence}|integer|in:0,1",
            'show_look_all_btn' => "{$presence}|integer|in:0,1",
            'look_all_btn_text' => "{$presence}|string|max:255",
            'show_bottom_area' => "{$presence}|integer|in:0,1",
            'bottom_area_btn_text' => "{$presence}|string|max:255",
            'tool_info_json' => "{$presence}|string",
        ];
    }
}
