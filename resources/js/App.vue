<template>
    <div class="game-container">
        <!-- Écran d'authentification si non connecté -->
        <AuthForm v-if="!user && !checkingAuth" @authenticated="handleAuth" />

        <!-- Chargement initial -->
        <div v-else-if="checkingAuth" class="loading-screen">
            <div class="loader"></div>
            <p>Chargement...</p>
        </div>

        <!-- Jeu si connecté -->
        <template v-else>
            <!-- Écran d'accueil avec porte -->
            <WelcomeDoor v-if="!doorOpened" @open="openDoor" />

            <!-- Scène avec le personnage interactif -->
            <InteractiveHost
                v-else
                :balance="balance"
                :user="user"
                :is-admin="isAdmin"
                @buy-ticket="handleBuyTicket"
                @show-leaderboard="showLeaderboard = true"
                @show-admin="showAdminPanel = true"
                @logout="handleLogout"
            />

            <!-- Modal ticket Métro (style Astro FDJ) -->
            <MetroTicket
                v-if="currentTicket && isMetroTicket"
                :ticket="currentTicket"
                :balance="balance"
                @close="closeTicket"
                @result="handleResult"
                @replay="handleReplay"
            />

            <!-- Modal ticket Cash (style Cash FDJ) -->
            <CashTicket
                v-if="currentTicket && isCashTicket"
                :ticket="currentTicket"
                :balance="balance"
                @close="closeTicket"
                @result="handleResult"
                @replay="handleReplay"
            />

            <!-- Modal ticket Millionnaire (style Millionnaire FDJ) -->
            <MillionaireTicket
                v-if="currentTicket && isMillionaireTicket"
                :ticket="currentTicket"
                :balance="balance"
                @close="closeTicket"
                @result="handleResult"
                @replay="handleReplay"
            />

            <!-- Modal Roulette Casino (YOLO) -->
            <RouletteTicket
                v-if="currentTicket && isRouletteTicket"
                :ticket="currentTicket"
                :balance="balance"
                @close="closeTicket"
                @result="handleResult"
                @replay="handleReplay"
            />

            <!-- Modal ticket classique (autres thèmes) -->
            <ScratchTicket
                v-if="currentTicket && !isMetroTicket && !isCashTicket && !isMillionaireTicket && !isRouletteTicket"
                :ticket="currentTicket"
                :balance="balance"
                @close="closeTicket"
                @result="handleResult"
                @replay="handleReplay"
            />

            <!-- Confirmation YOLO -->
            <YoloConfirm
                v-if="showYoloConfirm"
                @confirm="confirmYolo"
                @cancel="cancelYolo"
            />

            <!-- Chaos Windows si YOLO perdu -->
            <WindowsError
                v-if="showChaos"
                @chaos-complete="handleChaosComplete"
            />

            <!-- Modal Classement -->
            <Leaderboard
                v-if="showLeaderboard"
                :current-user-id="user?.id"
                @close="showLeaderboard = false"
            />

            <!-- Panel Admin -->
            <AdminPanel
                v-if="showAdminPanel && isAdmin"
                @close="showAdminPanel = false"
            />
        </template>

        <!-- Écran de mort permanent (YOLO perdu) -->
        <div v-if="showDeathScreen" class="death-screen">
            <div class="death-content">
                <div class="death-skull">💀</div>
                <h1>COMPTE SUPPRIMÉ</h1>
                <p class="death-name">{{ deletedUserName }}</p>
                <p class="death-message">Votre compte a été définitivement supprimé.</p>
                <p class="death-message">Vous avez joué YOLO... et perdu.</p>
                <div class="death-rip">
                    <span>R.I.P.</span>
                    <span class="death-date">{{ new Date().toLocaleDateString('fr-FR') }}</span>
                </div>
                <p class="death-instruction">Fermez cet onglet pour continuer.</p>
                <div class="death-flames">🔥🔥🔥🔥🔥</div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AuthForm from './components/AuthForm.vue';
import Leaderboard from './components/Leaderboard.vue';
import AdminPanel from './components/AdminPanel.vue';
import WelcomeDoor from './components/WelcomeDoor.vue';
import InteractiveHost from './components/InteractiveHost.vue';
import ScratchTicket from './components/ScratchTicket.vue';
import MetroTicket from './components/MetroTicket.vue';
import CashTicket from './components/CashTicket.vue';
import MillionaireTicket from './components/MillionaireTicket.vue';
import RouletteTicket from './components/RouletteTicket.vue';
import YoloConfirm from './components/YoloConfirm.vue';
import WindowsError from './components/WindowsError.vue';

// État utilisateur
const user = ref(null);
const checkingAuth = ref(true);
const showLeaderboard = ref(false);
const showAdminPanel = ref(false);

// Vérifier si l'utilisateur est admin
const isAdmin = computed(() => user.value?.roles?.includes('admin'));

const doorOpened = ref(false);
const balance = ref(100);
const currentTicket = ref(null);
const showChaos = ref(false);
const showYoloConfirm = ref(false);
const pendingYoloTicket = ref(null);

// Déterminer le type de ticket à afficher
const isMetroTicket = computed(() => currentTicket.value?.theme === 'astro');
const isCashTicket = computed(() => currentTicket.value?.theme === 'cash');
const isMillionaireTicket = computed(() => currentTicket.value?.theme === 'gold');
const isRouletteTicket = computed(() => currentTicket.value?.theme === 'vegas' && currentTicket.value?.cursed);

// Récupérer le token CSRF
const getCSRFToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.content;
};

