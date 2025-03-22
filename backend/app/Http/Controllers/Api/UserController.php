<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UserController extends BaseController
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
        $query = User::query();
        
        // Apply filters
        // Filter by name
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }
        
        // Filter by email
        if ($request->has('email')) {
            $query->where('email', 'like', '%' . $request->input('email') . '%');
        }
        
        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }
        
        // General search (searches in name and email)
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }
        
        // Apply sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);
        
        // Paginate results
        $perPage = (int) $request->input('per_page', 10);
        $users = $query->paginate($perPage);
        
        return $this->respondWithPagination($users, new UserTransformer, 'user', 200);
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
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:admin,user',
        ]);
        
        $data = $request->all();
        $data['password'] = bcrypt($data['password']);
        
        $user = User::create($data);
        
        return $this->respondWithData($user, new UserTransformer, 'user', 201);
    }

    /**
     * Display the specified resource.
     *
     * @param User $user
     * @return \Dingo\Api\Http\Response
     */
    public function show(User $user)
    {
        return $this->respondWithData($user, new UserTransformer, 'user', 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param User $user
     * @return \Dingo\Api\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $this->checkAdmin();
        
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|string|in:admin,user',
        ]);
        
        $data = $request->all();
        
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }
        
        $user->update($data);
        
        return $this->respondWithData($user, new UserTransformer, 'user', 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return \Dingo\Api\Http\Response
     */
    public function destroy(User $user)
    {
        $this->checkAdmin();
        
        // Prevent deleting yourself
        if (auth()->id() === $user->id) {
            throw new AccessDeniedHttpException('Cannot delete your own account');
        }
        
        $user->delete();
        
        return $this->response->noContent();
    }

    /**
     * Get the authenticated user's profile
     *
     * @return \Dingo\Api\Http\Response
     */
    public function me()
    {
        $user = auth()->user();
        
        return $this->response->array([
            'success' => true,
            'data' => [
                'user' => $user
            ]
        ]);
    }
}
