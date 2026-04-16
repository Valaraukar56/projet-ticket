<template>
    <div class="lobby">
        <!-- Balance display -->
        <div class="balance-display">
            <span class="balance-icon">$</span>
            <span class="balance-amount">{{ animatedBalance }}</span>
        </div>

        <!-- Host character -->
        <div class="host-area">
            <div class="host" :class="{ talking: showTickets }" @click="toggleTickets">
                <div class="host-body">
                    <div class="host-head">
                        <div class="host-eyes">
                            <div class="eye"></div>
                            <div class="eye"></div>
                        </div>
                        <div class="host-mouth" :class="{ open: showTickets }"></div>
                    </div>
                    <div class="host-torso"></div>
                    <div class="host-arms">
                        <div class="arm left"></div>
                        <div class="arm right"></div>
                    </div>
                </div>
                <div class="speech-bubble" v-if="!showTickets">
                    <p>Bienvenue! Cliquez sur moi pour voir les tickets!</p>
                </div>
            </div>
        </div>

        <!-- Ticket selection -->
        <transition name="slide">
            <div class="ticket-selection" v-if="showTickets">
                <h2>Choisissez votre ticket</h2>
                <div class="tickets-grid">
                    <div
                        v-for="ticket in ticketTypes"
                        :key="ticket.id"
                        class="ticket-option"
                        :class="{ disabled: balance < ticket.price }"
                        @click="selectTicket(ticket)"
                    >
                        <div class="ticket-risk" :style="{ background: ticket.color }">
                            {{ ticket.lossPercentage }}% risque
                        </div>
                        <div class="ticket-info">
                            <span class="ticket-name">{{ ticket.name }}</span>
                            <span class="ticket-price">Prix: {{ ticket.price }}$</span>
                            <span class="ticket-gain-base">2 icônes: {{ ticket.baseGain }}$</span>
                            <span class="ticket-gain-jackpot">3 icônes: {{ ticket.jackpotGain }}$</span>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';

