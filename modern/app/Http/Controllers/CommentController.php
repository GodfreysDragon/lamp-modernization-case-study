<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __invoke(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'is_internal' => ['boolean'],
        ]);

        // The body is stored exactly as typed. Escaping happens when it is rendered:
        // Vue's {{ }} interpolation always escapes, so there's no stored-XSS path.
        $ticket->comments()->create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Comment added.');
    }
}
