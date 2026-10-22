<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON source for the CustomerPicker component. Replaces ajax/search_customers.php,
 * which built JSON by string concatenation and broke on names like "O'Brien".
 */
class CustomerSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $term = $request->string('term')->trim()->limit(100, '')->toString();

        $results = Customer::query()
            ->search($term)
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn (Customer $customer) => ['id' => $customer->id, 'label' => $customer->label()]);

        return response()->json($results);
    }
}
