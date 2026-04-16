<template>
    <div class="ticket-overlay" @click.self="tryClose">
        <div class="metro-ticket" :style="{ '--line-color': lineColor }">
            <!-- Header du ticket -->
            <div class="ticket-header">
                <div class="ticket-logo">
                    <span class="metro-icon">M</span>
                    <span class="ticket-title">TICKET MÉTRO</span>
                </div>
                <div class="line-badge">
                    <span class="line-number">{{ lineNumber }}</span>
                </div>
            </div>

            <!-- Zone principale avec les 8 étoiles -->
            <div class="stars-zone">
                <div class="stars-title">GRATTEZ LES 8 ÉTOILES</div>
                <div class="stars-grid">
                    <div
                        v-for="(star, index) in stars"
                        :key="index"
                        class="star-container"
                        :class="{ 'revealed': star.revealed, 'winning': star.revealed && isWinningSymbol(star) }"
                    >
                        <canvas
                            :ref="el => starCanvases[index] = el"
                            class="star-canvas"
                            @mousemove="(e) => scratchStar(e, index)"
                            @touchmove.prevent="(e) => scratchStar(e, index)"
                        ></canvas>
                        <div class="star-content">
                            <span v-if="star.type === 'line'" class="star-symbol line-symbol">{{ lineNumber }}</span>
                            <span v-else class="star-symbol word-symbol">{{ star.value }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Zone inférieure: Gains + Bonus côte à côte -->
            <div class="bottom-zone">
                <!-- Zones de gains -->
                <div class="gains-zone">
                    <div class="gain-box" :class="{ 'won': game1Won && allStarsRevealed }">
                        <div class="gain-label">GAIN 1</div>
                        <div class="gain-amount">{{ gain1 }}€</div>
                        <div class="gain-condition">2x Ligne {{ lineNumber }}</div>
                    </div>
                    <div class="gain-box" :class="{ 'won': game2Won && allStarsRevealed }">
                        <div class="gain-label">GAIN 2</div>
                        <div class="gain-amount">{{ gain2 }}€</div>
                        <div class="gain-condition">2 mots identiques</div>
                    </div>
                </div>

                <!-- Zone Bonus -->
                <div class="bonus-zone">
                    <div class="bonus-title">⭐ BONUS ⭐</div>
                    <div class="bonus-stars">
                        <div
                            v-for="(bonus, index) in bonusSymbols"
                            :key="'bonus-' + index"
                            class="bonus-star-container"
                            :class="{ 'revealed': bonus.revealed }"
                        >
                            <canvas
                                :ref="el => bonusCanvases[index] = el"
                                class="bonus-canvas"
                                @mousemove="(e) => scratchBonus(e, index)"
                                @touchmove.prevent="(e) => scratchBonus(e, index)"
                            ></canvas>
                            <div class="bonus-content">
                                <span class="bonus-symbol" :style="{ color: bonus.color }">{{ bonus.symbol }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="bonus-gain" :class="{ 'won': bonusWon && allBonusRevealed }">
                        BONUS: {{ bonusGain }}€
                    </div>
                </div>
            </div>

            <!-- Résultat final -->
            <div v-if="allRevealed" class="result-zone" :class="{ 'winner': totalGain > 0 }">
                <div v-if="totalGain > 0" class="win-message">
                    <span class="win-text">🎉 FÉLICITATIONS ! 🎉</span>
                    <span class="win-amount">Vous gagnez {{ totalGain }}€</span>
                </div>
                <div v-else class="lose-message">
                    <span class="lose-text">Pas de gain cette fois</span>
                    <span class="lose-sub">Tentez à nouveau votre chance !</span>
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

// Couleurs des lignes de métro
const lineColors = {
    1: '#FFCD00', 2: '#003CA6', 3: '#837902', 4: '#CF009E',
    5: '#FF7E2E', 6: '#6ECA97', 7: '#FA9ABA', 8: '#E19BDF',
    9: '#B6BD00', 10: '#C9910D', 11: '#704B1C', 12: '#007852',
    13: '#6EC4E8', 14: '#62259D'
};

// Mots possibles
const words = ['PONCTUEL', 'RAPIDE', 'DIRECT', 'CONFORT', 'CALME', 'FIABLE', 'EXPRESS', 'FLUIDE'];

// Symboles bonus par ligne
const bonusSymbolsByLine = {
    1: { symbol: '●', color: '#FFCD00' },
    2: { symbol: '●', color: '#003CA6' },
    3: { symbol: '●', color: '#837902' },
    4: { symbol: '●', color: '#CF009E' },
    5: { symbol: '●', color: '#FF7E2E' },
    6: { symbol: '●', color: '#6ECA97' },
    7: { symbol: '●', color: '#FA9ABA' },
    8: { symbol: '●', color: '#E19BDF' },
    9: { symbol: '●', color: '#B6BD00' },
    10: { symbol: '●', color: '#C9910D' },
    11: { symbol: '●', color: '#704B1C' },
    12: { symbol: '●', color: '#007852' },
    13: { symbol: '●', color: '#6EC4E8' },
    14: { symbol: '●', color: '#62259D' },
};

// État du jeu
const lineNumber = ref(Math.floor(Math.random() * 14) + 1);
const lineColor = computed(() => lineColors[lineNumber.value]);

const stars = reactive([]);
const bonusSymbols = reactive([]);
const gain1 = ref(0);
const gain2 = ref(0);
const bonusGain = ref(0);
const game1Won = ref(false);
const game2Won = ref(false);
const bonusWon = ref(false);
const resultEmitted = ref(false);

const starCanvases = ref([]);
const bonusCanvases = ref([]);
const starCtxs = ref([]);
const bonusCtxs = ref([]);
const starPercentages = reactive(Array(8).fill(0));
const bonusPercentages = reactive(Array(2).fill(0));

const allStarsRevealed = computed(() => stars.every(s => s.revealed));
const allBonusRevealed = computed(() => bonusSymbols.every(b => b.revealed));
const allRevealed = computed(() => allStarsRevealed.value && allBonusRevealed.value);
const totalGain = computed(() => {
    let total = 0;
    if (game1Won.value) total += gain1.value;
    if (game2Won.value) total += gain2.value;
    if (bonusWon.value) total += bonusGain.value;
    return total;
});
const canReplay = computed(() => props.balance >= props.ticket.price);

const isWinningSymbol = (star) => {
    if (star.type === 'line' && game1Won.value) return true;
    if (star.type === 'word' && game2Won.value) {
        const wordCounts = {};
        stars.forEach(s => {
            if (s.type === 'word') {
                wordCounts[s.value] = (wordCounts[s.value] || 0) + 1;
            }
        });
        return wordCounts[star.value] >= 2;
    }
    return false;
};

// Génération du ticket
const generateTicket = () => {
    // Probabilités de gain
    const rand = Math.random() * 100;
    let gainLevel = 0;

    if (rand < 68) gainLevel = 0;
    else if (rand < 83) gainLevel = 2;
    else if (rand < 91) gainLevel = 4;
    else if (rand < 95) gainLevel = 6;
    else if (rand < 97.5) gainLevel = 10;
    else if (rand < 99) gainLevel = 20;
    else if (rand < 99.7) gainLevel = 100;
    else if (rand < 99.95) gainLevel = 1000;
    else gainLevel = 25000;

    // Décider quel jeu gagne
    const gameChoice = Math.random();

    if (gainLevel === 0) {
        // Aucun gain
        game1Won.value = false;
        game2Won.value = false;
        bonusWon.value = false;
    } else if (gameChoice < 0.4) {
        // Gain via jeu 1
        game1Won.value = true;
        gain1.value = gainLevel;
        gain2.value = Math.floor(Math.random() * 3 + 1) * 2;
    } else if (gameChoice < 0.8) {
        // Gain via jeu 2
        game2Won.value = true;
        gain2.value = gainLevel;
        gain1.value = Math.floor(Math.random() * 3 + 1) * 2;
    } else {
        // Gain via bonus
        bonusWon.value = true;
        bonusGain.value = gainLevel;
        gain1.value = Math.floor(Math.random() * 3 + 1) * 2;
        gain2.value = Math.floor(Math.random() * 3 + 1) * 2;
    }

    if (!game1Won.value) gain1.value = Math.floor(Math.random() * 5 + 1) * 2;
    if (!game2Won.value) gain2.value = Math.floor(Math.random() * 5 + 1) * 2;
    if (!bonusWon.value) bonusGain.value = Math.floor(Math.random() * 5 + 1) * 5;

    // Générer les 8 étoiles
    generateStars();

    // Générer le bonus
    generateBonus();
};

const generateStars = () => {
    stars.length = 0;
    const shuffledWords = [...words].sort(() => Math.random() - 0.5);

    if (game1Won.value) {
        // 2 symboles de ligne + 6 mots différents
        stars.push({ type: 'line', value: lineNumber.value, revealed: false });
        stars.push({ type: 'line', value: lineNumber.value, revealed: false });
        for (let i = 0; i < 6; i++) {
            stars.push({ type: 'word', value: shuffledWords[i], revealed: false });
        }
    } else if (game2Won.value) {
        // 2 mots identiques + 1 ligne + 5 mots différents
        const winningWord = shuffledWords[0];
        stars.push({ type: 'word', value: winningWord, revealed: false });
        stars.push({ type: 'word', value: winningWord, revealed: false });
        stars.push({ type: 'line', value: lineNumber.value, revealed: false });
        for (let i = 1; i < 6; i++) {
            stars.push({ type: 'word', value: shuffledWords[i], revealed: false });
        }
    } else {
        // Aucun doublon - 1 ligne + 7 mots différents
        stars.push({ type: 'line', value: lineNumber.value, revealed: false });
        for (let i = 0; i < 7; i++) {
            stars.push({ type: 'word', value: shuffledWords[i], revealed: false });
        }
    }

    // Mélanger les étoiles
    stars.sort(() => Math.random() - 0.5);
};

const generateBonus = () => {
    bonusSymbols.length = 0;
    const lineBonus = bonusSymbolsByLine[lineNumber.value];

    if (bonusWon.value) {
        bonusSymbols.push({ ...lineBonus, revealed: false });
        bonusSymbols.push({ ...lineBonus, revealed: false });
    } else {
        bonusSymbols.push({ ...lineBonus, revealed: false });
        // Symbole différent
        const otherLine = ((lineNumber.value % 14) + 1);
        bonusSymbols.push({ ...bonusSymbolsByLine[otherLine], revealed: false });
    }
    bonusSymbols.sort(() => Math.random() - 0.5);
};

// Initialisation des canvas
const initStarCanvas = (index) => {
    const canvas = starCanvases.value[index];
    if (!canvas) return;

    canvas.width = 95;
    canvas.height = 95;

    const ctx = canvas.getContext('2d');
    starCtxs.value[index] = ctx;

    // Fond argenté avec texture
    const gradient = ctx.createRadialGradient(47, 47, 0, 47, 47, 50);
    gradient.addColorStop(0, '#d4d4d4');
    gradient.addColorStop(0.5, '#a8a8a8');
    gradient.addColorStop(1, '#888888');

    ctx.fillStyle = gradient;
    ctx.beginPath();
    drawStar(ctx, 47, 47, 5, 45, 22);
    ctx.fill();

    // Texture
    ctx.fillStyle = 'rgba(255,255,255,0.3)';
    for (let i = 0; i < 25; i++) {
        const x = Math.random() * 95;
        const y = Math.random() * 95;
        ctx.fillRect(x, y, 1, 1);
    }
};

const initBonusCanvas = (index) => {
    const canvas = bonusCanvases.value[index];
    if (!canvas) return;

    canvas.width = 55;
    canvas.height = 55;

    const ctx = canvas.getContext('2d');
    bonusCtxs.value[index] = ctx;

    const gradient = ctx.createRadialGradient(27, 27, 0, 27, 27, 27);
    gradient.addColorStop(0, '#ffd700');
    gradient.addColorStop(0.7, '#daa520');
    gradient.addColorStop(1, '#b8860b');

    ctx.fillStyle = gradient;
    ctx.beginPath();
    ctx.arc(27, 27, 25, 0, Math.PI * 2);
    ctx.fill();
};

const drawStar = (ctx, cx, cy, spikes, outerRadius, innerRadius) => {
    let rot = Math.PI / 2 * 3;
    let x = cx;
    let y = cy;
    const step = Math.PI / spikes;

    ctx.moveTo(cx, cy - outerRadius);
    for (let i = 0; i < spikes; i++) {
        x = cx + Math.cos(rot) * outerRadius;
        y = cy + Math.sin(rot) * outerRadius;
        ctx.lineTo(x, y);
        rot += step;

        x = cx + Math.cos(rot) * innerRadius;
        y = cy + Math.sin(rot) * innerRadius;
        ctx.lineTo(x, y);
        rot += step;
    }
    ctx.lineTo(cx, cy - outerRadius);
    ctx.closePath();
};

// Grattage
const scratchStar = (e, index) => {
    if (stars[index].revealed) return;

    const canvas = starCanvases.value[index];
    const ctx = starCtxs.value[index];
    if (!canvas || !ctx) return;

    const rect = canvas.getBoundingClientRect();
    const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
    const y = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top;

    ctx.globalCompositeOperation = 'destination-out';
    ctx.beginPath();
    ctx.arc(x, y, 12, 0, Math.PI * 2);
    ctx.fill();

    updateStarPercentage(index);
};

const scratchBonus = (e, index) => {
    if (bonusSymbols[index].revealed) return;

    const canvas = bonusCanvases.value[index];
    const ctx = bonusCtxs.value[index];
    if (!canvas || !ctx) return;

    const rect = canvas.getBoundingClientRect();
    const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
    const y = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top;

    ctx.globalCompositeOperation = 'destination-out';
    ctx.beginPath();
    ctx.arc(x, y, 10, 0, Math.PI * 2);
    ctx.fill();

    updateBonusPercentage(index);
};

const updateStarPercentage = (index) => {
    const canvas = starCanvases.value[index];
    const ctx = starCtxs.value[index];
    if (!canvas || !ctx) return;

    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    let transparent = 0;
    for (let i = 3; i < imageData.data.length; i += 4) {
        if (imageData.data[i] === 0) transparent++;
    }

    starPercentages[index] = (transparent / (imageData.data.length / 4)) * 100;

    if (starPercentages[index] >= 50 && !stars[index].revealed) {
        stars[index].revealed = true;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        checkResult();
    }
};

const updateBonusPercentage = (index) => {
    const canvas = bonusCanvases.value[index];
    const ctx = bonusCtxs.value[index];
    if (!canvas || !ctx) return;

    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    let transparent = 0;
    for (let i = 3; i < imageData.data.length; i += 4) {
        if (imageData.data[i] === 0) transparent++;
    }

    bonusPercentages[index] = (transparent / (imageData.data.length / 4)) * 100;

    if (bonusPercentages[index] >= 50 && !bonusSymbols[index].revealed) {
        bonusSymbols[index].revealed = true;
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
        for (let i = 0; i < 8; i++) initStarCanvas(i);
        for (let i = 0; i < 2; i++) initBonusCanvas(i);
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

.metro-ticket {
    width: 95vw;
    max-width: 950px;
    max-height: 95vh;
    overflow-y: auto;
    background: linear-gradient(145deg, #0a0a1a 0%, #1a1a3a 50%, #0a0a1a 100%);
    border: 4px solid #ffd700;
    border-radius: 16px;
    padding: 25px;
    position: relative;
    box-shadow:
        0 0 30px rgba(255, 215, 0, 0.3),
        inset 0 0 60px rgba(0, 0, 0, 0.5);
}

/* Header */
.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid rgba(255, 215, 0, 0.3);
}

.ticket-logo {
    display: flex;
    align-items: center;
    gap: 12px;
}

.metro-icon {
    width: 40px;
    height: 40px;
    background: var(--line-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 22px;
    color: white;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
}

.ticket-title {
    font-size: 20px;
    font-weight: bold;
    color: #ffd700;
    text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
    letter-spacing: 2px;
}

.line-badge {
    width: 50px;
    height: 50px;
    background: var(--line-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid white;
    box-shadow: 0 0 15px var(--line-color);
}

.line-number {
    font-size: 24px;
    font-weight: bold;
    color: white;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
}

/* Zone étoiles */
.stars-zone {
    margin-bottom: 10px;
}

.stars-title {
    text-align: center;
    color: #aaa;
    font-size: 11px;
    margin-bottom: 8px;
    letter-spacing: 1px;
}

.stars-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 12px;
    justify-items: center;
}

.star-container {
    width: 95px;
    height: 95px;
    position: relative;
    cursor: pointer;
}

.star-canvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 2;
}

.star-content {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1a1a3a, #2a2a5a);
    clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
}

.star-symbol {
    font-weight: bold;
    text-align: center;
}

.line-symbol {
    width: 42px;
    height: 42px;
    background: var(--line-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    border: 2px solid white;
}

.word-symbol {
    color: #fff;
    font-size: 9px;
    line-height: 1.2;
    max-width: 70px;
    text-transform: uppercase;
}

.star-container.revealed .star-content {
    animation: revealPop 0.3s ease-out;
}

.star-container.winning .star-content {
    box-shadow: 0 0 15px #ffd700;
    animation: winGlow 1s infinite alternate;
}

@keyframes revealPop {
    0% { transform: scale(0.8); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

@keyframes winGlow {
    from { filter: brightness(1); }
    to { filter: brightness(1.3); }
}

/* Zone inférieure */
.bottom-zone {
    display: flex;
    justify-content: center;
    align-items: stretch;
    gap: 20px;
    margin-bottom: 10px;
}

/* Zone gains */
.gains-zone {
    display: flex;
    gap: 15px;
}

.gain-box {
    background: linear-gradient(135deg, #1a1a2a, #2a2a4a);
    border: 2px solid #444;
    border-radius: 10px;
    padding: 12px 20px;
    text-align: center;
    transition: all 0.3s;
}

.gain-box.won {
    border-color: #ffd700;
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
    animation: winPulse 0.5s ease-out;
}

@keyframes winPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.gain-label {
    font-size: 12px;
    color: #888;
    margin-bottom: 5px;
}

.gain-amount {
    font-size: 24px;
    font-weight: bold;
    color: #ffd700;
    text-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
}

.gain-condition {
    font-size: 11px;
    color: #666;
    margin-top: 5px;
}

/* Zone bonus */
.bonus-zone {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 215, 0, 0.05));
    border: 2px solid rgba(255, 215, 0, 0.3);
    border-radius: 10px;
    padding: 10px 15px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.bonus-title {
    text-align: center;
    color: #ffd700;
    font-weight: bold;
    font-size: 12px;
    margin-bottom: 8px;
    letter-spacing: 1px;
}

.bonus-stars {
    display: flex;
    justify-content: center;
    gap: 25px;
    margin-bottom: 8px;
}

.bonus-star-container {
    width: 55px;
    height: 55px;
    position: relative;
    cursor: pointer;
}

.bonus-canvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 2;
    border-radius: 50%;
}

.bonus-content {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #1a1a2a;
    border-radius: 50%;
    border: 2px solid #333;
}

.bonus-symbol {
    font-size: 30px;
}

.bonus-gain {
    text-align: center;
    color: #888;
    font-size: 14px;
    padding: 8px 12px;
    border-radius: 8px;
    background: rgba(0,0,0,0.3);
    margin-top: 8px;
}

.bonus-gain.won {
    color: #ffd700;
    background: rgba(255, 215, 0, 0.2);
    font-weight: bold;
}

/* Résultat */
.result-zone {
    text-align: center;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 10px;
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
    gap: 5px;
}

.win-text {
    font-size: 16px;
    color: #ffd700;
}

.win-amount {
    font-size: 22px;
    font-weight: bold;
    color: #fff;
    text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
}

.lose-message {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.lose-text {
    font-size: 14px;
    color: #888;
}

.lose-sub {
    font-size: 12px;
    color: #666;
}

/* Boutons */
.action-buttons {
    display: flex;
    justify-content: center;
    gap: 10px;
}

.replay-btn, .close-btn {
    padding: 10px 18px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
}

.replay-btn {
    background: linear-gradient(135deg, #4ade80, #22c55e);
    color: #1a1a2e;
}

.replay-btn:hover:not(.disabled) {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(74, 222, 128, 0.4);
}

.replay-btn.disabled {
    background: #444;
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
@media (max-width: 800px) {
    .metro-ticket {
        max-width: 95vw;
        padding: 15px;
    }

    .stars-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
    }

    .star-container {
        width: 60px;
        height: 60px;
    }

    .bottom-zone {
        flex-direction: column;
        align-items: center;
    }

    .gains-zone {
        justify-content: center;
    }
}

@media (max-width: 500px) {
    .stars-grid {
        grid-template-columns: repeat(4, 1fr);
    }

    .star-container {
        width: 50px;
        height: 50px;
    }

    .word-symbol {
        font-size: 7px;
    }

    .line-symbol {
        width: 26px;
        height: 26px;
        font-size: 13px;
    }
}
</style>
