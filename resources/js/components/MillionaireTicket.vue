<template>
    <div class="ticket-overlay" @click.self="tryClose">
        <div class="millionaire-ticket">
            <!-- Header du ticket -->
            <div class="ticket-header">
                <div class="ticket-logo">
                    <span class="crown-icon">👑</span>
                    <div class="title-wrapper">
                        <span class="ticket-title">MILLIONNAIRE</span>
                        <span class="ticket-subtitle">Devenez le prochain millionnaire !</span>
                    </div>
                    <span class="crown-icon">👑</span>
                </div>
            </div>

            <!-- Zone principale - Grille de gains -->
            <div class="main-zone">
                <div class="zone-title">💎 GRATTEZ LES 12 CASES 💎</div>
                <div class="gains-grid">
                    <div
                        v-for="(box, index) in boxes"
                        :key="index"
                        class="gain-box"
                        :class="{
                            'revealed': box.revealed,
                            'winning': box.revealed && box.isWinning,
                            'multiplier': box.revealed && box.isMultiplier
                        }"
                    >
                        <canvas
                            :ref="el => boxCanvases[index] = el"
                            class="box-canvas"
                            @mousemove="(e) => scratchBox(e, index)"
                            @touchmove.prevent="(e) => scratchBox(e, index)"
                        ></canvas>
                        <div class="box-content">
                            <span v-if="box.isMultiplier" class="multiplier-symbol">{{ box.value }}</span>
                            <span v-else class="gain-value">{{ box.value }}€</span>
                        </div>
                    </div>
                </div>
                <div class="game-rules">
                    3 montants identiques = Vous gagnez ce montant ! | 🌟 = Multiplicateur x2
                </div>
            </div>

            <!-- Zone Bonus Coffre-fort -->
            <div class="bonus-zone">
                <div class="bonus-title">🔐 BONUS COFFRE-FORT 🔐</div>
                <div class="safe-container" :class="{ 'revealed': bonusRevealed, 'won': bonusRevealed && bonusWon }">
                    <canvas
                        ref="bonusCanvas"
                        class="bonus-canvas"
                        @mousemove="scratchBonus"
                        @touchmove.prevent="scratchBonus"
                    ></canvas>
                    <div class="safe-content">
                        <div class="safe-door" :class="{ 'open': bonusRevealed }">
                            <span class="safe-icon">🔒</span>
                        </div>
                        <div v-if="bonusRevealed" class="safe-result">
                            <span v-if="bonusWon" class="bonus-win">{{ bonusAmount }}€</span>
                            <span v-else class="bonus-lose">❌</span>
                        </div>
                    </div>
                </div>
                <div class="bonus-rules">Ouvrez le coffre pour découvrir un gain surprise !</div>
            </div>

            <!-- Résultat final -->
            <div v-if="allRevealed" class="result-zone" :class="{ 'winner': totalGain > 0 }">
                <div v-if="totalGain > 0" class="win-message">
                    <span class="win-text">🏆 FÉLICITATIONS ! 🏆</span>
                    <span class="win-amount">Vous gagnez {{ totalGain }}€</span>
                    <span v-if="hasMultiplier" class="multiplier-info">(Multiplicateur x2 appliqué !)</span>
                </div>
                <div v-else class="lose-message">
                    <span class="lose-text">Pas de gain cette fois</span>
                    <span class="lose-sub">La fortune vous sourira bientôt !</span>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div v-if="allRevealed" class="action-buttons">
                <button class="replay-btn" :class="{ disabled: !canReplay }" @click="replay">
                    🔄 Nouveau ticket ({{ ticket.price }}$)
                </button>
                <button class="close-btn" @click="$emit('close')">Fermer</button>
            </div>

            <!-- Décorations -->
            <div class="gold-corners">
                <div class="corner tl"></div>
                <div class="corner tr"></div>
                <div class="corner bl"></div>
                <div class="corner br"></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue';

const props = defineProps({
    ticket: { type: Object, required: true },
    balance: { type: Number, required: true },
});

