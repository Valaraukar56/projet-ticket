<template>
    <div class="ticket-overlay" @click.self="tryClose">
        <div class="cash-ticket">
            <!-- Header du ticket -->
            <div class="ticket-header">
                <div class="cash-logo">
                    <span class="cash-icon">$</span>
                    <span class="ticket-title">CASH</span>
                </div>
                <div class="ticket-subtitle">Grattez et gagnez jusqu'à 500 000€</div>
            </div>

            <!-- Zone VOS NUMÉROS -->
            <div class="your-numbers-zone">
                <div class="zone-title">💵 VOS NUMÉROS 💵</div>
                <div class="numbers-grid">
                    <div
                        v-for="(num, index) in yourNumbers"
                        :key="'your-' + index"
                        class="number-container"
                        :class="{ 'revealed': num.revealed, 'winning': num.revealed && num.isWinning }"
                    >
                        <canvas
                            :ref="el => yourCanvases[index] = el"
                            class="number-canvas"
                            @mousemove="(e) => scratchYourNumber(e, index)"
                            @touchmove.prevent="(e) => scratchYourNumber(e, index)"
                        ></canvas>
                        <div class="number-content">
                            <span class="number-value">{{ num.value }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Zone NUMÉROS GAGNANTS -->
            <div class="winning-numbers-zone">
                <div class="zone-title">🏆 NUMÉROS GAGNANTS 🏆</div>
                <div class="winning-grid">
                    <div
                        v-for="(num, index) in winningNumbers"
                        :key="'win-' + index"
                        class="winning-container"
                        :class="{ 'revealed': num.revealed }"
                    >
                        <canvas
                            :ref="el => winningCanvases[index] = el"
                            class="winning-canvas"
                            @mousemove="(e) => scratchWinningNumber(e, index)"
                            @touchmove.prevent="(e) => scratchWinningNumber(e, index)"
                        ></canvas>
                        <div class="winning-content">
                            <span class="winning-value">{{ num.value }}</span>
                            <span class="winning-gain">{{ num.gain }}€</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Zone BONUS -->
            <div class="bonus-zone">
                <div class="bonus-title">💰 BONUS CASH 💰</div>
                <div class="bonus-container" :class="{ 'revealed': bonusRevealed, 'won': bonusRevealed && bonusWon }">
                    <canvas
                        ref="bonusCanvas"
                        class="bonus-canvas"
                        @mousemove="scratchBonus"
                        @touchmove.prevent="scratchBonus"
                    ></canvas>
                    <div class="bonus-content">
                        <span class="bonus-symbol">{{ bonusSymbol }}</span>
                        <span v-if="bonusWon" class="bonus-gain">+{{ bonusGain }}€</span>
                    </div>
                </div>
            </div>

            <!-- Résultat final -->
            <div v-if="allRevealed" class="result-zone" :class="{ 'winner': totalGain > 0 }">
                <div v-if="totalGain > 0" class="win-message">
                    <span class="win-text">💵 BRAVO ! 💵</span>
                    <span class="win-amount">Vous gagnez {{ totalGain }}€</span>
                </div>
                <div v-else class="lose-message">
                    <span class="lose-text">Pas de gain cette fois</span>
                    <span class="lose-sub">Retentez votre chance !</span>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div v-if="allRevealed" class="action-buttons">
                <button class="replay-btn" :class="{ disabled: !canReplay }" @click="replay">
                    🔄 Nouveau ticket ({{ ticket.price }}$)
                </button>
                <button class="close-btn" @click="$emit('close')">Fermer</button>
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

// Vos numéros (4 numéros)
const yourNumbers = reactive([]);
const yourCanvases = ref([]);
const yourCtxs = ref([]);
const yourScratchCount = reactive([0, 0, 0, 0]);

// Numéros gagnants (20 numéros avec gains)
const winningNumbers = reactive([]);
const winningCanvases = ref([]);
const winningCtxs = ref([]);
const winningScratchCount = reactive(Array(20).fill(0));

// Bonus
const bonusCanvas = ref(null);
const bonusCtx = ref(null);
const bonusScratchCount = ref(0);
const bonusRevealed = ref(false);
const bonusWon = ref(false);
const bonusSymbol = ref('');
const bonusGain = ref(0);

const resultEmitted = ref(false);

const allYourRevealed = computed(() => yourNumbers.every(n => n.revealed));
const allWinningRevealed = computed(() => winningNumbers.every(n => n.revealed));
const allRevealed = computed(() => allYourRevealed.value && allWinningRevealed.value && bonusRevealed.value);

const totalGain = computed(() => {
    let total = 0;
    // Gains des numéros correspondants
    yourNumbers.forEach(yn => {
        if (yn.revealed && yn.isWinning) {
            const match = winningNumbers.find(wn => wn.value === yn.value && wn.revealed);
            if (match) total += match.gain;
        }
    });
    // Bonus
    if (bonusWon.value) total += bonusGain.value;
    return total;
});

const canReplay = computed(() => props.balance >= props.ticket.price);

// Génération du ticket
const generateTicket = () => {
    const rand = Math.random() * 100;
    let hasWin = false;
    let winAmount = 0;

    // Probabilités similaires au CASH FDJ
    if (rand < 65) {
        hasWin = false;
    } else if (rand < 80) {
        hasWin = true;
        winAmount = 2;
    } else if (rand < 88) {
        hasWin = true;
        winAmount = 5;
    } else if (rand < 93) {
        hasWin = true;
        winAmount = 10;
    } else if (rand < 96) {
        hasWin = true;
        winAmount = 20;
    } else if (rand < 98) {
        hasWin = true;
        winAmount = 50;
    } else if (rand < 99.3) {
        hasWin = true;
        winAmount = 100;
    } else if (rand < 99.8) {
        hasWin = true;
        winAmount = 500;
    } else {
        hasWin = true;
        winAmount = 5000;
    }

    // Générer les numéros gagnants (20 numéros uniques de 1-30)
    const allNums = Array.from({ length: 30 }, (_, i) => i + 1);
    const shuffled = allNums.sort(() => Math.random() - 0.5);

    const winNums = shuffled.slice(0, 20);
    const possibleGains = [2, 2, 2, 5, 5, 5, 10, 10, 10, 20, 20, 50, 50, 100, 100, 200, 500, 1000, 2000, 5000];
    const gains = possibleGains.sort(() => Math.random() - 0.5);

    winningNumbers.length = 0;
    winNums.forEach((num, i) => {
        winningNumbers.push({
            value: num,
            gain: gains[i],
            revealed: false
        });
    });

    // Générer vos numéros (4 numéros)
    yourNumbers.length = 0;

    if (hasWin) {
        // Au moins un numéro correspondant
        const matchingNum = winNums[Math.floor(Math.random() * winNums.length)];
        const matchingWin = winningNumbers.find(w => w.value === matchingNum);
        matchingWin.gain = winAmount;

        yourNumbers.push({ value: matchingNum, revealed: false, isWinning: true });

        // 3 autres numéros non gagnants
        const otherNums = shuffled.filter(n => !winNums.includes(n)).slice(0, 3);
        otherNums.forEach(num => {
            yourNumbers.push({ value: num, revealed: false, isWinning: false });
        });
    } else {
        // Aucun numéro correspondant
        const nonWinNums = shuffled.filter(n => !winNums.includes(n)).slice(0, 4);
        nonWinNums.forEach(num => {
            yourNumbers.push({ value: num, revealed: false, isWinning: false });
        });
    }

    // Mélanger vos numéros
    yourNumbers.sort(() => Math.random() - 0.5);

    // Bonus (20% de chance)
    if (Math.random() < 0.2) {
        bonusWon.value = true;
        bonusSymbol.value = '💵';
        bonusGain.value = [5, 10, 20, 50][Math.floor(Math.random() * 4)];
    } else {
        bonusWon.value = false;
        bonusSymbol.value = ['❌', '💔', '🚫'][Math.floor(Math.random() * 3)];
        bonusGain.value = 0;
    }
};

// Canvas - Vos numéros
const initYourCanvas = (index) => {
    const canvas = yourCanvases.value[index];
    if (!canvas) return;

    canvas.width = 80;
    canvas.height = 80;

    const ctx = canvas.getContext('2d');
    yourCtxs.value[index] = ctx;

    // Fond vert style billet
    const gradient = ctx.createLinearGradient(0, 0, 80, 80);
    gradient.addColorStop(0, '#2e7d32');
    gradient.addColorStop(0.5, '#388e3c');
    gradient.addColorStop(1, '#1b5e20');

    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, 80, 80);

    // Texture
    ctx.fillStyle = 'rgba(255,255,255,0.15)';
    for (let i = 0; i < 20; i++) {
        ctx.fillRect(Math.random() * 80, Math.random() * 80, 2, 2);
    }

    // Symbole $
    ctx.fillStyle = 'rgba(255,255,255,0.3)';
    ctx.font = 'bold 40px Arial';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('$', 40, 40);
};

