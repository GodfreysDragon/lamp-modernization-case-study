<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('q')->limit(100, '')->toString();

        $customers = Customer::query()
            ->search($search)
            ->withCount('openTickets')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('customers/Index', [
            'customers' => CustomerResource::collection($customers),
            'filters' => ['q' => $search],
            'can' => ['delete' => $request->user()->is_admin],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('customers/Create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        return redirect()->route('customers.index')->with('success', 'Customer added.');
    }

    public function edit(Customer $customer): Response
    {
        return Inertia::render('customers/Edit', [
            'customer' => CustomerResource::make($customer),
        ]);
    }

    public function update(StoreCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('customers.index')->with('success', 'Customer saved.');
    }

    /**
     * Only reachable by DELETE with a CSRF token. The legacy version deleted on
     * GET customers.php?delete=ID, so any link or <img> tag could trigger it.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        Gate::authorize('delete', $customer);

        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }
}