const emit = defineEmits(['close', 'result', 'replay']);

// Cases principales (12 cases)
const boxes = reactive([]);
const boxCanvases = ref([]);
const boxCtxs = ref([]);
const boxScratchCount = reactive(Array(12).fill(0));

// Bonus
const bonusCanvas = ref(null);
const bonusCtx = ref(null);
const bonusScratchCount = ref(0);
const bonusRevealed = ref(false);
const bonusWon = ref(false);
const bonusAmount = ref(0);

const resultEmitted = ref(false);
const hasMultiplier = ref(false);
const baseWinAmount = ref(0);

const allBoxesRevealed = computed(() => boxes.every(b => b.revealed));
const allRevealed = computed(() => allBoxesRevealed.value && bonusRevealed.value);

const totalGain = computed(() => {
    let total = 0;

    // Gain principal (3 montants identiques)
    if (baseWinAmount.value > 0) {
        total = hasMultiplier.value ? baseWinAmount.value * 2 : baseWinAmount.value;
    }

    // Bonus coffre
    if (bonusWon.value) {
        total += bonusAmount.value;
    }

    return total;
});

const canReplay = computed(() => props.balance >= props.ticket.price);

// Montants possibles
const possibleAmounts = [5, 10, 20, 50, 100, 200, 500, 1000, 5000, 10000, 50000, 100000];

const generateTicket = () => {
    const rand = Math.random() * 100;
    let hasWin = false;
    let winAmount = 0;

    // Probabilités
    if (rand < 60) {
        hasWin = false;
    } else if (rand < 75) {
        hasWin = true;
        winAmount = 5;
    } else if (rand < 85) {
        hasWin = true;
        winAmount = 10;
    } else if (rand < 91) {
        hasWin = true;
        winAmount = 20;
    } else if (rand < 95) {
        hasWin = true;
        winAmount = 50;
    } else if (rand < 97) {
        hasWin = true;
        winAmount = 100;
    } else if (rand < 98.5) {
        hasWin = true;
        winAmount = 200;
    } else if (rand < 99.3) {
        hasWin = true;
        winAmount = 500;
    } else if (rand < 99.7) {
        hasWin = true;
        winAmount = 1000;
    } else if (rand < 99.9) {
        hasWin = true;
        winAmount = 5000;
    } else {
        hasWin = true;
        winAmount = 50000;
    }

    // Générer les 12 cases
    boxes.length = 0;

    // Ajouter un multiplicateur (20% de chance si gain)
    const addMultiplier = hasWin && Math.random() < 0.2;
    hasMultiplier.value = addMultiplier;
    baseWinAmount.value = hasWin ? winAmount : 0;

    if (hasWin) {
        // 3 cases avec le montant gagnant
        for (let i = 0; i < 3; i++) {
            boxes.push({ value: winAmount, revealed: false, isWinning: true, isMultiplier: false });
        }

        // 1 multiplicateur si applicable
        if (addMultiplier) {
            boxes.push({ value: '🌟x2', revealed: false, isWinning: false, isMultiplier: true });
        }

        // Remplir le reste avec des montants différents
        const remainingCount = addMultiplier ? 8 : 9;
        const otherAmounts = possibleAmounts.filter(a => a !== winAmount);

        for (let i = 0; i < remainingCount; i++) {
            const randomAmount = otherAmounts[Math.floor(Math.random() * otherAmounts.length)];
            boxes.push({ value: randomAmount, revealed: false, isWinning: false, isMultiplier: false });
        }
    } else {
        // Pas de gain - tous les montants différents (max 2 de chaque)
        const amounts = [];
        const usedTwice = new Set();

        for (let i = 0; i < 12; i++) {
            let amount;
            do {
                amount = possibleAmounts[Math.floor(Math.random() * possibleAmounts.length)];
            } while (amounts.filter(a => a === amount).length >= 2);

            amounts.push(amount);
        }

        amounts.forEach(amount => {
            boxes.push({ value: amount, revealed: false, isWinning: false, isMultiplier: false });
        });
    }

    // Mélanger les cases
    boxes.sort(() => Math.random() - 0.5);

    // Bonus coffre (30% de chance de gagner)
    if (Math.random() < 0.3) {
        bonusWon.value = true;
        bonusAmount.value = [10, 20, 50, 100, 200][Math.floor(Math.random() * 5)];
    } else {
        bonusWon.value = false;
        bonusAmount.value = 0;
    }
};

