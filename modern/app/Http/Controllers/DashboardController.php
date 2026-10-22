<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // One grouped query instead of one COUNT(*) per status.
        $totals = Ticket::query()
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = collect(TicketStatus::cases())
            ->mapWithKeys(fn (TicketStatus $status) => [$status->value => (int) ($totals[$status->value] ?? 0)]);

        // Eager-load the customer and count the comments up front, so the cost is
        // three queries whether there are 5 tickets or 500.
        $mine = $request->user()
            ->assignedTickets()
            ->unresolved()
            ->with('customer')
            ->withCount('comments')
            ->latest('id')
            ->get();

        return Inertia::render('Dashboard', [
            'counts' => $counts,
            'overdueCount' => Ticket::overdue()->count(),
            'tickets' => TicketResource::collection($mine),
        ]);
    }
}
