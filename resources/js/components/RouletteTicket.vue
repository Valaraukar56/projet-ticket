<template>
    <div class="ticket-overlay">
        <div class="slot-machine">
            <!-- Header -->
            <div class="machine-header">
                <div class="machine-lights">
                    <span v-for="i in 8" :key="i" class="light" :style="{ animationDelay: `${i * 0.15}s` }"></span>
                </div>
                <span class="machine-title">🎰 YOLO SLOTS 🎰</span>
                <span class="machine-subtitle">Tentez votre chance... si vous l'osez !</span>
            </div>

            <!-- Machine body -->
            <div class="machine-body">
                <!-- Écran des rouleaux -->
                <div class="reels-display">
                    <!-- Rouleau 1 -->
                    <div class="reel-window">
                        <div class="reel">
                            <div
                                class="reel-strip"
                                :class="{ 'spinning': reel1Spinning }"
                                ref="reel1Ref"
                            >
                                <div v-for="(symbol, i) in extendedSymbols" :key="'r1-' + i" class="symbol">
                                    {{ symbol }}
                                </div>
                            </div>
                        </div>
                        <!-- Symbole final affiché par-dessus -->
                        <div v-if="showFinal1" class="final-symbol">{{ finalSymbols[0] }}</div>
                    </div>

                    <!-- Rouleau 2 -->
                    <div class="reel-window">
                        <div class="reel">
                            <div
                                class="reel-strip"
                                :class="{ 'spinning': reel2Spinning }"
                                ref="reel2Ref"
                            >
                                <div v-for="(symbol, i) in extendedSymbols" :key="'r2-' + i" class="symbol">
                                    {{ symbol }}
                                </div>
                            </div>
                        </div>
                        <div v-if="showFinal2" class="final-symbol">{{ finalSymbols[1] }}</div>
                    </div>

                    <!-- Rouleau 3 -->
                    <div class="reel-window">
                        <div class="reel">
                            <div
                                class="reel-strip"
                                :class="{ 'spinning': reel3Spinning }"
                                ref="reel3Ref"
                            >
                                <div v-for="(symbol, i) in extendedSymbols" :key="'r3-' + i" class="symbol">
                                    {{ symbol }}
                                </div>
                            </div>
                        </div>
                        <div v-if="showFinal3" class="final-symbol">{{ finalSymbols[2] }}</div>
                    </div>
                </div>

                <!-- Ligne de gain -->
                <div class="win-line"></div>
            </div>

            <!-- Gains possibles -->
            <div v-if="!hasSpun" class="gains-info">
                <div class="gain-row"><span>💰💰💰</span> <span class="gain-amount">{{ slotSettings.jackpot.gain }}$</span></div>
                <div class="gain-row"><span>💎💎💎</span> <span class="gain-amount">{{ slotSettings.bigWin.gain }}$</span></div>
                <div class="gain-row"><span>🍒🍒🍒</span> <span class="gain-amount">{{ slotSettings.win.gain }}$</span></div>
                <div class="gain-row"><span>⭐⭐⭐</span> <span class="gain-amount">{{ slotSettings.smallWin.gain }}$</span></div>
                <div class="gain-row bomb"><span>💣💣💣</span> <span class="gain-amount">CHAOS</span></div>
            </div>

            <!-- Bouton Pull -->
            <div v-if="!hasSpun" class="lever-zone">
                <button class="pull-lever" @click="spin" :disabled="isSpinning">
                    <span class="lever-icon">🎰</span>
                    <span class="lever-text">TIRER LE LEVIER</span>
                    <span class="lever-warning">⚠️ 3 bombes = CHAOS</span>
                </button>
            </div>

            <!-- Résultat -->
            <div v-if="hasSpun && !isSpinning" class="result-zone" :class="resultClass">
                <div v-if="result === 'jackpot'" class="jackpot-result">
                    <span class="result-icon">💰💰💰</span>
                    <span class="result-title">JACKPOT !!!</span>
                    <span class="result-amount">+{{ winAmount }}€</span>
                </div>
                <div v-else-if="result === 'big-win'" class="big-win-result">
                    <span class="result-icon">💎💎💎</span>
                    <span class="result-title">GROS GAIN !</span>
                    <span class="result-amount">+{{ winAmount }}€</span>
                </div>
                <div v-else-if="result === 'win'" class="win-result">
                    <span class="result-icon">🍒🍒🍒</span>
                    <span class="result-title">Gagné !</span>
                    <span class="result-amount">+{{ winAmount }}€</span>
                </div>
                <div v-else-if="result === 'small-win'" class="small-win-result">
                    <span class="result-icon">⭐⭐⭐</span>
                    <span class="result-title">Petit gain !</span>
                    <span class="result-amount">+{{ winAmount }}€</span>
                </div>
                <div v-else-if="result === 'bomb'" class="bomb-result">
                    <span class="result-icon">💣💣💣</span>
                    <span class="result-title">BOOM !</span>
                    <span class="result-sub">Préparez-vous au chaos...</span>
                </div>
                <div v-else class="lose-result">
                    <span class="result-icon">{{ finalSymbols[0] }}{{ finalSymbols[1] }}{{ finalSymbols[2] }}</span>
                    <span class="result-title">Perdu...</span>
                    <span class="result-sub">La chance tournera !</span>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div v-if="hasSpun && !isSpinning && result !== 'bomb'" class="action-buttons">
                <button class="replay-btn" :class="{ disabled: !canReplay }" @click="replay">
                    🔄 Rejouer ({{ ticket.price }}$)
                </button>
                <button class="close-btn" @click="$emit('close')">Fermer</button>
            </div>

        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    ticket: { type: Object, required: true },
    balance: { type: Number, required: true },
});

