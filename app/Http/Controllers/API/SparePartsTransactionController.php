<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SparePart;
use App\Models\SparePartsTransaction;
use App\Models\SparePartsTransactionsProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SparePartsTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = SparePartsTransaction::query();

        // Eager load relationships
        $query->with('user', 'products');

        // Apply filters if present
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        // Add more filters as needed

        $transactions = $query->get();

        return response()->json(['data' => $transactions]);
    }

    public function show($id)
    {
        $transaction = SparePartsTransaction::with('user', 'products')->find($id);
        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }
        return response()->json(['data' => $transaction]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'customer_email' => 'nullable|email',
            'customer_phone_number' => 'nullable|string',
            'transaction_id' => 'nullable|numeric',
            'tx_ref' => 'nullable|string',
            'flw_ref' => 'nullable|string',
            'currency' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'charged_amount' => 'nullable|numeric',
            'charge_response_code' => 'nullable|string',
            'charge_response_message' => 'nullable|string',
            'gateway_created_at' => 'nullable|date',
            'products' => 'nullable|array',
            'products.*.name' => 'required|string',
            'products.*.price' => 'required|numeric',
            'products.*.spare_parts_id' => 'required|exists:spare_parts,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            // Start a database transaction
            DB::beginTransaction();

            // Create the SparePartsTransaction
            $transaction = SparePartsTransaction::create([
                'user_id' => $validated['user_id'],
                'status' => $validated['status'],
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone_number' => $validated['customer_phone_number'],
                'transaction_id' => $validated['transaction_id'],
                'tx_ref' => $validated['tx_ref'],
                'flw_ref' => $validated['flw_ref'],
                'currency' => $validated['currency'],
                'amount' => $validated['amount'],
                'charged_amount' => $validated['charged_amount'],
                'charge_response_code' => $validated['charge_response_code'],
                'charge_response_message' => $validated['charge_response_message'],
                'gateway_created_at' => $validated['gateway_created_at'],
            ]);

            if (isset($validated['products'])) {
                foreach ($validated['products'] as $productData) {
                    // Create each SparePartsTransactionsProduct record
                    SparePartsTransactionsProduct::create([
                        'spare_parts_id' => $productData['spare_parts_id'],
                        'spare_parts_transactions_id' => $transaction->id,
                        'quantity' => $productData['quantity'],
                        'name' => $productData['name'],
                        'price' => $productData['price'],
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);

                    // Update spare part quantity
                    $sparePart = SparePart::find($productData['spare_parts_id']);
                    if ($sparePart) {
                        $sparePart->decrement('quantity', $productData['quantity']);
                    }
                }
            }

            // Commit the transaction if all operations succeed
            DB::commit();

            // Load products relationship with the transaction
            $transaction->load('products');

            return response()->json(['message' => 'Transaction created successfully', 'data' => $transaction], 201);

        } catch (\Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();

            // Handle the exception, log it, or return an appropriate error response
            return response()->json(['message' => 'Failed to create transaction', 'error' => $e->getMessage()], 500);
        }
    }
    public function update(Request $request, $id)
    {
        $transaction = SparePartsTransaction::find($id);
        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $validated = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'status' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'customer_email' => 'nullable|email',
            'customer_phone_number' => 'nullable|string',
            'transaction_id' => 'nullable|string',
            'tx_ref' => 'nullable|string',
            'flw_ref' => 'nullable|string',
            'currency' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'charged_amount' => 'nullable|numeric',
            'charge_response_code' => 'nullable|string',
            'charge_response_message' => 'nullable|string',
            'gateway_created_at' => 'nullable|date',
            'products' => 'nullable|array',
            'products.*.name' => 'required|string',
            'products.*.price' => 'required|numeric',
            'products.*.spare_parts_id' => 'required|exists:spare_parts,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            // Start a database transaction
            DB::beginTransaction();

            // Update the SparePartsTransaction
            $transaction->update([
                'user_id' => $validated['user_id'] ?? $transaction->user_id,
                'status' => $validated['status'] ?? $transaction->status,
                'customer_name' => $validated['customer_name'] ?? $transaction->customer_name,
                'customer_email' => $validated['customer_email'] ?? $transaction->customer_email,
                'customer_phone_number' => $validated['customer_phone_number'] ?? $transaction->customer_phone_number,
                'transaction_id' => $validated['transaction_id'] ?? $transaction->transaction_id,
                'tx_ref' => $validated['tx_ref'] ?? $transaction->tx_ref,
                'flw_ref' => $validated['flw_ref'] ?? $transaction->flw_ref,
                'currency' => $validated['currency'] ?? $transaction->currency,
                'amount' => $validated['amount'] ?? $transaction->amount,
                'charged_amount' => $validated['charged_amount'] ?? $transaction->charged_amount,
                'charge_response_code' => $validated['charge_response_code'] ?? $transaction->charge_response_code,
                'charge_response_message' => $validated['charge_response_message'] ?? $transaction->charge_response_message,
                'gateway_created_at' => $validated['gateway_created_at'] ?? $transaction->gateway_created_at,
            ]);

            // Remove old quantities
            foreach ($transaction->products as $product) {
                $sparePart = SparePart::find($product->spare_parts_id);
                if ($sparePart) {
                    $sparePart->decrement('quantity', $product->quantity);
                }
            }
            // Delete existing products and create new ones
            $transaction->products()->delete();

            if (isset($validated['products'])) {
                foreach ($validated['products'] as $productData) {
                    SparePartsTransactionsProduct::create([
                        'spare_parts_id' => $productData['spare_parts_id'],
                        'spare_parts_transactions_id' => $transaction->id,
                        'quantity' => $productData['quantity'],
                        'name' => $productData['name'],
                        'price' => $productData['price'],
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);

                    // Update spare part quantity
                    $sparePart = SparePart::find($productData['spare_parts_id']);
                    if ($sparePart) {
                        $sparePart->increment('quantity', $productData['quantity']);
                    }
                }
            }

            // Commit the transaction if all operations succeed
            DB::commit();

            // Load products relationship with the transaction
            $transaction->load('products');

            return response()->json(['message' => 'Transaction updated successfully', 'data' => $transaction]);

        } catch (\Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();

            // Handle the exception, log it, or return an appropriate error response
            return response()->json(['message' => 'Failed to update transaction', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $transaction = SparePartsTransaction::find($id);
        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $transaction->products()->delete();
        $transaction->delete();

        return response()->json(null, 204);
    }

    public function get_spare_part_transactions(Request $request)
    {
        $transactions = SparePartsTransaction::with('user', 'products')->where('user_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();

        return response()->json($transactions);
    }
}