<template>
    <div class="game-container">
        <!-- Écran d'accueil avec porte -->
        <WelcomeDoor v-if="!doorOpened" @open="openDoor" />

        <!-- Lobby avec le bonhomme hôte -->
        <Lobby v-else :balance="balance" @buy-ticket="buyTicket" />

        <!-- Modal ticket à gratter -->
        <ScratchTicket
            v-if="currentTicket"
            :ticket="currentTicket"
            @close="closeTicket"
            @result="handleResult"
        />

        <!-- Chaos Windows si YOLO perdu -->
        <WindowsError
            v-if="showChaos"
            @chaos-complete="handleChaosComplete"
        />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import WelcomeDoor from './components/WelcomeDoor.vue';
import Lobby from './components/Lobby.vue';
import ScratchTicket from './components/ScratchTicket.vue';
import WindowsError from './components/WindowsError.vue';

const doorOpened = ref(false);
const balance = ref(100);
const currentTicket = ref(null);
const showChaos = ref(false);

const openDoor = () => {
    doorOpened.value = true;
};

const buyTicket = (ticketType) => {
    if (balance.value >= ticketType.price) {
        balance.value -= ticketType.price;
        currentTicket.value = {
            ...ticketType,
            scratchedPercentage: 0,
            result: null,
        };
    }
};

const closeTicket = () => {
    currentTicket.value = null;
};

const handleResult = (result) => {
    if (result.won) {
        balance.value += result.amount;
    }

    // Si YOLO perdu = CHAOS
    if (currentTicket.value?.cursed && !result.won) {
        setTimeout(() => {
            currentTicket.value = null;
            showChaos.value = true;
        }, 1500);
        return;
    }

    // Plus de temps pour admirer le jackpot
    const delay = result.jackpot ? 3500 : 2000;
    setTimeout(() => {
        currentTicket.value = null;
    }, delay);
};

const handleChaosComplete = () => {
    // Fermer la page / recharger
    window.close();
    // Si window.close ne marche pas (souvent bloqué par les navigateurs)
    setTimeout(() => {
        location.href = 'about:blank';
    }, 100);
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
</style>