// Canvas - Numéros gagnants
const initWinningCanvas = (index) => {
    const canvas = winningCanvases.value[index];
    if (!canvas) return;

    canvas.width = 120;
    canvas.height = 65;

    const ctx = canvas.getContext('2d');
    winningCtxs.value[index] = ctx;

    const gradient = ctx.createLinearGradient(0, 0, 120, 65);
    gradient.addColorStop(0, '#ffd700');
    gradient.addColorStop(0.5, '#ffb300');
    gradient.addColorStop(1, '#ff8f00');

    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, 120, 65);

    ctx.fillStyle = 'rgba(255,255,255,0.2)';
    for (let i = 0; i < 15; i++) {
        ctx.fillRect(Math.random() * 120, Math.random() * 65, 2, 2);
    }

    ctx.fillStyle = 'rgba(255,255,255,0.3)';
    ctx.font = 'bold 24px Arial';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('?', 60, 32);
};

// Canvas - Bonus
const initBonusCanvas = () => {
    const canvas = bonusCanvas.value;
    if (!canvas) return;

    canvas.width = 120;
    canvas.height = 80;

    const ctx = canvas.getContext('2d');
    bonusCtx.value = ctx;

    const gradient = ctx.createLinearGradient(0, 0, 120, 80);
    gradient.addColorStop(0, '#4caf50');
    gradient.addColorStop(0.5, '#66bb6a');
    gradient.addColorStop(1, '#43a047');

    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, 120, 80);

    ctx.fillStyle = 'rgba(255,255,255,0.2)';
    ctx.font = 'bold 24px Arial';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('BONUS', 60, 40);
};

