<?php

namespace App\Services;

use App\Models\GameLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class GameLogService
{
    /**
     * Log une action de jeu dans MongoDB
     */
    public static function log(string $action, array $data = [], ?Request $request = null): ?GameLog
    {
        try {
            $user = Auth::user();

            return GameLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'Anonyme',
                'action' => $action,
                'ticket_type' => $data['ticket_type'] ?? null,
                'ticket_name' => $data['ticket_name'] ?? null,
                'amount' => $data['amount'] ?? 0,
                'balance_before' => $data['balance_before'] ?? null,
                'balance_after' => $data['balance_after'] ?? null,
                'result' => $data['result'] ?? null,
                'ip_address' => $request?->ip() ?? request()->ip(),
                'user_agent' => $request?->userAgent() ?? request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Si MongoDB n'est pas disponible, on ne bloque pas l'application
            \Log::warning('MongoDB log failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Log un achat de ticket
     */
    public static function logPurchase(string $ticketType, string $ticketName, int $price, int $balanceBefore): ?GameLog
    {
        return self::log('purchase', [
            'ticket_type' => $ticketType,
            'ticket_name' => $ticketName,
            'amount' => -$price,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore - $price,
            'result' => 'pending',
        ]);
    }

    /**
     * Log un résultat de jeu
     */
    public static function logResult(string $ticketType, string $ticketName, int $gain, int $balanceBefore, string $result): ?GameLog
    {
        return self::log('result', [
            'ticket_type' => $ticketType,
            'ticket_name' => $ticketName,
            'amount' => $gain,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore + $gain,
            'result' => $result,
        ]);
    }

    /**
     * Log une inscription
     */
    public static function logRegister(): ?GameLog
    {
        return self::log('register', [
            'result' => 'success',
        ]);
    }

    /**
     * Log une connexion
     */
    public static function logLogin(): ?GameLog
    {
        return self::log('login', [
            'result' => 'success',
        ]);
    }

    /**
     * Log une déconnexion
     */
    public static function logLogout(): ?GameLog
    {
        return self::log('logout', [
            'result' => 'success',
        ]);
    }

    /**
     * Log un YOLO (suppression de compte)
     */
    public static function logYoloDeath(string $userName, int $finalBalance): ?GameLog
    {
        return self::log('yolo_death', [
            'ticket_type' => 'loterie',
            'ticket_name' => 'YOLO',
            'amount' => -$finalBalance,
            'balance_before' => $finalBalance,
            'balance_after' => 0,
            'result' => 'account_deleted',
        ]);
    }
}