const props = defineProps({
    balance: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['buy-ticket']);

const showTickets = ref(false);
const animatedBalance = ref(props.balance);

const ticketTypes = [
    { id: 1, name: 'Prudent', price: 500, lossPercentage: 30, baseGain: 650, jackpotGain: 1500, color: '#4ade80' },
    { id: 2, name: 'Equilibre', price: 1000, lossPercentage: 50, baseGain: 1300, jackpotGain: 4000, color: '#fbbf24' },
    { id: 3, name: 'Risque', price: 1500, lossPercentage: 70, baseGain: 2000, jackpotGain: 8000, color: '#f87171' },
    { id: 4, name: 'YOLO', price: 3000, lossPercentage: 90, baseGain: 4500, jackpotGain: 30000, color: '#a855f7' },
];

watch(() => props.balance, (newVal) => {
    const diff = newVal - animatedBalance.value;
    const steps = 20;
    const increment = diff / steps;
    let current = 0;

    const animate = () => {
        if (current < steps) {
            animatedBalance.value = Math.round(animatedBalance.value + increment);
            current++;
            requestAnimationFrame(animate);
        } else {
            animatedBalance.value = newVal;
        }
    };
    animate();
});

const toggleTickets = () => {
    showTickets.value = !showTickets.value;
};

const selectTicket = (ticket) => {
    if (props.balance >= ticket.price) {
        emit('buy-ticket', ticket);
        showTickets.value = false;
    }
};
</script>

<style scoped>
.lobby {
    width: 100%;
    height: 100%;
    background: linear-gradient(180deg, #1e1e3f 0%, #2d2d5a 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    overflow-y: auto;
}

.balance-display {
    position: absolute;
    top: 20px;
    right: 20px;
    background: linear-gradient(135deg, #ffd700 0%, #ffaa00 100%);
    padding: 15px 30px;
    border-radius: 50px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 5px 20px rgba(255, 215, 0, 0.4);
}

.balance-icon {
    font-size: 28px;
    font-weight: bold;
    color: #1a1a2e;
}

.balance-amount {
    font-size: 28px;
    font-weight: bold;
    color: #1a1a2e;
    min-width: 100px;
}

.host-area {
    flex: 0 0 auto;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px 0;
}

.host {
    cursor: pointer;
    position: relative;
    transition: transform 0.3s;
}

.host:hover {
    transform: scale(1.05);
}

.host-body {
    position: relative;
}

.host-head {
    width: 80px;
    height: 80px;
    background: #ffdbac;
    border-radius: 50%;
    position: relative;
    margin: 0 auto;
}

.host-eyes {
    display: flex;
    justify-content: center;
    gap: 20px;
    padding-top: 25px;
}

.eye {
    width: 12px;
    height: 12px;
    background: #333;
    border-radius: 50%;
    animation: blink 3s infinite;
}

@keyframes blink {
    0%, 90%, 100% { transform: scaleY(1); }
    95% { transform: scaleY(0.1); }
}

.host-mouth {
    width: 20px;
    height: 10px;
    background: #c0392b;
    border-radius: 0 0 10px 10px;
    margin: 10px auto 0;
    transition: height 0.2s;
}

.host-mouth.open {
    height: 15px;
    border-radius: 0 0 15px 15px;
}

.host-torso {
    width: 100px;
    height: 120px;
    background: linear-gradient(180deg, #e74c3c 0%, #c0392b 100%);
    border-radius: 20px 20px 10px 10px;
    margin-top: -10px;
}

.host-arms {
    position: absolute;
    top: 90px;
    width: 100%;
    display: flex;
    justify-content: space-between;
}

.arm {
    width: 25px;
    height: 80px;
    background: #ffdbac;
    border-radius: 10px;
}

.arm.left {
    transform: rotate(20deg);
    transform-origin: top center;
}

.arm.right {
    transform: rotate(-20deg);
    transform-origin: top center;
}

.host.talking .arm.right {
    animation: wave 0.5s infinite alternate;
}

@keyframes wave {
    from { transform: rotate(-20deg); }
    to { transform: rotate(-40deg); }
}

.speech-bubble {
    position: absolute;
    top: -60px;
    left: 50%;
    transform: translateX(-50%);
    background: white;
    padding: 15px 20px;
    border-radius: 20px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    white-space: nowrap;
    color: #333;
}

.speech-bubble::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    border-left: 10px solid transparent;
    border-right: 10px solid transparent;
    border-top: 10px solid white;
}

.ticket-selection {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    padding: 20px;
    border-radius: 20px;
    margin-bottom: 30px;
    max-height: 70vh;
    overflow-y: auto;
}

.ticket-selection h2 {
    color: #ffd700;
    text-align: center;
    margin-bottom: 20px;
}

.tickets-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

@media (max-height: 700px) {
    .tickets-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }
}

.ticket-option {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s;
    border: 2px solid transparent;
}

.ticket-option:hover:not(.disabled) {
    transform: translateY(-5px);
    border-color: #ffd700;
    box-shadow: 0 10px 30px rgba(255, 215, 0, 0.3);
}

.ticket-option.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.ticket-risk {
    padding: 8px 15px;
    border-radius: 20px;
    color: #000;
    font-weight: bold;
    text-align: center;
    margin-bottom: 10px;
}

.ticket-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
    color: white;
}

.ticket-name {
    font-size: 18px;
    font-weight: bold;
}

.ticket-price {
    color: #ff6b6b;
    font-size: 14px;
    font-weight: bold;
}

.ticket-gain-base {
    color: #4ade80;
    font-size: 13px;
}

.ticket-gain-jackpot {
    color: #ffd700;
    font-size: 14px;
    font-weight: bold;
}

.slide-enter-active,
.slide-leave-active {
    transition: all 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
    opacity: 0;
    transform: translateY(30px);
}
</style>
