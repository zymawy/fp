<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class AdminPanelController extends Controller
{
    /**
     * Serve the admin panel index page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin-panel.index');
    }

    /**
     * Serve admin panel static assets
     *
     * @param string $path
     * @return \Illuminate\Http\Response
     */
    public function asset($path)
    {
        $filePath = public_path('admin-panel/' . $path);
        
        if (!File::exists($filePath)) {
            abort(404);
        }

        $mimeType = File::mimeType($filePath);
        $contents = File::get($filePath);

        return Response::make($contents, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
        ]);
    }
} 