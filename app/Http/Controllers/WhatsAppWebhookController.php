<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppWebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    public function receive(Request $request): JsonResponse|Response
    {
        WhatsAppWebhookLog::create([
            'method' => $request->method(),
            'path' => $request->path(),
            'headers' => $request->headers->all(),
            'payload' => $request->all() ?: null,
            'ip' => $request->ip(),
        ]);

        return response()->json(['status' => 'ok']);
    }
}
