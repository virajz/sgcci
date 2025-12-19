<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|file|max:20480', // 20MB
        ]);

        $file = $request->file('logo');
        $extension = strtolower($file->getClientOriginalExtension());

        // Validate file extension
        if (! in_array($extension, ['ai', 'cdr', 'psd'])) {
            return response()->json([
                'message' => 'Only AI, CDR, and PSD files are allowed.',
            ], 422);
        }

        $filename = Str::random(40).'.'.$extension;
        $path = $file->storeAs('booking-logos', $filename, 'public');

        return response()->json([
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    public function downloadLogo(string $path)
    {
        if (! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return response()->download(Storage::disk('public')->path($path));
    }
}