const emit = defineEmits(['close', 'result', 'replay']);

// Paramètres des gains (chargés depuis le backend)
const slotSettings = ref({
    jackpot: { icon: '💰', name: 'Jackpot', gain: 10000 },
    bigWin: { icon: '💎', name: 'Gros Gain', gain: 5000 },
    win: { icon: '🍒', name: 'Gain', gain: 2500 },
    smallWin: { icon: '⭐', name: 'Petit Gain', gain: 1000 },
    bomb: { icon: '💣', name: 'Chaos', gain: 0 },
    lose: { icon: '💀', name: 'Perdu', gain: 0 },
});

// Charger les paramètres depuis le backend
const loadSettings = async () => {
    try {
        const response = await fetch('/api/admin/tickets');
        if (response.ok) {
            const data = await response.json();
            slotSettings.value = data.settings;
        }
    } catch (e) {
        console.log('Utilisation des paramètres par défaut');
    }
};

// Symboles de base
const baseSymbols = ['💰', '💀', '🍒', '💣', '⭐', '💀', '💎', '💣', '🍒', '💀', '⭐', '💣'];

// Symboles étendus pour l'animation (répétés plusieurs fois)
const extendedSymbols = computed(() => {
    const extended = [];
    for (let i = 0; i < 10; i++) {
        extended.push(...baseSymbols);
    }
    return extended;
});

// Mapping symbole -> type
const symbolTypes = {
    '💰': { type: 'jackpot' },
    '💎': { type: 'big-win' },
    '🍒': { type: 'win' },
    '⭐': { type: 'small-win' },
    '💣': { type: 'bomb' },
    '💀': { type: 'lose' },
};

// État des rouleaux
const reel1Spinning = ref(false);
const reel2Spinning = ref(false);
const reel3Spinning = ref(false);
const showFinal1 = ref(false);
const showFinal2 = ref(false);
const showFinal3 = ref(false);
const isSpinning = ref(false);
const hasSpun = ref(false);
const result = ref(null);
const winAmount = ref(0);
const finalSymbols = ref(['💀', '💀', '💀']);

const canReplay = computed(() => props.balance >= props.ticket.price);

