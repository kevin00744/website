<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Contacts/Index', [
            'submissions' => ContactSubmission::orderByDesc('created_at')->paginate(15),
        ]);
    }

    public function update(Request $request, ContactSubmission $contact)
    {
        $contact->update($request->validate(['is_read' => 'required|boolean']));
        return back();
    }

    public function destroy(ContactSubmission $contact)
    {
        $contact->delete();
        return back()->with('success', '訊息已刪除。');
    }
}
