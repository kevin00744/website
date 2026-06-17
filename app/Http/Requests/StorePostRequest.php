<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $postId = $this->route('post')?->id;

        return [
            'title'          => 'required|string|max:255',
            'slug'           => ['nullable', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($postId)],
            'excerpt'        => 'nullable|string|max:500',
            'content'        => 'required|string',
            'content_json'   => 'nullable|array',
            'status'         => ['required', Rule::in(['draft', 'review', 'published', 'archived'])],
            'type'           => ['required', Rule::in(['post', 'page'])],
            'category_id'    => 'nullable|exists:categories,id',
            'featured_image_id' => 'nullable|exists:media,id',
            'tags'           => 'nullable|array',
            'tags.*'         => 'exists:tags,id',
            'is_featured'    => 'boolean',
            'allow_comments' => 'boolean',
            'seo_title'      => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
            'published_at'   => 'nullable|date',
        ];
    }
}
