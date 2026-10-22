<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Replaces legacy ajax/update_status.php, which had no login check and put
 * $_POST values straight into an UPDATE statement.
 */
class TicketStatusController extends Controller
{
    public function __invoke(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('update', $ticket);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(TicketStatus::class)],
        ]);

        $ticket->update($validated);

        return back()->with('success', "Ticket #{$ticket->id} marked {$ticket->status->label()}.");
    }
}
