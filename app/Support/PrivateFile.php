<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * IDs, foundation documents and payment receipts are kept outside the public
 * folder. They open only through a signed link that expires, which the API
 * gives to the people allowed to see them. Pictures can't send a login token,
 * so the signature is what proves the link came from us.
 */
class PrivateFile
{
    /** Folders whose files are private. Everything else stays on the public disk. */
    public const FOLDERS = ['foundation/admin_ids/', 'foundation/documents/', 'donations/proofs/'];

    public const LINK_MINUTES = 60;

    /**
     * A full address, so the mobile app shows it as it is. Only the path is signed,
     * so the link works whichever address the site was reached by (ngrok, Wi-Fi, host).
     */
    public static function url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return url(URL::temporarySignedRoute('private-file', now()->addMinutes(self::LINK_MINUTES), ['path' => $path], false));
    }

    public static function isPrivatePath(string $path): bool
    {
        return !str_contains($path, '..') && !str_contains($path, '\\') && Str::startsWith($path, self::FOLDERS);
    }
}
