<template>
    <div class="organ-overlay">
        <!-- Panel latéral d'avertissement -->
        <div class="warning-panel">
            <div class="warning-title">⚠️ DANGER</div>
            <div class="warning-content">
                <div class="organ-warning death">
                    <span class="organ-icon">🫀</span>
                    <span class="organ-name">Cœur</span>
                    <span class="organ-result">= MORT</span>
                </div>
                <div class="organ-warning death">
                    <span class="organ-icon">🧠</span>
                    <span class="organ-name">Cerveau</span>
                    <span class="organ-result">= MORT</span>
                </div>
                <div class="organ-warning safe">
                    <span class="organ-icon">🫁</span>
                    <span class="organ-name">Poumons</span>
                    <span class="organ-result">+50$</span>
                </div>
                <div class="organ-warning safe">
                    <span class="organ-icon">🦴</span>
                    <span class="organ-name">Os</span>
                    <span class="organ-result">+30$</span>
                </div>
                <div class="organ-warning safe">
                    <span class="organ-icon">👁️</span>
                    <span class="organ-name">Œil</span>
                    <span class="organ-result">+20$</span>
                </div>
                <div class="organ-warning safe">
                    <span class="organ-icon">🦷</span>
                    <span class="organ-name">Dent</span>
                    <span class="organ-result">+10$</span>
                </div>
            </div>
            <div class="warning-footer">
                3 organes identiques<br/>pour gagner... ou mourir
            </div>
        </div>

        <!-- Machine à sous centrale -->
        <div class="organ-machine">
            <div class="machine-title">
                <span class="blood-drip">🩸</span>
                MARCHÉ NOIR
                <span class="blood-drip">🩸</span>
            </div>
            <div class="machine-subtitle">Vendez vos organes...</div>

            <div class="reels-container">
                <div class="reel" :class="{ spinning: isSpinning && !reelsStopped[0] }">
                    <div class="reel-inner" :style="{ transform: `translateY(${reelPositions[0]}px)` }">
                        <div v-for="(symbol, i) in reelSymbols[0]" :key="i" class="reel-symbol">
                            {{ symbol }}
                        </div>
                    </div>
                    <div v-if="reelsStopped[0]" class="reel-final">{{ finalSymbols[0] }}</div>
                </div>
                <div class="reel" :class="{ spinning: isSpinning && !reelsStopped[1] }">
                    <div class="reel-inner" :style="{ transform: `translateY(${reelPositions[1]}px)` }">
                        <div v-for="(symbol, i) in reelSymbols[1]" :key="i" class="reel-symbol">
                            {{ symbol }}
                        </div>
                    </div>
                    <div v-if="reelsStopped[1]" class="reel-final">{{ finalSymbols[1] }}</div>
                </div>
                <div class="reel" :class="{ spinning: isSpinning && !reelsStopped[2] }">
                    <div class="reel-inner" :style="{ transform: `translateY(${reelPositions[2]}px)` }">
                        <div v-for="(symbol, i) in reelSymbols[2]" :key="i" class="reel-symbol">
                            {{ symbol }}
                        </div>
                    </div>
                    <div v-if="reelsStopped[2]" class="reel-final">{{ finalSymbols[2] }}</div>
                </div>
            </div>

            <!-- Résultat -->
            <div v-if="showResult" class="result-display" :class="resultClass">
                <template v-if="isDeath">
                    <div class="death-result">
                        <span class="death-icon">💀</span>
                        <span class="death-text">VOUS ÊTES MORT</span>
                    </div>
                </template>
                <template v-else-if="hasWon">
                    <div class="win-result">
                        <span class="win-text">+{{ winAmount }}$</span>
                    </div>
                </template>
                <template v-else>
                    <div class="lose-result">
                        <span class="lose-text">Rien...</span>
                    </div>
                </template>
            </div>

            <!-- Levier -->
            <div class="lever-section">
                <button
                    class="pull-lever"
                    @click="spin"
                    :disabled="isSpinning"
                >
                    <span class="lever-icon">🩸</span>
                    {{ isSpinning ? 'EXTRACTION...' : 'VENDRE UN ORGANE' }}
                </button>
            </div>

            <div class="escape-hint" v-if="!isSpinning && !showResult">
                <button class="escape-btn" @click="$emit('escape')">
                    Fuir dans l'ombre...
                </button>
            </div>
        </div>

        <!-- Effets visuels -->
        <div class="blood-overlay"></div>
        <div class="vignette"></div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const emit = defineEmits(['win', 'death', 'escape']);

