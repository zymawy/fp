<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Transformers\CategoryTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function index(Request $request)
    {
        $query = Category::query();
        
        // Check if relationships were requested via includes parameter
        if ($request->has('include')) {
            $requestedIncludes = explode(',', $request->input('include'));
            $validIncludes = ['causes'];
            
            $relationships = [];
            foreach ($requestedIncludes as $include) {
                if (in_array($include, $validIncludes)) {
                    $relationships[] = $include;
                }
            }
            
            if (!empty($relationships)) {
                $query->with($relationships);
            }
        }
        
        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);
        
        $perPage = (int) $request->input('per_page', 15);
        $categories = $query->withCount('causes')->paginate($perPage);
        
        return $this->respondWithPagination($categories, new CategoryTransformer);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
        ]);
        
        $data = $request->all();
        
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        
        $category = Category::create($data);
        
        return $this->respondWithData($category, new CategoryTransformer, 'category', 201);
    }

    /**
     * Display the specified resource.
     *
     * @param string $id
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function show(string $id, Request $request)
    {
        $query = Category::withCount('causes');
        
        // Check if relationships were requested via includes parameter
        if ($request->has('include')) {
            $requestedIncludes = explode(',', $request->input('include'));
            $validIncludes = ['causes'];
            
            $relationships = [];
            foreach ($requestedIncludes as $include) {
                if (in_array($include, $validIncludes)) {
                    $relationships[] = $include;
                }
            }
            
            if (!empty($relationships)) {
                $query->with($relationships);
            }
        }
        
        // Try to find by ID first
        $category = $query->where('id', $id)
            ->orWhere('slug', $id)
            ->firstOrFail();
        
        return $this->respondWithData($category, new CategoryTransformer, 'category');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Category $category
     * @return \Dingo\Api\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
        ]);
        
        $category->update($request->all());
        
        return $this->respondWithData($category, new CategoryTransformer, 'category');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Category $category
     * @return \Dingo\Api\Http\Response
     */
    public function destroy(Category $category)
    {
        // Check if category has causes
        if ($category->causes()->exists()) {
            return $this->respondWithError('Cannot delete category with associated causes', 422);
        }
        
        $category->delete();
        
        return $this->response->noContent();
    }
} 