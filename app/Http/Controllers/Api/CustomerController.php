<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::where('store_id', $request->user()->store_id)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json(['status' => 'success', 'data' => $customers]);
    }

    public function show(Request $request, string $id)
    {
        $customer = Customer::where('store_id', $request->user()->store_id)
            ->with(['transactions' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }])
            ->find($id);

        if (!$customer) {
            return response()->json(['status' => 'error', 'message' => 'Pelanggan tidak ditemukan'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $customer]);
    }
}