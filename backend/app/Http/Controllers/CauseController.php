<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidateCauseRequest;
use App\Models\Cause;
use Flugg\Responder\Responder;

class CauseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Responder $responder)
    {
        $causes = Cause::query()->simplePaginate(4);
        return $this->success($causes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ValidateCauseRequest $request)
    {
        $cause = Cause::query()->create($request->validated());

        return $this->success($cause);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $cause)
    {
        $cause = Cause::query()->findOrFail($cause);

        return $this->success($cause);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ValidateCauseRequest $request, string $id)
    {
        $cause = Cause::query()->findOrFail($id);

        $cause->update($request->validated());

        return $this->success($cause);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cause = Cause::query()->findOrFail($id);
        $cause->delete();

        return $this->success();
    }
}
