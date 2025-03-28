<?php

namespace App\Http\Controllers\Api;

use App\Models\Partner;
use App\Transformers\PartnerTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PartnerController extends BaseController
{
    /**
     * Check if current user is admin
     * 
     * @throws AccessDeniedHttpException
     */
    protected function checkAdmin()
    {
        $user = auth()->user();
        
        if (!$user || !$user->isAdmin()) {
            throw new AccessDeniedHttpException('Admin access required');
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function index(Request $request)
    {
        $query = Partner::query();
        
        // Get requested includes
        $includes = $this->getRequestedIncludes($request);
        
        // Check if we should include trashed partners
        if ($request->has('withTrashed') && $request->input('withTrashed') === 'true') {
            $query->withTrashed();
        } else if ($request->has('onlyTrashed') && $request->input('onlyTrashed') === 'true') {
            $query->onlyTrashed();
        }
        
        // Filter by featured status if provided
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
            // Limit to 6 partners when showing featured
            $perPage = 6;
        } else {
            $perPage = $request->input('per_page', 10);
        }
        
        // Apply name filter if provided
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }
        
        // Sort by created_at or other fields
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);
        
        $partners = $query->paginate($perPage);
        
        // Process logos for placeholders if needed
        foreach ($partners as $partner) {
            if (empty($partner->logo) || (!Str::startsWith($partner->logo, ['http://', 'https://']))) {
                // Use partner name for relevant image
                $partner->logo = 'https://source.unsplash.com/400x200/?company,logo,' . urlencode($partner->name);
            }
        }
        
        return $this->respondWithPagination($partners, new PartnerTransformer, 'partners');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function store(Request $request)
    {
        $this->checkAdmin();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|max:2048',
            'is_featured' => 'boolean',
        ]);
        
        $data = $request->all();
        
        // Handle logo upload
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $path = $file->store('partners', 'public');
            $data['logo'] = Storage::url($path);
        }
        
        $partner = Partner::create($data);
        
        return $this->respondWithData($partner, new PartnerTransformer, 'partner', 201);
    }

    /**
     * Display the specified resource.
     *
     * @param string $id
     * @return \Dingo\Api\Http\Response
     */
    public function show(string $id)
    {
        $partner = Partner::findOrFail($id);
        
        return $this->respondWithData($partner, new PartnerTransformer, 'partner');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Partner $partner
     * @return \Dingo\Api\Http\Response
     */
    public function update(Request $request, Partner $partner)
    {
        $this->checkAdmin();
        
        $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|max:2048',
            'is_featured' => 'boolean',
        ]);
        
        $data = $request->all();
        
        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if it exists
            if ($partner->logo) {
                $oldPath = str_replace('/storage/', '', $partner->logo);
                Storage::disk('public')->delete($oldPath);
            }
            
            $file = $request->file('logo');
            $path = $file->store('partners', 'public');
            $data['logo'] = Storage::url($path);
        }
        
        $partner->update($data);
        
        return $this->respondWithData($partner, new PartnerTransformer, 'partner');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Partner $partner
     * @return \Dingo\Api\Http\Response
     */
    public function destroy(Partner $partner)
    {
        $this->checkAdmin();
        
        // Delete logo if it exists
        if ($partner->logo) {
            $path = str_replace('/storage/', '', $partner->logo);
            Storage::disk('public')->delete($path);
        }
        
        $partner->delete();
        
        return $this->response->noContent();
    }
} 