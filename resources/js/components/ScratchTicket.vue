<template>
    <div class="scratch-overlay" @click.self="tryClose">
        <div class="scratch-modal" :class="['theme-' + (ticket.theme || 'vegas'), { 'cursed-modal': ticket.cursed }]">
            <button v-if="allRevealed && !hasBombChaos" class="close-btn" @click="tryClose">X</button>

            <h2>{{ ticket.name }} - Prix: {{ ticket.price }}$</h2>

            <div class="scratch-zones">
                <div
                    v-for="(icon, index) in resultIcons"
                    :key="index"
                    class="scratch-zone"
                    :class="{
                        'revealed': revealedIcons[index],
                        'matching': revealedIcons[index] && isMatching(index),
                        'bomb': revealedIcons[index] && icon.isBomb
                    }"
                >
                    <canvas
                        :ref="el => canvasRefs[index] = el"
                        @mousemove="(e) => scratchHover(e, index)"
                        @touchstart.prevent="(e) => scratchHover(e, index)"
                        @touchmove.prevent="(e) => scratchHover(e, index)"
                    ></canvas>
                    <div class="icon-content">
                        <span class="icon">{{ icon.emoji }}</span>
                        <span class="icon-name">{{ icon.name }}</span>
                    </div>
                </div>
            </div>

            <div v-if="allRevealed && !hasBombChaos" class="result-message" :class="resultClass">
                <template v-if="jackpot">
                    <span class="jackpot-text">JACKPOT!</span>
                    <span class="result-amount">+{{ ticket.jackpotGain }}$</span>
                </template>
                <template v-else-if="won">
                    <span class="win-text">GAGNE!</span>
                    <span class="result-amount">+{{ ticket.baseGain }}$</span>
                </template>
                <template v-else>
                    <span class="lose-text">PERDU</span>
                    <span class="result-amount">-{{ ticket.price }}$</span>
                </template>
            </div>

            <div v-if="allRevealed && !hasBombChaos" class="action-buttons">
                <button class="replay-btn" :class="{ disabled: !canReplay }" @click="replay">
                    🔄 Rejouer ({{ ticket.price }}$)
                </button>
                <button class="close-ticket-btn" @click="tryClose">Fermer</button>
            </div>

            <div v-if="allRevealed && hasBombChaos" class="result-message bomb-result">
                <span class="bomb-text">💣 BOOM 💣</span>
                <span class="bomb-subtext">Ça va mal se passer...</span>
            </div>

            <div class="legend">
                <span>2 identiques = {{ ticket.baseGain }}$</span>
                <span>3 identiques = JACKPOT {{ ticket.jackpotGain }}$</span>
                <span v-if="ticket.cursed" class="bomb-warning">💣 = DANGER</span>
            </div>

            <div class="progress-info">
                <span>{{ revealedCount }}/3 icônes révélées</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue';

const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
    balance: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['close', 'result', 'replay']);

const canReplay = computed(() => props.balance >= props.ticket.price);

const canvasRefs = ref([]);
const ctxRefs = ref([]);
const scratchPercentages = reactive([0, 0, 0]);
const revealedIcons = reactive([false, false, false]);
const resultIcons = ref([]);
const won = ref(false);
const jackpot = ref(false);
const resultEmitted = ref(false);
const hasBombChaos = ref(false);

