<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends BaseController
{
    /**
     * Upload avatar image
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'file' => 'required|file|image|max:5120', // 5MB max
        ]);

        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $filename = 'avatar_' . $user->id . '_' . Str::random(10) . '.' . $extension;
        $path = 'avatars/' . $filename;

        // Store the file in the public disk
        $file->storeAs('avatars', $filename, 'public');

        // Get the URL to the file
        $url = Storage::disk('public')->url($path);

        // Delete old avatar if it exists
        if ($user->avatar_url) {
            // Extract filename from the full URL
            $oldPath = parse_url($user->avatar_url, PHP_URL_PATH);
            if ($oldPath) {
                $oldFilename = basename($oldPath);
                if (Storage::disk('public')->exists('avatars/' . $oldFilename)) {
                    Storage::disk('public')->delete('avatars/' . $oldFilename);
                }
            }
        }

        // Update the user's avatar_url
        $user->avatar_url = $url;
        $user->save();

        return response()->json([
            'success' => true,
            'url' => $url,
            'message' => 'Avatar uploaded successfully'
        ]);
    }
} 