const resultClass = computed(() => {
    if (result.value === 'jackpot') return 'jackpot';
    if (result.value === 'big-win') return 'big-win';
    if (result.value === 'win') return 'win';
    if (result.value === 'small-win') return 'small-win';
    if (result.value === 'bomb') return 'bomb';
    return 'lose';
});

// Déterminer le résultat en fonction des probabilités YOLO
const determineResult = () => {
    const rand = Math.random() * 100;

    // 25% bombs (CHAOS), 40% perdu, 15% petit gain, 12% gain, 5% gros gain, 3% jackpot
    if (rand < 25) {
        return 'bomb';
    } else if (rand < 65) {
        return 'lose';
    } else if (rand < 80) {
        return 'small-win';
    } else if (rand < 92) {
        return 'win';
    } else if (rand < 97) {
        return 'big-win';
    } else {
        return 'jackpot';
    }
};

// Obtenir le symbole pour un type
const getSymbolForType = (type) => {
    const map = {
        'jackpot': '💰',
        'big-win': '💎',
        'win': '🍒',
        'small-win': '⭐',
        'bomb': '💣',
        'lose': '💀',
    };
    return map[type];
};

// Obtenir un symbole aléatoire différent
const getRandomSymbol = (exclude = null) => {
    const symbols = ['💰', '💎', '🍒', '⭐', '💣', '💀'];
    let symbol;
    do {
        symbol = symbols[Math.floor(Math.random() * symbols.length)];
    } while (exclude && symbol === exclude);
    return symbol;
};

const spin = () => {
    if (isSpinning.value) return;

    isSpinning.value = true;
    hasSpun.value = true;
    showFinal1.value = false;
    showFinal2.value = false;
    showFinal3.value = false;

    // Lancer l'animation de spin
    reel1Spinning.value = true;
    reel2Spinning.value = true;
    reel3Spinning.value = true;

    // Déterminer le résultat
    const targetResult = determineResult();
    result.value = targetResult;

    let sym1, sym2, sym3;

    if (targetResult === 'lose') {
        // Perdu - symboles différents (pas 3 identiques)
        sym1 = getRandomSymbol();
        sym2 = getRandomSymbol(sym1);
        sym3 = getRandomSymbol();
        // S'assurer qu'on n'a pas 3 identiques
        if (sym1 === sym2 && sym2 === sym3) {
            sym3 = getRandomSymbol(sym1);
        }
    } else {
        // Gagné ou bomb - 3 symboles identiques
        const winSymbol = getSymbolForType(targetResult);
        sym1 = winSymbol;
        sym2 = winSymbol;
        sym3 = winSymbol;
    }

    finalSymbols.value = [sym1, sym2, sym3];

    // Calculer le gain basé sur les paramètres admin
    if (targetResult !== 'lose' && targetResult !== 'bomb') {
        if (targetResult === 'jackpot') {
            winAmount.value = slotSettings.value.jackpot.gain;
        } else if (targetResult === 'big-win') {
            winAmount.value = slotSettings.value.bigWin.gain;
        } else if (targetResult === 'win') {
            winAmount.value = slotSettings.value.win.gain;
        } else if (targetResult === 'small-win') {
            winAmount.value = slotSettings.value.smallWin.gain;
        }
    }

    // Arrêter les rouleaux progressivement et afficher les symboles finaux
    setTimeout(() => {
        reel1Spinning.value = false;
        showFinal1.value = true;
    }, 1000);

    setTimeout(() => {
        reel2Spinning.value = false;
        showFinal2.value = true;
    }, 1500);

    setTimeout(() => {
        reel3Spinning.value = false;
        showFinal3.value = true;
        isSpinning.value = false;

        // Émettre le résultat
        if (targetResult === 'bomb') {
            emit('result', {
                won: false,
                amount: 0,
                chaos: true,
            });
        } else {
            emit('result', {
                won: targetResult !== 'lose',
                amount: winAmount.value,
                jackpot: targetResult === 'jackpot',
                chaos: false,
            });
        }
    }, 2000);
};