// Icônes par thème
const iconsByTheme = {
    astro: [
        { emoji: '⭐', name: 'Étoile' },
        { emoji: '🌙', name: 'Lune' },
        { emoji: '🪐', name: 'Planète' },
        { emoji: '☄️', name: 'Comète' },
        { emoji: '🌟', name: 'Brillante' },
        { emoji: '🔭', name: 'Téléscope' },
        { emoji: '🛸', name: 'OVNI' },
        { emoji: '🌌', name: 'Galaxie' },
    ],
    cash: [
        { emoji: '💵', name: 'Billet' },
        { emoji: '💰', name: 'Sac' },
        { emoji: '🏦', name: 'Banque' },
        { emoji: '💳', name: 'Carte' },
        { emoji: '🤑', name: 'Riche' },
        { emoji: '💲', name: 'Dollar' },
        { emoji: '🪙', name: 'Pièce' },
        { emoji: '📈', name: 'Hausse' },
    ],
    gold: [
        { emoji: '👑', name: 'Couronne' },
        { emoji: '💎', name: 'Diamant' },
        { emoji: '🏆', name: 'Trophée' },
        { emoji: '🥇', name: 'Médaille' },
        { emoji: '💍', name: 'Bague' },
        { emoji: '⚜️', name: 'Fleur Lys' },
        { emoji: '🔱', name: 'Trident' },
        { emoji: '✨', name: 'Étincelle' },
    ],
    vegas: [
        { emoji: '🎰', name: 'Slot' },
        { emoji: '🃏', name: 'Joker' },
        { emoji: '🎲', name: 'Dés' },
        { emoji: '♠️', name: 'Pique' },
        { emoji: '♥️', name: 'Coeur' },
        { emoji: '7️⃣', name: 'Sept' },
        { emoji: '🍒', name: 'Cerise' },
        { emoji: '🔔', name: 'Cloche' },
    ],
};

// Couleurs par thème
const themeColors = {
    astro: { primary: '#1e1b4b', secondary: '#4338ca', accent: '#818cf8', scratch: '#312e81' },
    cash: { primary: '#14532d', secondary: '#22c55e', accent: '#86efac', scratch: '#166534' },
    gold: { primary: '#78350f', secondary: '#f59e0b', accent: '#fcd34d', scratch: '#92400e' },
    vegas: { primary: '#450a0a', secondary: '#dc2626', accent: '#fca5a5', scratch: '#7f1d1d' },
};

const currentTheme = computed(() => props.ticket.theme || 'vegas');
const icons = computed(() => iconsByTheme[currentTheme.value] || iconsByTheme.vegas);
const colors = computed(() => themeColors[currentTheme.value] || themeColors.vegas);

const bombIcon = { emoji: '💣', name: 'Bombe', isBomb: true };

const revealedCount = computed(() => revealedIcons.filter(r => r).length);
const allRevealed = computed(() => revealedIcons.every(r => r));

const resultClass = computed(() => {
    if (jackpot.value) return 'jackpot';
    if (won.value) return 'win';
    return 'lose';
});

const isMatching = (index) => {
    const currentIcon = resultIcons.value[index].emoji;
    if (currentIcon === '💣') return false; // Les bombes ne "match" pas pour le gain
    const matchCount = resultIcons.value.filter(i => i.emoji === currentIcon).length;
    return matchCount >= 2;
};

const generateResult = () => {
    // Si ticket YOLO (cursed), logique spéciale avec bombes
    if (props.ticket.cursed) {
        generateYoloResult();
        return;
    }

    // Logique normale pour les autres tickets
    const random = Math.random() * 100;
    const isWin = random > props.ticket.lossPercentage;
    const themeIcons = icons.value;

    if (isWin) {
        const isJackpot = Math.random() < 0.3;
        const winningIcon = themeIcons[Math.floor(Math.random() * themeIcons.length)];

        if (isJackpot) {
            resultIcons.value = [winningIcon, winningIcon, winningIcon];
            jackpot.value = true;
            won.value = true;
        } else {
            const otherIcon = themeIcons.filter(i => i.emoji !== winningIcon.emoji)[Math.floor(Math.random() * (themeIcons.length - 1))];
            const positions = [winningIcon, winningIcon, otherIcon];
            resultIcons.value = positions.sort(() => Math.random() - 0.5);
            won.value = true;
            jackpot.value = false;
        }
    } else {
        const shuffled = [...themeIcons].sort(() => Math.random() - 0.5);
        resultIcons.value = shuffled.slice(0, 3);
        won.value = false;
        jackpot.value = false;
    }
};