// Canvas - Cases principales
const initBoxCanvas = (index) => {
    const canvas = boxCanvases.value[index];
    if (!canvas) return;

    canvas.width = 90;
    canvas.height = 70;

    const ctx = canvas.getContext('2d');
    boxCtxs.value[index] = ctx;

    // Fond doré luxueux
    const gradient = ctx.createLinearGradient(0, 0, 90, 70);
    gradient.addColorStop(0, '#ffd700');
    gradient.addColorStop(0.3, '#ffec80');
    gradient.addColorStop(0.5, '#ffd700');
    gradient.addColorStop(0.7, '#daa520');
    gradient.addColorStop(1, '#b8860b');

    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, 90, 70);

    // Motif diamant
    ctx.fillStyle = 'rgba(255,255,255,0.3)';
    ctx.font = '20px Arial';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('💎', 45, 35);

    // Texture brillante
    ctx.fillStyle = 'rgba(255,255,255,0.2)';
    for (let i = 0; i < 10; i++) {
        ctx.fillRect(Math.random() * 90, Math.random() * 70, 2, 2);
    }
};

// Canvas - Bonus coffre
const initBonusCanvas = () => {
    const canvas = bonusCanvas.value;
    if (!canvas) return;

    canvas.width = 140;
    canvas.height = 100;

    const ctx = canvas.getContext('2d');
    bonusCtx.value = ctx;

    // Fond métallique
    const gradient = ctx.createLinearGradient(0, 0, 140, 100);
    gradient.addColorStop(0, '#4a4a4a');
    gradient.addColorStop(0.3, '#6a6a6a');
    gradient.addColorStop(0.5, '#5a5a5a');
    gradient.addColorStop(0.7, '#4a4a4a');
    gradient.addColorStop(1, '#3a3a3a');

    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, 140, 100);

    // Texture
    ctx.fillStyle = 'rgba(255,255,255,0.1)';
    for (let i = 0; i < 20; i++) {
        ctx.fillRect(Math.random() * 140, Math.random() * 100, 1, 1);
    }

    // Symbole coffre
    ctx.fillStyle = 'rgba(255, 215, 0, 0.4)';
    ctx.font = 'bold 40px Arial';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('🔐', 70, 50);
};

// Grattage
const scratchBox = (e, index) => {
    if (boxes[index].revealed) return;

    const canvas = boxCanvases.value[index];
    const ctx = boxCtxs.value[index];
    if (!canvas || !ctx) return;

    const rect = canvas.getBoundingClientRect();
    const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
    const y = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top;

    ctx.globalCompositeOperation = 'destination-out';
    ctx.beginPath();
    ctx.arc(x, y, 18, 0, Math.PI * 2);
    ctx.fill();

    boxScratchCount[index]++;

    if (boxScratchCount[index] >= 6) {
        boxes[index].revealed = true;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        checkResult();
    }
};

const scratchBonus = (e) => {
    if (bonusRevealed.value) return;

    const canvas = bonusCanvas.value;
    const ctx = bonusCtx.value;
    if (!canvas || !ctx) return;

    const rect = canvas.getBoundingClientRect();
    const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
    const y = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top;

    ctx.globalCompositeOperation = 'destination-out';
    ctx.beginPath();
    ctx.arc(x, y, 22, 0, Math.PI * 2);
    ctx.fill();

    bonusScratchCount.value++;

    if (bonusScratchCount.value >= 8) {
        bonusRevealed.value = true;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        checkResult();
    }
};

