<?php

namespace App\Http\Controllers;

use App\Models\User;

class LeaderboardController extends Controller
{
    /**
     * Récupérer le classement des joueurs
     */
    public function index()
    {
        $players = User::role('joueur')
            ->orderBy('balance', 'desc')
            ->take(50)
            ->get(['id', 'name', 'balance']);

        return response()->json([
            'leaderboard' => $players,
        ]);
    }
}