// Grattage
const scratchYourNumber = (e, index) => {
    if (yourNumbers[index].revealed) return;

    const canvas = yourCanvases.value[index];
    const ctx = yourCtxs.value[index];
    if (!canvas || !ctx) return;

    const rect = canvas.getBoundingClientRect();
    const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
    const y = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top;

    ctx.globalCompositeOperation = 'destination-out';
    ctx.beginPath();
    ctx.arc(x, y, 20, 0, Math.PI * 2);
    ctx.fill();

    yourScratchCount[index]++;

    // Révéler après 8 mouvements
    if (yourScratchCount[index] >= 8) {
        yourNumbers[index].revealed = true;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        checkResult();
    }
};

const scratchWinningNumber = (e, index) => {
    if (winningNumbers[index].revealed) return;

    const canvas = winningCanvases.value[index];
    const ctx = winningCtxs.value[index];
    if (!canvas || !ctx) return;

    const rect = canvas.getBoundingClientRect();
    const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
    const y = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top;

    ctx.globalCompositeOperation = 'destination-out';
    ctx.beginPath();
    ctx.arc(x, y, 18, 0, Math.PI * 2);
    ctx.fill();

    winningScratchCount[index]++;

    // Révéler après 6 mouvements
    if (winningScratchCount[index] >= 6) {
        winningNumbers[index].revealed = true;
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
    ctx.arc(x, y, 20, 0, Math.PI * 2);
    ctx.fill();

    bonusScratchCount.value++;

    // Révéler après 8 mouvements
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
            jackpot: totalGain.value >= 500,
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
        for (let i = 0; i < 4; i++) initYourCanvas(i);
        for (let i = 0; i < 20; i++) initWinningCanvas(i);
        initBonusCanvas();
    }, 50);
});
</script>

<style scoped>
.ticket-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.85);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 100;
    padding: 10px;
    overflow: auto;
}

.cash-ticket {
    width: 95vw;
    max-width: 750px;
    max-height: 95vh;
    overflow-y: auto;
    background: linear-gradient(145deg, #1b5e20 0%, #2e7d32 50%, #1b5e20 100%);
    border: 4px solid #ffd700;
    border-radius: 16px;
    padding: 20px;
    position: relative;
    box-shadow:
        0 0 30px rgba(76, 175, 80, 0.4),
        inset 0 0 60px rgba(0, 0, 0, 0.3);
}

/* Header */
.ticket-header {
    text-align: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 3px solid rgba(255, 215, 0, 0.5);
}

.cash-logo {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    margin-bottom: 8px;
}

.cash-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #ffd700, #ffb300);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 30px;
    color: #1b5e20;
    border: 3px solid white;
}

.ticket-title {
    font-size: 36px;
    font-weight: bold;
    color: #ffd700;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    letter-spacing: 8px;
}

.ticket-subtitle {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.8);
    letter-spacing: 2px;
}

