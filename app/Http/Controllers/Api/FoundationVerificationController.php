<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Foundation;
use App\Models\FoundationDocument;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\FoundationVerificationSubmitted;

class FoundationVerificationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'foundation_id' => 'required|exists:foundations,id',
            'admin_id_type' => 'required|string',
            'doc_type' => 'required|string',

            'admin_files' => 'required|array|min:1|max:2',
            'admin_files.*' => 'image|max:5120',

            'doc_files' => 'required|array|min:1|max:2',
            'doc_files.*' => 'image|max:5120',
        ]);


        $foundation = Foundation::findOrFail($request->foundation_id);

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

        if ($superAdmin && $superAdmin->email) {
            Mail::to($superAdmin->email)
                ->send(new FoundationVerificationSubmitted($foundation));
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification submitted successfully.',
            'foundation_status' => $foundation->status
        ], 200);
    }
}