<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\NoProfanity;
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
            'name'     => ['required', 'string', 'max:255', new NoProfanity()],
            'email'    => 'required|email|unique:users',
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
                'casino_unlocked' => $user->casino_unlocked ?? false,
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
                'email' => [__('messages.invalid_credentials')],
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
                'casino_unlocked' => $user->casino_unlocked ?? false,
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

        return response()->json(['message' => __('messages.logged_out')]);
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
                'casino_unlocked' => $user->casino_unlocked ?? false,
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
            return response()->json(['error' => __('messages.unauthenticated')], 401);
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
            return response()->json(['error' => __('messages.unauthenticated')], 401);
        }

        if ($user->hasRole('admin')) {
            return response()->json(['error' => __('messages.admin_cannot_be_deleted'), 'deleted' => false], 403);
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
            'message' => __('messages.account_deleted', ['name' => $userName]),
            'deleted' => true,
        ]);
    }
}