const organs = [
    { symbol: '🫀', name: 'heart', fatal: true },
    { symbol: '🧠', name: 'brain', fatal: true },
    { symbol: '🫁', name: 'lungs', gain: 50 },
    { symbol: '🦴', name: 'bone', gain: 30 },
    { symbol: '👁️', name: 'eye', gain: 20 },
    { symbol: '🦷', name: 'tooth', gain: 10 },
];

const isSpinning = ref(false);
const showResult = ref(false);
const isDeath = ref(false);
const hasWon = ref(false);
const winAmount = ref(0);
const resultClass = ref('');

const reelSymbols = ref([[], [], []]);
const reelPositions = ref([0, 0, 0]);
const reelsStopped = ref([false, false, false]);
const finalSymbols = ref(['', '', '']);

// Générer des symboles aléatoires pour l'animation
const generateReelSymbols = () => {
    for (let i = 0; i < 3; i++) {
        reelSymbols.value[i] = [];
        for (let j = 0; j < 20; j++) {
            const randomOrgan = organs[Math.floor(Math.random() * organs.length)];
            reelSymbols.value[i].push(randomOrgan.symbol);
        }
    }
};

// Déterminer le résultat (pondéré pour plus de tension)
const determineResult = () => {
    const results = [];

    for (let i = 0; i < 3; i++) {
        // 15% chance d'organe vital (coeur/cerveau)
        // 85% chance d'organe non-vital
        const rand = Math.random();
        if (rand < 0.15) {
            // Organe vital
            results.push(organs[Math.random() < 0.5 ? 0 : 1]);
        } else {
            // Organe non-vital
            const nonFatal = organs.filter(o => !o.fatal);
            results.push(nonFatal[Math.floor(Math.random() * nonFatal.length)]);
        }
    }

    return results;
};

const spin = () => {
    if (isSpinning.value) return;

    isSpinning.value = true;
    showResult.value = false;
    isDeath.value = false;
    hasWon.value = false;
    reelsStopped.value = [false, false, false];

    generateReelSymbols();

    // Déterminer le résultat final
    const results = determineResult();
    finalSymbols.value = results.map(r => r.symbol);

    // Animation des rouleaux
    let pos = [0, 0, 0];
    const animationInterval = setInterval(() => {
        for (let i = 0; i < 3; i++) {
            if (!reelsStopped.value[i]) {
                pos[i] -= 80;
                reelPositions.value[i] = pos[i];
            }
        }
    }, 50);

    // Arrêter les rouleaux un par un
    setTimeout(() => {
        reelsStopped.value[0] = true;
    }, 1000);

    setTimeout(() => {
        reelsStopped.value[1] = true;
    }, 1500);

    setTimeout(() => {
        reelsStopped.value[2] = true;
        clearInterval(animationInterval);

        // Calculer le résultat
        setTimeout(() => {
            checkResult(results);
        }, 300);
    }, 2000);
};

const checkResult = (results) => {
    isSpinning.value = false;
    showResult.value = true;

    // Vérifier si 3 identiques
    const allSame = results[0].symbol === results[1].symbol && results[1].symbol === results[2].symbol;

    if (allSame) {
        if (results[0].fatal) {
            // MORT - organe vital x3
            isDeath.value = true;
            resultClass.value = 'death';

            setTimeout(() => {
                emit('death');
            }, 2000);
        } else {
            // GAIN - organe non-vital x3
            hasWon.value = true;
            winAmount.value = results[0].gain;
            resultClass.value = 'win';

            setTimeout(() => {
                emit('win', winAmount.value);
            }, 1500);
        }
    } else {
        // Pas de combo
        resultClass.value = 'lose';
    }
};

onMounted(() => {
    generateReelSymbols();
});
</script>

<style scoped>
.organ-overlay {
    position: fixed;
    inset: 0;
    background: linear-gradient(135deg, #0a0000 0%, #1a0505 50%, #0a0000 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 500;
    overflow: hidden;
}

.blood-overlay {
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at top, rgba(139, 0, 0, 0.3) 0%, transparent 70%);
    pointer-events: none;
}

.vignette {
    position: absolute;
    inset: 0;
    box-shadow: inset 0 0 150px rgba(0, 0, 0, 0.9);
    pointer-events: none;
}

/* Panel d'avertissement */
.warning-panel {
    position: absolute;
    right: 30px;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(20, 0, 0, 0.9);
    border: 2px solid #8b0000;
    border-radius: 15px;
    padding: 20px;
    width: 180px;
}

.warning-title {
    color: #ff0000;
    font-size: 18px;
    font-weight: bold;
    text-align: center;
    margin-bottom: 15px;
    text-shadow: 0 0 10px #ff0000;
}

