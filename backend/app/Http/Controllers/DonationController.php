<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidateDonationRequest;
use App\Models\Donation;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $donations = Donation::query()->paginate();

        return $this->success($donations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ValidateDonationRequest $request)
    {
        $donation = Donation::query()->create($request->validated());

        return $this->success($donation);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $donation = Donation::query()->findOrFail($id);
        return $this->success($donation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ValidateDonationRequest $request, string $id)
    {
        $donation = Donation::query()->findOrFail($id);
        $donation->update($request->validated());
        return $this->success($donation);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $donation = Donation::query()->findOrFail($id);
        $donation->delete();
        return $this->success();
    }
}