const checkResult = () => {
    if (allRevealed.value && !resultEmitted.value) {
        resultEmitted.value = true;
        emit('result', {
            won: totalGain.value > 0,
            amount: totalGain.value,
            jackpot: totalGain.value >= 1000,
        });
    }
};

const tryClose = () => {
    if (allRevealed.value) {
        emit('close');
    }
};

const replay = () => {
    if (canReplay.value && allRevealed.value) {
        emit('replay', props.ticket);
    }
};

onMounted(() => {
    generateTicket();
    setTimeout(() => {
        for (let i = 0; i < 12; i++) initBoxCanvas(i);
        initBonusCanvas();
    }, 50);
});
</script>

<style scoped>
.ticket-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 100;
    padding: 10px;
    overflow: auto;
}

.millionaire-ticket {
    width: 95vw;
    max-width: 700px;
    max-height: 95vh;
    overflow-y: auto;
    background: linear-gradient(145deg, #1a1a1a 0%, #2d2d2d 50%, #1a1a1a 100%);
    border: 5px solid #ffd700;
    border-radius: 20px;
    padding: 25px;
    position: relative;
    box-shadow:
        0 0 50px rgba(255, 215, 0, 0.4),
        inset 0 0 100px rgba(255, 215, 0, 0.05);
}

/* Coins dorés */
.gold-corners .corner {
    position: absolute;
    width: 40px;
    height: 40px;
    border: 4px solid #ffd700;
}

.corner.tl { top: 10px; left: 10px; border-right: none; border-bottom: none; border-radius: 10px 0 0 0; }
.corner.tr { top: 10px; right: 10px; border-left: none; border-bottom: none; border-radius: 0 10px 0 0; }
.corner.bl { bottom: 10px; left: 10px; border-right: none; border-top: none; border-radius: 0 0 0 10px; }
.corner.br { bottom: 10px; right: 10px; border-left: none; border-top: none; border-radius: 0 0 10px 0; }

/* Header */
.ticket-header {
    text-align: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 3px solid rgba(255, 215, 0, 0.4);
}

.ticket-logo {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.crown-icon {
    font-size: 36px;
    animation: crownBounce 2s ease-in-out infinite;
}

@keyframes crownBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

.title-wrapper {
    display: flex;
    flex-direction: column;
}

.ticket-title {
    font-size: 32px;
    font-weight: bold;
    background: linear-gradient(135deg, #ffd700, #ffec80, #ffd700);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-shadow: none;
    letter-spacing: 4px;
}

.ticket-subtitle {
    font-size: 12px;
    color: rgba(255, 215, 0, 0.7);
    letter-spacing: 2px;
}

/* Zone principale */
.main-zone {
    background: rgba(255, 215, 0, 0.08);
    border: 2px solid rgba(255, 215, 0, 0.3);
    border-radius: 15px;
    padding: 15px;
    margin-bottom: 15px;
}

.zone-title {
    text-align: center;
    color: #ffd700;
    font-weight: bold;
    font-size: 16px;
    margin-bottom: 15px;
    letter-spacing: 2px;
}

.gains-grid {
    display: grid;
    grid-template-columns: repeat(4, 90px);
    gap: 10px;
    justify-content: center;
}

.gain-box {
    width: 90px;
    height: 70px;
    position: relative;
    cursor: pointer;
    border-radius: 10px;
    overflow: hidden;
    border: 2px solid rgba(255, 215, 0, 0.4);
    transition: all 0.3s;
}

.gain-box.revealed {
    border-color: rgba(255, 255, 255, 0.3);
}

.gain-box.winning {
    border-color: #ffd700;
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.6);
    animation: winGlow 1s infinite alternate;
}

.gain-box.multiplier {
    border-color: #ff6b6b;
    box-shadow: 0 0 20px rgba(255, 107, 107, 0.6);
}

@keyframes winGlow {
    from { box-shadow: 0 0 20px rgba(255, 215, 0, 0.6); }
    to { box-shadow: 0 0 35px rgba(255, 215, 0, 0.9); }
}

.box-canvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 90px;
    height: 70px;
    z-index: 2;
    cursor: pointer;
}

.box-content {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #2a2a2a, #1a1a1a);
}

.gain-value {
    font-size: 16px;
    font-weight: bold;
    color: #ffd700;
}

.multiplier-symbol {
    font-size: 20px;
}

.game-rules {
    text-align: center;
    color: rgba(255, 255, 255, 0.6);
    font-size: 11px;
    margin-top: 12px;
    padding-top: 10px;
    border-top: 1px solid rgba(255, 215, 0, 0.2);
}

/* Zone Bonus */
.bonus-zone {
    background: linear-gradient(135deg, rgba(74, 74, 74, 0.3), rgba(50, 50, 50, 0.3));
    border: 2px solid rgba(255, 215, 0, 0.3);
    border-radius: 15px;
    padding: 15px;
    margin-bottom: 15px;
    text-align: center;
}

.bonus-title {
    color: #ffd700;
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 12px;
    letter-spacing: 2px;
}

.safe-container {
    width: 140px;
    height: 100px;
    margin: 0 auto;
    position: relative;
    cursor: pointer;
    border-radius: 12px;
    overflow: hidden;
    border: 3px solid #5a5a5a;
    transition: all 0.3s;
}

.safe-container.won {
    border-color: #ffd700;
    box-shadow: 0 0 30px rgba(255, 215, 0, 0.5);
}

.bonus-canvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 140px;
    height: 100px;
    z-index: 2;
    cursor: pointer;
}

