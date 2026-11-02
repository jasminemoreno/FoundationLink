<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\PrivateFile;
use Illuminate\Support\Facades\Storage;

class PrivateFileController extends Controller
{
    /** Opens a private file. The route only lets a valid, unexpired signature through. */
    public function show(string $path)
    {
        abort_unless(PrivateFile::isPrivatePath($path), 404);

        // Files uploaded earlier stay on the public disk until `files:make-private` is run
        $disk = collect(['local', 'public'])->first(fn($name) => Storage::disk($name)->exists($path));
        abort_unless($disk, 404);

        return Storage::disk($disk)->response($path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
