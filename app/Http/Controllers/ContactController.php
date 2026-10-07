<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:100',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'required|regex:/^[0-9]{10,13}$/',
            'message' => 'required|string|min:5|max:2000',
        ]);

        DB::table('contact_messages')->insert([
            'name'       => $validated['name'],
            'email'      => $validated['email'] ?? null,
            'phone'      => $validated['phone'],
            'subject'    => 'No Subject',
            'message'    => $validated['message'],
            'ip_address' => $request->ip(),
            'status'     => 'unread',
            'created_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Message sent successfully']);
    }
}