.safe-content {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #3a3a3a, #2a2a2a);
}

.safe-door {
    transition: all 0.5s;
}

.safe-door.open {
    opacity: 0;
    transform: scale(0);
}

.safe-icon {
    font-size: 40px;
}

.safe-result {
    position: absolute;
}

.bonus-win {
    font-size: 28px;
    font-weight: bold;
    color: #ffd700;
    text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
}

.bonus-lose {
    font-size: 36px;
}

.bonus-rules {
    color: rgba(255, 255, 255, 0.5);
    font-size: 11px;
    margin-top: 10px;
}

/* Résultat */
.result-zone {
    text-align: center;
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 15px;
    background: rgba(0, 0, 0, 0.3);
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.result-zone.winner {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 215, 0, 0.1));
    border: 2px solid #ffd700;
}

.win-message {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.win-text {
    font-size: 22px;
    color: #ffd700;
}

.win-amount {
    font-size: 32px;
    font-weight: bold;
    color: #fff;
    text-shadow: 0 0 30px rgba(255, 215, 0, 0.5);
}

.multiplier-info {
    font-size: 14px;
    color: #ff6b6b;
    font-weight: bold;
}

.lose-message {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.lose-text {
    font-size: 18px;
    color: rgba(255, 255, 255, 0.7);
}

.lose-sub {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.5);
}

/* Boutons */
.action-buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
}

.replay-btn, .close-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
}

.replay-btn {
    background: linear-gradient(135deg, #ffd700, #daa520);
    color: #1a1a1a;
}

.replay-btn:hover:not(.disabled) {
    transform: translateY(-2px);
    box-shadow: 0 5px 25px rgba(255, 215, 0, 0.5);
}

.replay-btn.disabled {
    background: #444;
    color: #888;
    cursor: not-allowed;
}

.close-btn {
    background: linear-gradient(135deg, #4a4a4a, #3a3a3a);
    color: white;
    border: 1px solid #5a5a5a;
}

.close-btn:hover {
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 600px) {
    .millionaire-ticket {
        padding: 15px;
    }

    .ticket-title {
        font-size: 24px;
        letter-spacing: 2px;
    }

    .crown-icon {
        font-size: 28px;
    }

    .gains-grid {
        grid-template-columns: repeat(3, 80px);
        gap: 8px;
    }

    .gain-box {
        width: 80px;
        height: 60px;
    }

    .box-canvas {
        width: 80px;
        height: 60px;
    }

    .gain-value {
        font-size: 14px;
    }
}
</style>