// Vérifier si déjà connecté au chargement
onMounted(async () => {
    try {
        const response = await fetch('/api/me');
        const data = await response.json();
        if (data.user) {
            user.value = data.user;
            balance.value = data.user.balance;
        }
    } catch (e) {
        console.error('Erreur vérification auth:', e);
    } finally {
        checkingAuth.value = false;
    }
});

// Gérer l'authentification
const handleAuth = (userData) => {
    user.value = userData;
    balance.value = userData.balance;
};

// Synchroniser le solde avec le serveur
const syncBalance = async () => {
    if (!user.value) return;

    try {
        await fetch('/api/balance', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken(),
            },
            body: JSON.stringify({ balance: balance.value }),
        });
    } catch (e) {
        console.error('Erreur sync balance:', e);
    }
};

// Déconnexion
const handleLogout = async () => {
    try {
        await fetch('/api/logout', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCSRFToken(),
            },
        });
        user.value = null;
        balance.value = 100;
        doorOpened.value = false;
    } catch (e) {
        console.error('Erreur déconnexion:', e);
    }
};

const openDoor = () => {
    doorOpened.value = true;
};

const handleBuyTicket = (ticketType) => {
    if (balance.value < ticketType.price) return;

    // Si c'est un ticket YOLO, demander confirmation
    if (ticketType.cursed) {
        pendingYoloTicket.value = ticketType;
        showYoloConfirm.value = true;
        return;
    }

    buyTicket(ticketType);
};

const confirmYolo = () => {
    showYoloConfirm.value = false;
    if (pendingYoloTicket.value) {
        buyTicket(pendingYoloTicket.value);
        pendingYoloTicket.value = null;
    }
};

const cancelYolo = () => {
    showYoloConfirm.value = false;
    pendingYoloTicket.value = null;
};

const buyTicket = (ticketType) => {
    balance.value -= ticketType.price;
    syncBalance(); // Sauvegarder en BDD

    currentTicket.value = {
        ...ticketType,
        scratchedPercentage: 0,
        result: null,
    };
};

const closeTicket = () => {
    currentTicket.value = null;
};

const handleReplay = (ticket) => {
    // Vérifier le solde
    if (balance.value < ticket.price) return;

    // Fermer le ticket actuel et en racheter un nouveau du même type
    currentTicket.value = null;

    // Petit délai pour que le composant se réinitialise
    // On appelle buyTicket directement pour éviter la confirmation YOLO
    setTimeout(() => {
        buyTicket(ticket);
    }, 100);
};

const handleResult = (result) => {
    if (result.won) {
        balance.value += result.amount;
    }

    // Sauvegarder le nouveau solde
    syncBalance();

    // Si 2 bombes = CHAOS
    if (result.chaos) {
        setTimeout(() => {
            currentTicket.value = null;
            showChaos.value = true;
        }, 2000);
        return;
    }

    // Ne plus fermer automatiquement - le joueur utilise les boutons Rejouer/Fermer
};

const showDeathScreen = ref(false);
const deletedUserName = ref('');

const handleChaosComplete = async () => {
    // Sauvegarder le nom avant suppression
    deletedUserName.value = user.value?.name || 'Joueur';

    // Supprimer le compte de l'utilisateur
    try {
        await fetch('/api/account', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': getCSRFToken(),
            },
        });
    } catch (e) {
        console.error('Erreur suppression compte:', e);
    }

    // Afficher l'écran de mort
    showChaos.value = false;
    showDeathScreen.value = true;
};
</script>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    min-height: 100vh;
    overflow: hidden;
}

.game-container {
    width: 100vw;
    height: 100vh;
    position: relative;
}

.loading-screen {
    position: fixed;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 20px;
    color: white;
}

.loader {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(255, 215, 0, 0.3);
    border-top-color: #ffd700;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Écran de mort YOLO */
.death-screen {
    position: fixed;
    inset: 0;
    background: linear-gradient(180deg, #000000 0%, #1a0000 50%, #000000 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    animation: deathFadeIn 1s ease-out;
}

@keyframes deathFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.death-content {
    text-align: center;
    color: #ff0000;
    animation: deathPulse 2s ease-in-out infinite;
}

@keyframes deathPulse {
    0%, 100% { filter: brightness(1); }
    50% { filter: brightness(1.3); }
}

.death-skull {
    font-size: 80px;
    margin-bottom: 15px;
    animation: skullFloat 3s ease-in-out infinite;
}

@keyframes skullFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}

.death-content h1 {
    font-size: 36px;
    font-weight: bold;
    text-shadow: 0 0 30px #ff0000, 0 0 60px #ff0000;
    margin-bottom: 15px;
    letter-spacing: 5px;
}

.death-name {
    font-size: 28px;
    color: #ffffff;
    margin-bottom: 20px;
    text-decoration: line-through;
    opacity: 0.7;
}

.death-message {
    font-size: 18px;
    color: #cc0000;
    margin-bottom: 10px;
}

.death-rip {
    margin: 25px 0;
    padding: 20px 40px;
    border: 3px solid #ff0000;
    display: inline-block;
    background: rgba(255, 0, 0, 0.1);
}

.death-rip span {
    display: block;
    font-size: 32px;
    font-weight: bold;
    color: #ff0000;
}

.death-date {
    font-size: 16px !important;
    margin-top: 8px;
    color: #888 !important;
}

.death-instruction {
    font-size: 16px;
    color: #666;
    margin-top: 30px;
    animation: blink 1s infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.death-flames {
    font-size: 30px;
    margin-top: 20px;
    animation: flamesWave 0.5s ease-in-out infinite alternate;
}

@keyframes flamesWave {
    from { transform: scaleY(1); }
    to { transform: scaleY(1.2); }
}
</style>
