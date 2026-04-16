<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    /**
     * Vérifier que l'utilisateur est admin
     */
    private function checkAdmin()
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('admin')) {
            abort(403, 'Accès refusé');
        }
    }

    /**
     * Liste tous les utilisateurs
     */
    public function users()
    {
        $this->checkAdmin();

        $users = User::with('roles')
            ->orderBy('balance', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'balance' => $user->balance,
                    'roles' => $user->getRoleNames(),
                    'created_at' => $user->created_at->format('d/m/Y H:i'),
                ];
            });

        return response()->json(['users' => $users]);
    }

    /**
     * Modifier le solde d'un utilisateur
     */
    public function updateUserBalance(Request $request, $id)
    {
        $this->checkAdmin();

        $request->validate([
            'balance' => 'required|integer|min:0',
        ]);

        $user = User::findOrFail($id);
        $user->balance = $request->balance;
        $user->save();

        return response()->json([
            'message' => 'Solde mis à jour',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'balance' => $user->balance,
            ],
        ]);
    }

    /**
     * Supprimer un utilisateur
     */
    public function deleteUser($id)
    {
        $this->checkAdmin();

        $user = User::findOrFail($id);

        // Empêcher la suppression d'un admin
        if ($user->hasRole('admin')) {
            return response()->json(['error' => 'Impossible de supprimer un admin'], 403);
        }

        $userName = $user->name;
        $user->delete();

        return response()->json([
            'message' => "Utilisateur {$userName} supprimé",
        ]);
    }

    /**
     * Récupérer tous les paramètres (slots + tickets)
     */
    public function getTicketSettings()
    {
        $this->checkAdmin();

        // Paramètres par défaut des icônes YOLO
        $defaultSlots = [
            'jackpot' => ['icon' => '💰', 'name' => 'Jackpot', 'gain' => 10000],
            'bigWin' => ['icon' => '💎', 'name' => 'Gros Gain', 'gain' => 5000],
            'win' => ['icon' => '🍒', 'name' => 'Gain', 'gain' => 2500],
            'smallWin' => ['icon' => '⭐', 'name' => 'Petit Gain', 'gain' => 1000],
            'bomb' => ['icon' => '💣', 'name' => 'Chaos', 'gain' => 0],
            'lose' => ['icon' => '💀', 'name' => 'Perdu', 'gain' => 0],
        ];

        // Paramètres par défaut des tickets
        $defaultTickets = [
            'metro' => [
                ['id' => 'm1', 'name' => 'Étoile Filante', 'price' => 1, 'lossPercentage' => 20, 'baseGain' => 2, 'jackpotGain' => 5],
                ['id' => 'm2', 'name' => 'Constellation', 'price' => 2, 'lossPercentage' => 25, 'baseGain' => 3, 'jackpotGain' => 8],
                ['id' => 'm3', 'name' => 'Galaxie', 'price' => 3, 'lossPercentage' => 30, 'baseGain' => 5, 'jackpotGain' => 12],
                ['id' => 'm4', 'name' => 'Cosmos', 'price' => 5, 'lossPercentage' => 35, 'baseGain' => 8, 'jackpotGain' => 20],
            ],
            'bus' => [
                ['id' => 'b1', 'name' => 'Petit Cash', 'price' => 2, 'lossPercentage' => 30, 'baseGain' => 3, 'jackpotGain' => 10],
                ['id' => 'b2', 'name' => 'Cash Express', 'price' => 4, 'lossPercentage' => 35, 'baseGain' => 6, 'jackpotGain' => 18],
                ['id' => 'b3', 'name' => 'Banco', 'price' => 6, 'lossPercentage' => 40, 'baseGain' => 10, 'jackpotGain' => 30],
                ['id' => 'b4', 'name' => 'Mega Cash', 'price' => 10, 'lossPercentage' => 45, 'baseGain' => 18, 'jackpotGain' => 50],
            ],
            'train' => [
                ['id' => 't1', 'name' => 'Pépite', 'price' => 5, 'lossPercentage' => 45, 'baseGain' => 8, 'jackpotGain' => 30],
                ['id' => 't2', 'name' => 'Lingot', 'price' => 10, 'lossPercentage' => 50, 'baseGain' => 18, 'jackpotGain' => 80],
                ['id' => 't3', 'name' => 'Trésor', 'price' => 15, 'lossPercentage' => 55, 'baseGain' => 28, 'jackpotGain' => 150],
                ['id' => 't4', 'name' => 'El Dorado', 'price' => 25, 'lossPercentage' => 60, 'baseGain' => 50, 'jackpotGain' => 300],
            ],
            'loterie' => [
                ['id' => 'l1', 'name' => 'Vegas Night', 'price' => 5, 'lossPercentage' => 70, 'baseGain' => 15, 'jackpotGain' => 100],
                ['id' => 'l2', 'name' => 'High Roller', 'price' => 10, 'lossPercentage' => 75, 'baseGain' => 35, 'jackpotGain' => 500],
                ['id' => 'l3', 'name' => 'Royal Flush', 'price' => 20, 'lossPercentage' => 85, 'baseGain' => 100, 'jackpotGain' => 2000],
                ['id' => 'l4', 'name' => 'YOLO', 'price' => 50, 'lossPercentage' => 90, 'baseGain' => 5000, 'jackpotGain' => 10000, 'cursed' => true],
            ],
        ];

        // Charger les slots
        $slotsPath = storage_path('app/slot_settings.json');
        $slots = file_exists($slotsPath)
            ? json_decode(file_get_contents($slotsPath), true)
            : $defaultSlots;

        // Charger les tickets
        $ticketsPath = storage_path('app/ticket_settings.json');
        $tickets = file_exists($ticketsPath)
            ? json_decode(file_get_contents($ticketsPath), true)
            : $defaultTickets;

        return response()->json([
            'slots' => $slots,
            'tickets' => $tickets,
        ]);
    }

    /**
     * Sauvegarder tous les paramètres
     */
    public function updateTicketSettings(Request $request)
    {
        $this->checkAdmin();

        // Sauvegarder les slots
        if ($request->has('slots')) {
            $slotsPath = storage_path('app/slot_settings.json');
            file_put_contents($slotsPath, json_encode($request->input('slots'), JSON_PRETTY_PRINT));
        }

        // Sauvegarder les tickets
        if ($request->has('tickets')) {
            $ticketsPath = storage_path('app/ticket_settings.json');
            file_put_contents($ticketsPath, json_encode($request->input('tickets'), JSON_PRETTY_PRINT));
        }

        return response()->json([
            'message' => 'Paramètres sauvegardés',
        ]);
    }
}
