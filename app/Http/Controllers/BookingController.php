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

        // Find the booking with this logo path to get the original filename
        $booking = \App\Models\Booking::where('company_logo', $path)->first();

        // Use the original filename if available, otherwise use the stored filename
        $filename = $booking?->company_logo_original_name ?? basename($path);

        $headers = [
            'Content-Type' => Storage::disk('public')->mimeType($path),
            'Content-Disposition' => 'attachment; filename="'.str_replace('"', '\\"', $filename).'"',
        ];

        return response()->stream(function () use ($path) {
            $stream = Storage::disk('public')->readStream($path);
            fpassthru($stream);
            fclose($stream);
        }, 200, $headers);
    }
}
