<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;

class TagApiController extends Controller
{
    public function index()
    {
        return response()->json(
            Tag::withCount('posts')->orderBy('name')->get()
        );
    }
}
