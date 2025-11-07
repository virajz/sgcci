<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupportTicketController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB
        ]);

        $file = $request->file('document');
        $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('support-tickets', $filename, 'public');

        return response()->json([
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
        ]);
    }

    public function download(string $path)
    {
        if (! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return response()->download(Storage::disk('public')->path($path));
    }
}
