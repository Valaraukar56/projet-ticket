<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GameLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'balance' => 100,
        ]);

        // Attribuer le rôle joueur
        $user->assignRole('joueur');

        Auth::login($user);

        // Log dans MongoDB
        GameLogService::logRegister();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'balance' => $user->balance,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Connexion d'un utilisateur existant
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Email ou mot de passe incorrect.'],
            ]);
        }

        $user = Auth::user();

        // Log dans MongoDB
        GameLogService::logLogin();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'balance' => $user->balance,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        // Log dans MongoDB avant déconnexion
        GameLogService::logLogout();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Déconnecté']);
    }

    /**
     * Récupérer l'utilisateur connecté
     */
    public function me()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['user' => null]);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'balance' => $user->balance,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Mettre à jour le solde
     */
    public function updateBalance(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $request->validate([
            'balance' => 'required|integer|min:0',
        ]);

        $user->balance = $request->balance;
        $user->save();

        return response()->json([
            'balance' => $user->balance,
        ]);
    }

    /**
     * Supprimer le compte (YOLO CHAOS)
     */
    public function deleteAccount(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $userName = $user->name;
        $finalBalance = $user->balance;

        // Log YOLO death dans MongoDB
        GameLogService::logYoloDeath($userName, $finalBalance);

        // Déconnecter l'utilisateur
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Supprimer le compte
        $user->delete();

        return response()->json([
            'message' => "Le compte de {$userName} a été supprimé. YOLO!",
            'deleted' => true,
        ]);
    }
}