const generateYoloResult = () => {
    // 50% de chance d'avoir des bombes
    const hasBombs = Math.random() < 0.5;
    const themeIcons = icons.value;

    if (hasBombs) {
        // 2 bombes + 1 icône normale = CHAOS
        const randomIcon = themeIcons[Math.floor(Math.random() * themeIcons.length)];
        const positions = [bombIcon, bombIcon, randomIcon];
        resultIcons.value = positions.sort(() => Math.random() - 0.5);
        hasBombChaos.value = true;
        won.value = false;
        jackpot.value = false;
    } else {
        // Ticket normal sans bombes
        const random = Math.random() * 100;
        const isWin = random > props.ticket.lossPercentage;

        if (isWin) {
            const isJackpot = Math.random() < 0.3;
            const winningIcon = themeIcons[Math.floor(Math.random() * themeIcons.length)];

            if (isJackpot) {
                resultIcons.value = [winningIcon, winningIcon, winningIcon];
                jackpot.value = true;
                won.value = true;
            } else {
                const otherIcon = themeIcons.filter(i => i.emoji !== winningIcon.emoji)[Math.floor(Math.random() * (themeIcons.length - 1))];
                const positions = [winningIcon, winningIcon, otherIcon];
                resultIcons.value = positions.sort(() => Math.random() - 0.5);
                won.value = true;
                jackpot.value = false;
            }
        } else {
            // Perdu mais pas de bombes = perdu simple
            const shuffled = [...themeIcons].sort(() => Math.random() - 0.5);
            resultIcons.value = shuffled.slice(0, 3);
            won.value = false;
            jackpot.value = false;
        }
    }
};

const initCanvas = (index) => {
    const canvas = canvasRefs.value[index];
    if (!canvas) return;

    canvas.width = 100;
    canvas.height = 100;

    const ctx = canvas.getContext('2d');
    ctxRefs.value[index] = ctx;

    const themeColor = colors.value;

    // Fond avec couleur du thème
    ctx.fillStyle = themeColor.scratch;
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Texture avec particules
    ctx.fillStyle = themeColor.secondary;
    for (let i = 0; i < 30; i++) {
        const x = Math.random() * canvas.width;
        const y = Math.random() * canvas.height;
        ctx.fillRect(x, y, 2, 2);
    }

    // Point d'interrogation
    ctx.fillStyle = themeColor.accent;
    ctx.font = 'bold 40px Arial';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('?', canvas.width / 2, canvas.height / 2);
};

onMounted(() => {
    generateResult();
    setTimeout(() => {
        for (let i = 0; i < 3; i++) {
            initCanvas(i);
        }
    }, 50);
});

const getPosition = (e, canvas) => {
    const rect = canvas.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    return {
        x: clientX - rect.left,
        y: clientY - rect.top,
    };
};

const scratchHover = (e, index) => {
    if (revealedIcons[index]) return;

    const canvas = canvasRefs.value[index];
    const ctx = ctxRefs.value[index];
    if (!canvas || !ctx) return;

    const pos = getPosition(e, canvas);
    ctx.globalCompositeOperation = 'destination-out';
    ctx.beginPath();
    ctx.arc(pos.x, pos.y, 20, 0, Math.PI * 2);
    ctx.fill();

    updatePercentage(index);
};

const tryClose = () => {
    // On ne peut fermer que si tout est révélé ET pas de chaos en cours
    if (allRevealed.value && !hasBombChaos.value) {
        emit('close');
    }
};

const replay = () => {
    if (canReplay.value && allRevealed.value && !hasBombChaos.value) {
        emit('replay', props.ticket);
    }
};

