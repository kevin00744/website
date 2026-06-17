<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'message'  => 'nullable|string',
            'interest' => 'nullable|string|max:255',
            'note'     => 'nullable|string',
        ]);

        ContactSubmission::create($data);

        return response()->json(['message' => '感謝您的訊息，我們會盡快與您聯繫。']);
    }
}
