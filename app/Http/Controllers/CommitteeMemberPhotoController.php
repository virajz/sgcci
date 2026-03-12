<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CommitteeMember;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class CommitteeMemberPhotoController extends Controller
{
    public function show(CommitteeMember $committeeMember): Response
    {
        abort_unless($committeeMember->photo && Storage::exists($committeeMember->photo), 404);

        $contents = Storage::get($committeeMember->photo);
        $mime = Storage::mimeType($committeeMember->photo) ?: 'image/jpeg';

        return response($contents)->header('Content-Type', $mime);
    }
}
