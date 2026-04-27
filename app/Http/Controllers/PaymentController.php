<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function purchase(Request $request): JsonResponse
    {
        $request->validate([
            'ticket_id' => 'required|integer|exists:tickets,id',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $ticket = Ticket::findOrFail($request->ticket_id);

        if ($user->balance < $ticket->price) {
            return response()->json(['error' => 'Solde insuffisant'], 422);
        }

        $user->balance -= $ticket->price;
        $user->save();

        $payment = Payment::create([
            'user_id'   => $user->id,
            'ticket_id' => $ticket->id,
            'amount'    => $ticket->price,
            'type'      => 'purchase',
        ]);

        return response()->json([
            'payment' => $payment,
            'balance' => $user->balance,
        ], 201);
    }

    public function win(Request $request): JsonResponse
    {
        $request->validate([
            'ticket_id' => 'required|integer|exists:tickets,id',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $ticket = Ticket::where('id', $request->ticket_id)
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->where('result', 'win')
            ->firstOrFail();

        $user->balance += $ticket->potential_gain;
        $user->save();

        $payment = Payment::create([
            'user_id'   => $user->id,
            'ticket_id' => $ticket->id,
            'amount'    => $ticket->potential_gain,
            'type'      => 'win',
        ]);

        return response()->json([
            'payment' => $payment,
            'balance' => $user->balance,
        ], 201);
    }

    public function history(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $payments = Payment::with('ticket:id,name,price,potential_gain')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['payments' => $payments]);
    }
}
