<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    /* ── Keys the frontend can read ── */
    private array $readable = [
        'platform_name',
        'admin_email',
        'description',
    ];

    /* ── Keys the frontend can write (admin_email excluded) ── */
    private array $writable = [
        'platform_name',
        'description',
    ];

    /* GET /api/admin/settings */
    public function index()
    {
        $rows = DB::table('settings')
            ->whereIn('key', $this->readable)
            ->pluck('value', 'key');

        $result = [];
        foreach ($this->readable as $key) {
            $result[$key] = $rows[$key] ?? null;
        }

        return response()->json($result);
    }

    /* PUT /api/admin/settings */
    public function update(Request $request)
    {
        $data = $request->validate([
            'platform_name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
        ]);

        foreach ($data as $key => $value) {
            if (!in_array($key, $this->writable))
                continue;

            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => (string) $value, 'updated_at' => now()]
            );
        }

        return response()->json(['message' => 'Settings saved successfully.']);
    }
}