const replay = () => {
    if (!canReplay.value) return;
    emit('replay', props.ticket);
};

// Charger les paramètres au montage
onMounted(() => {
    loadSettings();
});
</script>

<style scoped>
.ticket-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.95);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 100;
    padding: 10px;
    overflow: auto;
}

.slot-machine {
    width: 95vw;
    max-width: 400px;
    background: linear-gradient(145deg, #8B0000 0%, #4a0000 50%, #2a0000 100%);
    border: 6px solid #ffd700;
    border-radius: 25px;
    padding: 20px;
    box-shadow:
        0 0 50px rgba(255, 215, 0, 0.3),
        inset 0 0 50px rgba(0, 0, 0, 0.5),
        0 10px 30px rgba(0, 0, 0, 0.8);
}

/* Header avec lumières */
.machine-header {
    text-align: center;
    margin-bottom: 15px;
}

.machine-lights {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-bottom: 10px;
}

.light {
    width: 15px;
    height: 15px;
    border-radius: 50%;
    background: #ffd700;
    box-shadow: 0 0 10px #ffd700, 0 0 20px #ffd700;
    animation: blink 1s infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; box-shadow: 0 0 10px #ffd700, 0 0 20px #ffd700; }
    50% { opacity: 0.3; box-shadow: 0 0 5px #ffd700; }
}

.machine-title {
    display: block;
    font-size: 26px;
    font-weight: bold;
    color: #ffd700;
    text-shadow: 0 0 20px rgba(255, 215, 0, 0.8), 2px 2px 0 #8B0000;
    letter-spacing: 2px;
}

.machine-subtitle {
    display: block;
    font-size: 11px;
    color: rgba(255, 255, 255, 0.7);
    margin-top: 5px;
}

/* Corps de la machine */
.machine-body {
    position: relative;
    background: linear-gradient(180deg, #1a1a1a 0%, #0a0a0a 100%);
    border: 4px solid #ffd700;
    border-radius: 15px;
    padding: 15px;
    margin-bottom: 15px;
}

.reels-display {
    display: flex;
    justify-content: center;
    gap: 10px;
}

.reel-window {
    width: 80px;
    height: 80px;
    background: linear-gradient(180deg, #fff 0%, #e0e0e0 100%);
    border: 3px solid #333;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: inset 0 5px 15px rgba(0, 0, 0, 0.5);
    position: relative;
}

.reel {
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.reel-strip {
    display: flex;
    flex-direction: column;
}

.reel-strip.spinning {
    animation: spin 0.1s linear infinite;
}

@keyframes spin {
    0% { transform: translateY(0); }
    100% { transform: translateY(-960px); } /* 12 symboles * 80px */
}

.symbol {
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 50px;
    flex-shrink: 0;
    background: linear-gradient(180deg, #fff 0%, #f0f0f0 100%);
}

/* Symbole final affiché par-dessus */
.final-symbol {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 50px;
    background: linear-gradient(180deg, #fff 0%, #f0f0f0 100%);
    animation: symbolPop 0.3s ease-out;
}

@keyframes symbolPop {
    0% { transform: scale(1.5); }
    50% { transform: scale(0.9); }
    100% { transform: scale(1); }
}

/* Ligne de gain */
.win-line {
    position: absolute;
    left: 10px;
    right: 10px;
    top: 50%;
    height: 4px;
    background: linear-gradient(90deg, transparent, #ff0000, #ff0000, transparent);
    transform: translateY(-50%);
    box-shadow: 0 0 10px #ff0000;
    pointer-events: none;
}

/* Gains possibles */
.gains-info {
    background: rgba(0, 0, 0, 0.5);
    border: 2px solid #ffd700;
    border-radius: 10px;
    padding: 10px 15px;
    margin-bottom: 15px;
}

.gain-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 14px;
}

.gain-row:last-child {
    border-bottom: none;
}

.gain-row.bomb {
    color: #ff4444;
}

.gain-amount {
    color: #ffd700;
    font-weight: bold;
}

.gain-row.bomb .gain-amount {
    color: #ff4444;
}

/* Levier */
.lever-zone {
    text-align: center;
    margin-bottom: 15px;
}

.pull-lever {
    background: linear-gradient(180deg, #ffd700 0%, #b8860b 100%);
    border: 3px solid #8B4513;
    border-radius: 15px;
    padding: 15px 35px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    margin: 0 auto;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
}

.pull-lever:hover:not(:disabled) {
    transform: scale(1.05);
    box-shadow: 0 0 30px rgba(255, 215, 0, 0.6);
}

.pull-lever:active:not(:disabled) {
    transform: scale(0.95);
}

.pull-lever:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.lever-icon {
    font-size: 30px;
}

.lever-text {
    font-size: 16px;
    font-weight: bold;
    color: #4a0000;
}

.lever-warning {
    font-size: 10px;
    color: #8B0000;
    font-weight: bold;
}

/* Résultats */
.result-zone {
    text-align: center;
    padding: 15px;
    border-radius: 15px;
    margin-bottom: 15px;
    animation: resultPop 0.5s ease-out;
}

@keyframes resultPop {
    0% { transform: scale(0.5); opacity: 0; }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); opacity: 1; }
}

.result-zone.jackpot {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.4), rgba(255, 215, 0, 0.2));
    border: 3px solid #ffd700;
    animation: resultPop 0.5s ease-out, jackpotPulse 0.5s infinite 0.5s;
}

@keyframes jackpotPulse {
    0%, 100% { box-shadow: 0 0 30px rgba(255, 215, 0, 0.8); }
    50% { box-shadow: 0 0 60px rgba(255, 215, 0, 1); }
}

.result-zone.big-win {
    background: linear-gradient(135deg, rgba(0, 191, 255, 0.3), rgba(0, 191, 255, 0.1));
    border: 3px solid #00bfff;
}

.result-zone.win {
    background: linear-gradient(135deg, rgba(255, 99, 71, 0.3), rgba(255, 99, 71, 0.1));
    border: 3px solid #ff6347;
}

.result-zone.small-win {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 215, 0, 0.1));
    border: 3px solid #daa520;
}

.result-zone.bomb {
    background: linear-gradient(135deg, rgba(255, 0, 0, 0.4), rgba(139, 0, 0, 0.2));
    border: 3px solid #ff0000;
    animation: resultPop 0.5s ease-out, bombShake 0.1s infinite 0.5s;
}

@keyframes bombShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.result-zone.lose {
    background: rgba(0, 0, 0, 0.3);
    border: 2px solid #555;
}

.result-icon {
    display: block;
    font-size: 40px;
    margin-bottom: 8px;
}

.result-title {
    display: block;
    font-size: 22px;
    font-weight: bold;
    color: white;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}

.result-amount {
    display: block;
    font-size: 32px;
    font-weight: bold;
    color: #ffd700;
    text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
}

.result-sub {
    display: block;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.6);
    margin-top: 5px;
}

/* Boutons */
.action-buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-bottom: 15px;
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
    background: linear-gradient(135deg, #ffd700, #b8860b);
    color: #4a0000;
}

.replay-btn:hover:not(.disabled) {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(255, 215, 0, 0.5);
}

.replay-btn.disabled {
    background: #444;
    color: #888;
    cursor: not-allowed;
}

.close-btn {
    background: linear-gradient(135deg, #4a4a4a, #3a3a3a);
    color: white;
}

.close-btn:hover {
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 400px) {
    .slot-machine {
        padding: 15px;
    }

    .machine-title {
        font-size: 20px;
    }

    .reel-window {
        width: 70px;
        height: 70px;
    }

    .symbol, .final-symbol {
        width: 70px;
        height: 70px;
        font-size: 42px;
    }

    .pull-lever {
        padding: 12px 25px;
    }

    .lever-text {
        font-size: 14px;
    }
}
</style>
