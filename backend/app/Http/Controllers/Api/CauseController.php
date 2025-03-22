<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Cause;
use App\Transformers\CauseTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CauseController extends BaseController
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
     * Display a listing of the resource with filtering options.
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function index(Request $request)
    {
        // Base relationships to always load
        $relationships = ['category'];
        
        // Check if additional relationships were requested via includes parameter
        if ($request->has('include')) {
            $requestedIncludes = explode(',', $request->input('include'));
            $validIncludes = ['partner', 'donations', 'updates'];
            
            foreach ($requestedIncludes as $include) {
                if (in_array($include, $validIncludes) && !in_array($include, $relationships)) {
                    $relationships[] = $include;
                }
            }
        }
        
        $query = Cause::query()->with($relationships);
        
        // Apply filters
        // Filter by title
        if ($request->has('title')) {
            $query->where('title', 'like', '%' . $request->input('title') . '%');
        }
        
        // Filter by description
        if ($request->has('description')) {
            $query->where('description', 'like', '%' . $request->input('description') . '%');
        }
        
        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        
        // Filter by target amount (range)
        if ($request->has('min_target')) {
            $query->where('goal_amount', '>=', $request->input('min_target'));
        }
        
        if ($request->has('max_target')) {
            $query->where('goal_amount', '<=', $request->input('max_target'));
        }
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        // Filter by featured status if provided
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }
        
        // General search (searches in title and description)
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }
        
        // Apply sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);
        
        // Paginate results
        $perPage = (int) $request->input('per_page', 10);
        $causes = $query->paginate($perPage);
        
        return $this->respondWithPagination($causes, new CauseTransformer, 'cause', 200);
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
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:causes',
            'description' => 'required|string',
            'image' => 'nullable|string',
            'goal_amount' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|string|in:active,inactive,completed',
            'category_id' => 'required|exists:categories,id',
            'partner_id' => 'nullable|exists:partners,id',
            'featured_image' => 'nullable|image|max:2048',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ]);
        
        $data = $request->all();
        
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }
        
        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $file = $request->file('featured_image');
            $path = $file->store('causes', 'public');
            $data['featured_image'] = Storage::url($path);
        }
        
        $data['raised_amount'] = 0; // Initialize raised amount
        
        $cause = Cause::create($data);
        $cause->load(['category', 'partner']);
        
        return $this->respondWithData($cause, new CauseTransformer, 'cause', 201);
    }

    /**
     * Display the specified resource.
     *
     * @param string $id
     * @return \Dingo\Api\Http\Response
     */
    public function show(string $id, Request $request)
    {
        // Base relationships to always load
        $relationships = ['category'];
        
        // Check if additional relationships were requested via includes parameter
        if ($request->has('include')) {
            $requestedIncludes = explode(',', $request->input('include'));
            $validIncludes = ['partner', 'donations', 'updates'];
            
            foreach ($requestedIncludes as $include) {
                if (in_array($include, $validIncludes) && !in_array($include, $relationships)) {
                    $relationships[] = $include;
                }
            }
        }
        
        // Try to find by ID first, then by slug
        $cause = Cause::with($relationships)
            ->where('id', $id)
            ->orWhere('slug', $id)
            ->firstOrFail();
        
        return $this->respondWithData($cause, new CauseTransformer, 'cause', 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Cause $cause
     * @return \Dingo\Api\Http\Response
     */
    public function update(Request $request, Cause $cause)
    {
        $this->checkAdmin();
        
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'slug' => 'nullable|string|max:255|unique:causes,slug,' . $cause->id,
            'description' => 'sometimes|string',
            'image' => 'nullable|string',
            'goal_amount' => 'sometimes|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'sometimes|string|in:active,inactive,completed',
            'category_id' => 'sometimes|exists:categories,id',
            'partner_id' => 'nullable|exists:partners,id',
            'featured_image' => 'nullable|image|max:2048',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ]);
        
        $data = $request->all();
        
        if (isset($data['title']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }
        
        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            // Delete old image if it exists
            if ($cause->featured_image) {
                $oldPath = str_replace('/storage/', '', $cause->featured_image);
                Storage::disk('public')->delete($oldPath);
            }
            
            $file = $request->file('featured_image');
            $path = $file->store('causes', 'public');
            $data['featured_image'] = Storage::url($path);
        }
        
        $cause->update($data);
        $cause->load(['category', 'partner', 'donations', 'updates']);
        
        return $this->respondWithData($cause, new CauseTransformer, 'cause', 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Cause $cause
     * @return \Dingo\Api\Http\Response
     */
    public function destroy(Cause $cause)
    {
        $this->checkAdmin();
        
        // Check if cause has donations
        if ($cause->donations()->exists()) {
            return $this->respondWithError('Cannot delete cause with associated donations', 422);
        }
        
        // Delete featured image if it exists
        if ($cause->featured_image) {
            $path = str_replace('/storage/', '', $cause->featured_image);
            Storage::disk('public')->delete($path);
        }
        
        $cause->delete();
        
        return $this->response->noContent();
    }
} 