/* Zone VOS NUMÉROS */
.your-numbers-zone {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 12px;
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

.numbers-grid {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
}

.number-container {
    width: 80px;
    height: 80px;
    position: relative;
    cursor: pointer;
    border-radius: 10px;
    overflow: hidden;
    border: 3px solid rgba(255, 215, 0, 0.3);
    transition: all 0.3s;
}

.number-container.revealed {
    border-color: rgba(255, 255, 255, 0.5);
}

.number-container.winning {
    border-color: #ffd700;
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.6);
    animation: winPulse 0.5s ease-out;
}

.number-canvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 2;
    cursor: pointer;
}

.number-content {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
}

.number-value {
    font-size: 32px;
    font-weight: bold;
    color: #1b5e20;
}

/* Zone NUMÉROS GAGNANTS */
.winning-numbers-zone {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 15px;
}

.winning-grid {
    display: grid;
    grid-template-columns: repeat(5, 120px);
    gap: 10px;
    justify-content: center;
}

.winning-container {
    width: 120px;
    height: 65px;
    position: relative;
    cursor: pointer;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid rgba(255, 215, 0, 0.3);
}

.winning-container.revealed {
    border-color: rgba(255, 255, 255, 0.5);
}

.winning-canvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 2;
    cursor: pointer;
}

.winning-content {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #fff8e1, #ffecb3);
    gap: 2px;
}

.winning-value {
    font-size: 24px;
    font-weight: bold;
    color: #1b5e20;
}

.winning-gain {
    font-size: 14px;
    font-weight: bold;
    color: #ff6f00;
    background: rgba(0, 0, 0, 0.1);
    padding: 2px 8px;
    border-radius: 4px;
}

/* Zone Bonus */
.bonus-zone {
    background: rgba(255, 215, 0, 0.15);
    border: 2px solid rgba(255, 215, 0, 0.4);
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 15px;
    text-align: center;
}

.bonus-title {
    color: #ffd700;
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 10px;
    letter-spacing: 2px;
}

.bonus-container {
    width: 120px;
    height: 80px;
    margin: 0 auto;
    position: relative;
    cursor: pointer;
    border-radius: 10px;
    overflow: hidden;
    border: 3px solid rgba(255, 215, 0, 0.5);
}

.bonus-container.won {
    border-color: #ffd700;
    box-shadow: 0 0 25px rgba(255, 215, 0, 0.7);
}

.bonus-canvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 2;
    cursor: pointer;
}

.bonus-content {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #e8f5e9, #a5d6a7);
    gap: 5px;
}

.bonus-symbol {
    font-size: 32px;
}

.bonus-gain {
    font-size: 16px;
    font-weight: bold;
    color: #1b5e20;
}

/* Résultat */
.result-zone {
    text-align: center;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 15px;
    background: rgba(0, 0, 0, 0.2);
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes winPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.result-zone.winner {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.3), rgba(255, 215, 0, 0.1));
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
    font-size: 28px;
    font-weight: bold;
    color: #fff;
    text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
}

.lose-message {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.lose-text {
    font-size: 18px;
    color: rgba(255, 255, 255, 0.8);
}

.lose-sub {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.6);
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
    border-radius: 8px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
}

.replay-btn {
    background: linear-gradient(135deg, #ffd700, #ffb300);
    color: #1b5e20;
}

.replay-btn:hover:not(.disabled) {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(255, 215, 0, 0.4);
}

.replay-btn.disabled {
    background: #555;
    color: #888;
    cursor: not-allowed;
}

.close-btn {
    background: linear-gradient(135deg, #6b7280, #4b5563);
    color: white;
}

.close-btn:hover {
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 700px) {
    .cash-ticket {
        padding: 15px;
    }

    .ticket-title {
        font-size: 28px;
        letter-spacing: 4px;
    }

    .numbers-grid {
        gap: 8px;
    }

    .winning-grid {
        grid-template-columns: repeat(4, 100px);
        gap: 8px;
    }

    .winning-container {
        width: 100px;
        height: 55px;
    }

    .winning-canvas {
        width: 100%;
        height: 100%;
    }

    .winning-value {
        font-size: 18px;
    }

    .winning-gain {
        font-size: 11px;
    }
}

@media (max-width: 500px) {
    .winning-grid {
        grid-template-columns: repeat(4, 80px);
    }

    .winning-container {
        width: 80px;
        height: 50px;
    }

    .winning-canvas {
        width: 100%;
        height: 100%;
    }

    .number-container {
        width: 65px;
        height: 65px;
    }

    .number-canvas {
        width: 100%;
        height: 100%;
    }

    .number-value {
        font-size: 22px;
    }
}
</style>