const updatePercentage = (index) => {
    const canvas = canvasRefs.value[index];
    const ctx = ctxRefs.value[index];
    if (!canvas || !ctx) return;

    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const pixels = imageData.data;
    let transparent = 0;

    for (let i = 3; i < pixels.length; i += 4) {
        if (pixels[i] === 0) transparent++;
    }

    const total = pixels.length / 4;
    scratchPercentages[index] = (transparent / total) * 100;

    // Révéler si plus de 60% gratté
    if (scratchPercentages[index] >= 60 && !revealedIcons[index]) {
        revealedIcons[index] = true;
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Vérifier si toutes les icônes sont révélées
        if (allRevealed.value && !resultEmitted.value) {
            resultEmitted.value = true;

            if (hasBombChaos.value) {
                // CHAOS - 2 bombes détectées
                emit('result', {
                    won: false,
                    jackpot: false,
                    amount: 0,
                    icons: resultIcons.value,
                    chaos: true,
                });
            } else {
                const winAmount = jackpot.value
                    ? props.ticket.jackpotGain
                    : (won.value ? props.ticket.baseGain : 0);

                emit('result', {
                    won: won.value,
                    jackpot: jackpot.value,
                    amount: winAmount,
                    icons: resultIcons.value,
                    chaos: false,
                });
            }
        }
    }
};
</script>

<style scoped>
.scratch-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 100;
}

.scratch-modal {
    padding: 30px;
    border-radius: 20px;
    text-align: center;
    position: relative;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    min-width: 420px;
    border: 3px solid transparent;
}

/* Thème Astro (Métro) - Espace et étoiles */
.scratch-modal.theme-astro {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
    border-color: #818cf8;
    box-shadow: 0 20px 60px rgba(99, 102, 241, 0.3);
}

.scratch-modal.theme-astro h2 {
    color: #c7d2fe;
}

.scratch-modal.theme-astro::before {
    content: '✨';
    position: absolute;
    top: 10px;
    left: 20px;
    font-size: 24px;
}

.scratch-modal.theme-astro::after {
    content: '🌙';
    position: absolute;
    top: 10px;
    right: 50px;
    font-size: 24px;
}