.warning-content {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.organ-warning {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    border-radius: 8px;
    background: rgba(0, 0, 0, 0.5);
}

.organ-warning.death {
    border: 1px solid #ff0000;
    background: rgba(139, 0, 0, 0.3);
}

.organ-warning.safe {
    border: 1px solid #444;
}

.organ-icon {
    font-size: 20px;
}

.organ-name {
    flex: 1;
    color: #ccc;
    font-size: 12px;
}

.organ-result {
    font-weight: bold;
    font-size: 11px;
}

.organ-warning.death .organ-result {
    color: #ff0000;
}

.organ-warning.safe .organ-result {
    color: #4ade80;
}

.warning-footer {
    margin-top: 15px;
    text-align: center;
    color: #666;
    font-size: 11px;
    line-height: 1.4;
}

/* Machine à sous */
.organ-machine {
    background: linear-gradient(180deg, #1a0808 0%, #0d0404 100%);
    border: 3px solid #8b0000;
    border-radius: 25px;
    padding: 30px 40px;
    box-shadow: 0 0 50px rgba(139, 0, 0, 0.5), inset 0 0 30px rgba(0, 0, 0, 0.8);
    position: relative;
    z-index: 10;
}

.machine-title {
    font-size: 28px;
    font-weight: bold;
    color: #8b0000;
    text-align: center;
    text-shadow: 0 0 20px #ff0000;
    margin-bottom: 5px;
    letter-spacing: 3px;
}

.blood-drip {
    animation: drip 2s ease-in-out infinite;
}

@keyframes drip {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(5px); }
}

.machine-subtitle {
    text-align: center;
    color: #666;
    font-size: 14px;
    margin-bottom: 25px;
    font-style: italic;
}

/* Rouleaux */
.reels-container {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-bottom: 25px;
}

.reel {
    width: 100px;
    height: 100px;
    background: linear-gradient(180deg, #0a0000 0%, #1a0505 50%, #0a0000 100%);
    border: 3px solid #5a0000;
    border-radius: 15px;
    overflow: hidden;
    position: relative;
    box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.8);
}

.reel-inner {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    transition: transform 0.05s linear;
}

.reel-symbol {
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 50px;
}

.reel-final {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 55px;
    background: linear-gradient(180deg, #1a0505 0%, #0d0303 100%);
    animation: revealOrgan 0.3s ease-out;
}

@keyframes revealOrgan {
    from { transform: scale(1.5); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.reel.spinning {
    animation: reelGlow 0.2s ease-in-out infinite alternate;
}

@keyframes reelGlow {
    from { border-color: #5a0000; }
    to { border-color: #8b0000; }
}

/* Résultat */
.result-display {
    text-align: center;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 15px;
    animation: resultAppear 0.5s ease-out;
}

@keyframes resultAppear {
    from { transform: scale(0.8); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.result-display.death {
    background: linear-gradient(180deg, rgba(139, 0, 0, 0.5), rgba(80, 0, 0, 0.5));
    border: 2px solid #ff0000;
}

.result-display.win {
    background: linear-gradient(180deg, rgba(0, 100, 0, 0.3), rgba(0, 60, 0, 0.3));
    border: 2px solid #4ade80;
}

.result-display.lose {
    background: rgba(50, 50, 50, 0.3);
    border: 2px solid #444;
}

.death-result {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.death-icon {
    font-size: 50px;
    animation: deathPulse 0.5s ease-in-out infinite;
}

@keyframes deathPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

.death-text {
    color: #ff0000;
    font-size: 24px;
    font-weight: bold;
    text-shadow: 0 0 20px #ff0000;
    letter-spacing: 3px;
}

.win-text {
    color: #4ade80;
    font-size: 32px;
    font-weight: bold;
    text-shadow: 0 0 20px #4ade80;
}

.lose-text {
    color: #666;
    font-size: 20px;
}

/* Levier */
.lever-section {
    display: flex;
    justify-content: center;
}

.pull-lever {
    padding: 15px 40px;
    background: linear-gradient(180deg, #8b0000 0%, #5a0000 100%);
    border: 2px solid #ff0000;
    border-radius: 15px;
    color: white;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 10px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
}

.pull-lever:hover:not(:disabled) {
    background: linear-gradient(180deg, #a00000 0%, #700000 100%);
    transform: scale(1.05);
    box-shadow: 0 0 30px rgba(255, 0, 0, 0.5);
}

.pull-lever:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.lever-icon {
    font-size: 24px;
}

/* Bouton de fuite */
.escape-hint {
    margin-top: 20px;
    text-align: center;
}

.escape-btn {
    background: none;
    border: 1px solid #333;
    color: #555;
    padding: 10px 20px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.3s;
}

.escape-btn:hover {
    color: #888;
    border-color: #555;
}
</style>
