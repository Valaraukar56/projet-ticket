<?php

namespace App\Http\Controllers;

use App\Models\CasinoData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CasinoController extends Controller
{
    /**
     * Récupérer les données du casino de l'utilisateur
     */
    public function get(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $casinoData = CasinoData::firstOrCreate(
            ['user_id' => $user->id],
            [
                'money' => 100,
                'max_money' => 1000,
                'machines' => [['occupied' => false, 'level' => 1]],
                'last_collected_at' => now(),
            ]
        );

        // Si machines est null, initialiser avec une machine par défaut
        if (empty($casinoData->machines)) {
            $casinoData->machines = [['occupied' => false, 'level' => 1]];
            $casinoData->save();
        }

        // Calculer les gains offline
        $offlineEarnings = 0;
        $lastCollected = $casinoData->last_collected_at ?? $casinoData->updated_at ?? now();
        $secondsElapsed = abs(now()->diffInSeconds($lastCollected));

        // Calculer les gains selon le niveau de chaque machine
        $machines = is_array($casinoData->machines) ? $casinoData->machines : [['occupied' => false, 'level' => 1]];
        $totalEarningsPerTick = 0;

        foreach ($machines as $machine) {
            if (isset($machine['occupied']) && $machine['occupied'] === true) {
                $level = $machine['level'] ?? 1;
                // Niveau 1 = 1$, niveau 2 = 3$, niveau 3 = 5$, etc.
                $machineEarnings = 1 + ($level - 1) * 2;
                $totalEarningsPerTick += $machineEarnings;
            }
        }

        if ($totalEarningsPerTick > 0 && $secondsElapsed > 1) {
            // Gains par seconde (1 tick = 1.5 secondes)
            $earningsPerSecond = $totalEarningsPerTick / 1.5;
            $totalEarnings = (int) floor($earningsPerSecond * $secondsElapsed);

            // Respecter le cap
            $maxMoney = $casinoData->max_money ?? PHP_INT_MAX;
            $spaceAvailable = max(0, $maxMoney - $casinoData->money);
            $offlineEarnings = min($totalEarnings, $spaceAvailable);

            // Mettre à jour l'argent
            if ($offlineEarnings > 0) {
                $casinoData->money += $offlineEarnings;
            }
        }

        // Mettre à jour le timestamp
        $casinoData->last_collected_at = now();
        $casinoData->save();

        return response()->json([
            'money' => $casinoData->money,
            'max_money' => $casinoData->max_money,
            'machines' => $casinoData->machines,
            'offline_earnings' => $offlineEarnings,
        ]);
    }

    /**
     * Sauvegarder les données du casino
     */
    public function save(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $validated = $request->validate([
            'money' => 'required|integer|min:0',
            'max_money' => 'nullable|integer|min:1000',
            'machines' => 'required|array',
        ]);

        $casinoData = CasinoData::updateOrCreate(
            ['user_id' => $user->id],
            [
                'money' => $validated['money'],
                'max_money' => $validated['max_money'],
                'machines' => $validated['machines'],
                'last_collected_at' => now(),
            ]
        );

        return response()->json(['success' => true, 'data' => $casinoData]);
    }

    /**
     * Débloquer le casino (quand le joueur atteint 5000$)
     */
    public function unlock(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        // Vérifier si déjà débloqué ou si le solde est suffisant
        if ($user->casino_unlocked) {
            return response()->json(['success' => true, 'already_unlocked' => true]);
        }

        if ($user->balance >= 5000) {
            $user->casino_unlocked = true;
            $user->save();
            return response()->json(['success' => true, 'unlocked' => true]);
        }

        return response()->json(['error' => 'Solde insuffisant (5000$ requis)'], 403);
    }
}
