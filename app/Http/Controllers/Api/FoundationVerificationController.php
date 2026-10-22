<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Foundation;
use App\Models\FoundationDocument;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\FoundationVerificationSubmitted;
use Laravel\Sanctum\PersonalAccessToken;

class FoundationVerificationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'admin_id_type' => 'required|string',
            'doc_type' => 'required|string',

            'admin_files' => 'required|array|min:1|max:2',
            'admin_files.*' => 'image|max:5120',

            'doc_files' => 'required|array|min:1|max:2',
            'doc_files.*' => 'image|max:5120',
        ]);


        // Only the signed-in admin's own foundation, and only during first sign-up.
        // Verified / suspended / under-review foundations can't be pushed back into
        // review from here; rejected ones use /foundation/resubmit instead.
        $foundation = Foundation::where('user_id', auth()->id())->firstOrFail();

        if (!in_array($foundation->status, ['pending_verification', 'incomplete'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Verification documents have already been submitted for this foundation.'
            ], 422);
        }

        // Update foundation status
        $foundation->status = 'under_review';
        $foundation->save();

        /**
         * SAVE ADMIN ID DOCUMENTS
         */
        if ($request->hasFile('admin_files')) {

            foreach ($request->file('admin_files') as $file) {

                $path = $file->store('foundation/admin_ids', 'public');

                FoundationDocument::create([
                    'foundation_id' => $foundation->id,
                    'type' => 'identity',
                    'document_type' => $request->admin_id_type,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientOriginalExtension(),
                    'status' => 'pending'
                ]);
            }
        }

        /**
         * SAVE FOUNDATION LEGITIMACY DOCUMENTS
         */
        if ($request->hasFile('doc_files')) {

            foreach ($request->file('doc_files') as $file) {

                $path = $file->store('foundation/documents', 'public');

                FoundationDocument::create([
                    'foundation_id' => $foundation->id,
                    'type' => 'legitimacy',
                    'document_type' => $request->doc_type,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientOriginalExtension(),
                    'status' => 'pending'
                ]);
            }
        }

        /**
         * EMAIL SUPER ADMIN
         */
        $superAdmin = User::where('role', 'superadmin')->first();

        // Documents are already saved; if this mail failed the sign-up would show an
        // error, and retrying would then be refused as "already submitted".
        if ($superAdmin && $superAdmin->email) {
            try {
                Mail::to($superAdmin->email)
                    ->send(new FoundationVerificationSubmitted($foundation));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Sign-up is finished: retire the temporary registration token.
        // A normal login token is left alone.
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken && $token->name === AuthController::REGISTRATION_TOKEN_NAME) {
            $token->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification submitted successfully.',
            'foundation_status' => $foundation->status
        ], 200);
    }
}