/* Thème Cash (Bus) - Argent et billets */
.scratch-modal.theme-cash {
    background: linear-gradient(135deg, #14532d 0%, #166534 100%);
    border-color: #86efac;
    box-shadow: 0 20px 60px rgba(34, 197, 94, 0.3);
}

.scratch-modal.theme-cash h2 {
    color: #bbf7d0;
}

.scratch-modal.theme-cash::before {
    content: '💵';
    position: absolute;
    top: 10px;
    left: 20px;
    font-size: 24px;
}

.scratch-modal.theme-cash::after {
    content: '💰';
    position: absolute;
    top: 10px;
    right: 50px;
    font-size: 24px;
}

/* Thème Gold (Train) - Or et luxe */
.scratch-modal.theme-gold {
    background: linear-gradient(135deg, #78350f 0%, #92400e 100%);
    border-color: #fcd34d;
    box-shadow: 0 20px 60px rgba(245, 158, 11, 0.3);
}

.scratch-modal.theme-gold h2 {
    color: #fef3c7;
}

.scratch-modal.theme-gold::before {
    content: '👑';
    position: absolute;
    top: 10px;
    left: 20px;
    font-size: 24px;
}

.scratch-modal.theme-gold::after {
    content: '💎';
    position: absolute;
    top: 10px;
    right: 50px;
    font-size: 24px;
}

/* Thème Vegas (Loterie) - Casino et néons */
.scratch-modal.theme-vegas {
    background: linear-gradient(135deg, #450a0a 0%, #7f1d1d 100%);
    border-color: #fca5a5;
    box-shadow: 0 20px 60px rgba(220, 38, 38, 0.3);
}

.scratch-modal.theme-vegas h2 {
    color: #fecaca;
}

.scratch-modal.theme-vegas::before {
    content: '🎰';
    position: absolute;
    top: 10px;
    left: 20px;
    font-size: 24px;
}

.scratch-modal.theme-vegas::after {
    content: '🎲';
    position: absolute;
    top: 10px;
    right: 50px;
    font-size: 24px;
}

.scratch-modal.cursed-modal {
    border: 3px solid #a855f7;
    box-shadow: 0 0 40px rgba(168, 85, 247, 0.4);
    animation: cursedPulse 2s infinite;
}

@keyframes cursedPulse {
    0%, 100% { box-shadow: 0 0 40px rgba(168, 85, 247, 0.4); }
    50% { box-shadow: 0 0 60px rgba(168, 85, 247, 0.7); }
}

.close-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #ff4757;
    border: none;
    color: white;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    cursor: pointer;
    font-weight: bold;
    font-size: 16px;
}

h2 {
    color: #ffd700;
    margin-bottom: 25px;
}

.scratch-zones {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 20px;
}

.scratch-zone {
    width: 100px;
    height: 100px;
    position: relative;
    border-radius: 15px;
    overflow: hidden;
    border: 3px solid rgba(255, 255, 255, 0.3);
    transition: all 0.3s;
}

.scratch-zone canvas {
    position: absolute;
    top: 0;
    left: 0;
    cursor: crosshair;
    z-index: 2;
    border-radius: 12px;
}

.scratch-zone .icon-content {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
}

.scratch-zone.revealed {
    border-color: rgba(255, 255, 255, 0.5);
}

.scratch-zone.revealed.matching {
    border-color: #ffd700;
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
    animation: glow 1s infinite alternate;
}

.scratch-zone.revealed.bomb {
    border-color: #ff0000;
    box-shadow: 0 0 25px rgba(255, 0, 0, 0.7);
    animation: bombPulse 0.5s infinite alternate;
}

@keyframes bombPulse {
    from { box-shadow: 0 0 20px rgba(255, 0, 0, 0.5); }
    to { box-shadow: 0 0 35px rgba(255, 0, 0, 0.9); }
}

@keyframes glow {
    from { box-shadow: 0 0 20px rgba(255, 215, 0, 0.5); }
    to { box-shadow: 0 0 30px rgba(255, 215, 0, 0.8); }
}

.icon {
    font-size: 36px;
}

.icon-name {
    font-size: 11px;
    color: #888;
    margin-top: 5px;
}

.result-message {
    padding: 15px 30px;
    border-radius: 15px;
    margin-bottom: 15px;
    animation: popIn 0.3s ease-out;
}

@keyframes popIn {
    from { transform: scale(0.8); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.result-message.jackpot {
    background: linear-gradient(135deg, #ffd700 0%, #ffaa00 100%);
}

.result-message.win {
    background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
}

.result-message.lose {
    background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
}

.result-message.bomb-result {
    background: linear-gradient(135deg, #7f1d1d 0%, #450a0a 100%);
    animation: bombShake 0.1s infinite;
}

@keyframes bombShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-3px); }
    75% { transform: translateX(3px); }
}

.bomb-text {
    font-size: 28px;
    font-weight: bold;
    color: #ff4444;
    display: block;
    text-shadow: 0 0 10px #ff0000;
}

.bomb-subtext {
    font-size: 16px;
    color: #ffaaaa;
    display: block;
}

.jackpot-text {
    font-size: 28px;
    font-weight: bold;
    color: #1a1a2e;
    display: block;
    animation: shake 0.5s infinite;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.win-text {
    font-size: 24px;
    font-weight: bold;
    color: #1a1a2e;
    display: block;
}

.lose-text {
    font-size: 24px;
    font-weight: bold;
    color: white;
    display: block;
}

.result-amount {
    font-size: 20px;
    font-weight: bold;
    color: #1a1a2e;
    display: block;
}

.result-message.lose .result-amount {
    color: white;
}

.legend {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 15px;
    color: #888;
    font-size: 13px;
}

.bomb-warning {
    color: #ff4444;
    font-weight: bold;
}

.progress-info {
    margin-top: 10px;
    color: #ffd700;
    font-size: 14px;
}

.action-buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 20px;
}

.replay-btn, .close-ticket-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
}

.replay-btn {
    background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
    color: #1a1a2e;
}

.replay-btn:hover:not(.disabled) {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(74, 222, 128, 0.4);
}

.replay-btn.disabled {
    background: #555;
    color: #888;
    cursor: not-allowed;
}

.close-ticket-btn {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    color: white;
}

.close-ticket-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(107, 114, 128, 0.4);
}
</style>
