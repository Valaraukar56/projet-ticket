<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'             => 'required|string',
            'price'            => 'required|integer|min:0',
            'loss_percentage'  => 'required|integer|min:0|max:100',
            'potential_gain'   => 'required|integer|min:0',
        ]);

        $user = Auth::user();

        $ticket = Ticket::create([
            'name'             => $request->name,
            'price'            => $request->price,
            'loss_percentage'  => $request->loss_percentage,
            'potential_gain'   => $request->potential_gain,
            'user_id'          => $user?->id,
            'status'           => 'purchased',
        ]);

        return response()->json(['ticket' => $ticket], 201);
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'result' => 'required|in:win,lose',
        ]);

        $ticket = Ticket::findOrFail($id);
        $ticket->update([
            'status' => 'completed',
            'result' => $request->result,
            'scratched_percentage' => 100,
        ]);

        return response()->json(['ticket' => $ticket]);
    }

    public function openTickets(): JsonResponse
    {
        $tickets = Ticket::with('user:id,name,email')
            ->whereIn('status', ['purchased', 'scratching'])
            ->get();

        return response()->json(['tickets' => $tickets]);
    }

    public function closedTickets(): JsonResponse
    {
        $tickets = Ticket::with('user:id,name,email')
            ->where('status', 'completed')
            ->get();

        return response()->json(['tickets' => $tickets]);
    }

    public function ticketsByUser(string $email): JsonResponse
    {
        $user = User::where('email', $email)->firstOrFail();

        $tickets = Ticket::where('user_id', $user->id)->get();

        return response()->json([
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            'tickets' => $tickets,
        ]);
    }
}
