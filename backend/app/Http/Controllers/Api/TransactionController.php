<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidateTransactionRequest;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transactions = Transaction::query()->paginate();
        return $this->success($transactions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ValidateTransactionRequest $request)
    {
        $transaction = Transaction::query()->create($request->validated());
        return $this->success($transaction);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $transaction = Transaction::query()->findOrFail($id);
        return $this->success($transaction);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ValidateTransactionRequest $request, string $id)
    {
        $transaction = Transaction::query()->findOrFail($id);
        $transaction->update($request->validated());
        return $this->success($transaction);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $transaction = Transaction::query()->findOrFail($id);
        $transaction->delete();
        return $this->success();
    }
}
