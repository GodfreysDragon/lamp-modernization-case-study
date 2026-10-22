<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(TicketStatus::class)],
            'q' => ['nullable', 'string', 'max:100'],
            'assignee' => ['nullable', 'integer'],
        ]);

        $tickets = Ticket::query()
            ->filter($filters)
            ->with(['customer', 'assignee:id,name'])
            ->withCount('comments')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('tickets/Index', [
            'tickets' => TicketResource::collection($tickets),
            'filters' => (object) $filters,
            'agents' => $this->agents(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('tickets/Create', [
            'agents' => $this->agents(),
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = Ticket::create($request->validated());

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket created.');
    }

    public function show(Ticket $ticket): Response
    {
        $ticket->load(['customer', 'assignee:id,name', 'comments.author:id,name']);

        return Inertia::render('tickets/Show', [
            'ticket' => TicketResource::make($ticket),
            'can' => [
                'delete' => Gate::allows('delete', $ticket),
            ],
        ]);
    }

    public function edit(Ticket $ticket): Response
    {
        $ticket->load('customer');

        return Inertia::render('tickets/Edit', [
            'ticket' => TicketResource::make($ticket),
            'customer' => $ticket->customer ? CustomerResource::make($ticket->customer) : null,
            'agents' => $this->agents(),
        ]);
    }

    public function update(StoreTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('update', $ticket);

        $ticket->update($request->validated());

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket saved.');
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('delete', $ticket);

        $ticket->delete();

        return redirect()->route('tickets.index')->with('success', 'Ticket deleted.');
    }

    private function agents()
    {
        return User::orderBy('name')->get(['id', 'name']);
    }
}
