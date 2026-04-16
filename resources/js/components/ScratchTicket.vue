<template>
    <div class="scratch-overlay" @click.self="$emit('close')">
        <div class="scratch-modal">
            <button class="close-btn" @click="$emit('close')">X</button>

            <h2>{{ ticket.name }} - Risque {{ ticket.lossPercentage }}%</h2>

            <div class="scratch-area" ref="scratchArea">
                <canvas
                    ref="canvas"
                    @mousedown="startScratching"
                    @mousemove="scratch"
                    @mouseup="stopScratching"
                    @mouseleave="stopScratching"
                    @touchstart.prevent="startScratching"
                    @touchmove.prevent="scratchTouch"
                    @touchend="stopScratching"
                ></canvas>

                <div class="result-layer" :class="resultClass">
                    <div class="icons-container">
                        <div
                            v-for="(icon, index) in resultIcons"
                            :key="index"
                            class="icon-slot"
                            :class="{ 'matching': isMatching(index) }"
                        >
                            <span class="icon">{{ icon.emoji }}</span>
                            <span class="icon-name">{{ icon.name }}</span>
                        </div>
                    </div>

                    <div v-if="revealed" class="result-message">
                        <template v-if="jackpot">
                            <span class="jackpot-text">JACKPOT!</span>
                            <span class="result-amount">+{{ ticket.potentialGain * 2 }}$</span>
                        </template>
                        <template v-else-if="won">
                            <span class="win-text">GAGNE!</span>
                            <span class="result-amount">+{{ ticket.potentialGain }}$</span>
                        </template>
                        <template v-else>
                            <span class="lose-text">PERDU</span>
                            <span class="result-amount">-500$</span>
                        </template>
                    </div>
                </div>
            </div>

            <div class="legend">
                <span>2 identiques = Gain</span>
                <span>3 identiques = JACKPOT x2</span>
            </div>

            <div class="progress-bar">
                <div class="progress" :style="{ width: scratchPercentage + '%' }"></div>
            </div>
            <p class="progress-text">{{ Math.round(scratchPercentage) }}% gratté (75% pour révéler)</p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close', 'result']);

const canvas = ref(null);
const scratchArea = ref(null);
const ctx = ref(null);
const isScratching = ref(false);
const scratchPercentage = ref(0);
const revealed = ref(false);
const resultIcons = ref([]);
const won = ref(false);
const jackpot = ref(false);

const icons = [
    { emoji: '💎', name: 'Diamant' },
    { emoji: '🍀', name: 'Trèfle' },
    { emoji: '⭐', name: 'Étoile' },
    { emoji: '🎰', name: 'Slot' },
    { emoji: '💰', name: 'Sac' },
    { emoji: '🍒', name: 'Cerise' },
    { emoji: '7️⃣', name: 'Sept' },
    { emoji: '🔔', name: 'Cloche' },
];

const resultClass = computed(() => {
    if (!revealed.value) return '';
    if (jackpot.value) return 'jackpot-bg';
    if (won.value) return 'win-bg';
    return 'lose-bg';
});

const isMatching = (index) => {
    if (!revealed.value) return false;
    const currentIcon = resultIcons.value[index].emoji;
    const matchCount = resultIcons.value.filter(i => i.emoji === currentIcon).length;
    return matchCount >= 2;
};

const generateResult = () => {
    const random = Math.random() * 100;
    const isWin = random > props.ticket.lossPercentage;

    if (isWin) {
        // Gagné : on génère 2 ou 3 icônes identiques
        const isJackpot = Math.random() < 0.3; // 30% chance de jackpot si gagné
        const winningIcon = icons[Math.floor(Math.random() * icons.length)];

        if (isJackpot) {
            // Jackpot : 3 icônes identiques
            resultIcons.value = [winningIcon, winningIcon, winningIcon];
            jackpot.value = true;
            won.value = true;
        } else {
            // Gain normal : 2 icônes identiques
            const otherIcon = icons.filter(i => i.emoji !== winningIcon.emoji)[Math.floor(Math.random() * (icons.length - 1))];
            const positions = [winningIcon, winningIcon, otherIcon];
            // Mélanger
            resultIcons.value = positions.sort(() => Math.random() - 0.5);
            won.value = true;
            jackpot.value = false;
        }
    } else {
        // Perdu : 3 icônes différentes
        const shuffled = [...icons].sort(() => Math.random() - 0.5);
        resultIcons.value = shuffled.slice(0, 3);
        won.value = false;
        jackpot.value = false;
    }
};

onMounted(() => {
    const canvasEl = canvas.value;
    const area = scratchArea.value;

    canvasEl.width = area.offsetWidth;
    canvasEl.height = area.offsetHeight;

    ctx.value = canvasEl.getContext('2d');

    // Fond gris avec texture
    ctx.value.fillStyle = '#888';
    ctx.value.fillRect(0, 0, canvasEl.width, canvasEl.height);

    // Texture grattable
    ctx.value.fillStyle = '#999';
    for (let i = 0; i < 200; i++) {
        const x = Math.random() * canvasEl.width;
        const y = Math.random() * canvasEl.height;
        ctx.value.fillRect(x, y, 2, 2);
    }

    // Texte "Grattez ici"
    ctx.value.fillStyle = '#666';
    ctx.value.font = 'bold 24px Arial';
    ctx.value.textAlign = 'center';
    ctx.value.fillText('GRATTEZ ICI', canvasEl.width / 2, canvasEl.height / 2);

    // Générer le résultat
    generateResult();
});

const getPosition = (e) => {
    const rect = canvas.value.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    return {
        x: clientX - rect.left,
        y: clientY - rect.top,
    };
};

const startScratching = (e) => {
    isScratching.value = true;
    const pos = getPosition(e);
    ctx.value.globalCompositeOperation = 'destination-out';
    ctx.value.beginPath();
    ctx.value.arc(pos.x, pos.y, 30, 0, Math.PI * 2);
    ctx.value.fill();
    updatePercentage();
};

const scratch = (e) => {
    if (!isScratching.value || revealed.value) return;

    const pos = getPosition(e);
    ctx.value.globalCompositeOperation = 'destination-out';
    ctx.value.beginPath();
    ctx.value.arc(pos.x, pos.y, 30, 0, Math.PI * 2);
    ctx.value.fill();
    updatePercentage();
};

const scratchTouch = (e) => {
    if (!isScratching.value || revealed.value) return;

    const pos = getPosition(e);
    ctx.value.globalCompositeOperation = 'destination-out';
    ctx.value.beginPath();
    ctx.value.arc(pos.x, pos.y, 30, 0, Math.PI * 2);
    ctx.value.fill();
    updatePercentage();
};

const stopScratching = () => {
    isScratching.value = false;
};

const updatePercentage = () => {
    const imageData = ctx.value.getImageData(0, 0, canvas.value.width, canvas.value.height);
    const pixels = imageData.data;
    let transparent = 0;

    for (let i = 3; i < pixels.length; i += 4) {
        if (pixels[i] === 0) transparent++;
    }

    const total = pixels.length / 4;
    scratchPercentage.value = (transparent / total) * 100;

    if (scratchPercentage.value >= 75 && !revealed.value) {
        revealed.value = true;

        const winAmount = jackpot.value
            ? props.ticket.potentialGain * 2
            : (won.value ? props.ticket.potentialGain : 0);

        emit('result', {
            won: won.value,
            jackpot: jackpot.value,
            amount: winAmount,
            icons: resultIcons.value,
        });

        // Clear remaining scratch layer
        ctx.value.clearRect(0, 0, canvas.value.width, canvas.value.height);
        scratchPercentage.value = 100;
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
    background: linear-gradient(135deg, #2d2d5a 0%, #1e1e3f 100%);
    padding: 30px;
    border-radius: 20px;
    text-align: center;
    position: relative;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
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
    margin-bottom: 20px;
}

.scratch-area {
    width: 400px;
    height: 220px;
    position: relative;
    border-radius: 15px;
    overflow: hidden;
    margin: 0 auto;
    border: 4px solid #ffd700;
}

canvas {
    position: absolute;
    top: 0;
    left: 0;
    cursor: crosshair;
    z-index: 2;
}

.result-layer {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    transition: background 0.5s;
    padding: 20px;
}

.result-layer.win-bg {
    background: linear-gradient(135deg, #2d5a2d 0%, #1e3f1e 100%);
}

.result-layer.lose-bg {
    background: linear-gradient(135deg, #5a2d2d 0%, #3f1e1e 100%);
}

.result-layer.jackpot-bg {
    background: linear-gradient(135deg, #5a4d2d 0%, #3f351e 100%);
    animation: jackpotPulse 0.5s infinite alternate;
}

@keyframes jackpotPulse {
    from { box-shadow: inset 0 0 30px rgba(255, 215, 0, 0.3); }
    to { box-shadow: inset 0 0 50px rgba(255, 215, 0, 0.6); }
}

.icons-container {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.icon-slot {
    width: 90px;
    height: 90px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    border: 3px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s;
}

.icon-slot.matching {
    border-color: #ffd700;
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
    background: rgba(255, 215, 0, 0.2);
}

.icon {
    font-size: 40px;
}

.icon-name {
    font-size: 12px;
    color: #888;
    margin-top: 5px;
}

.result-message {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}

.jackpot-text {
    font-size: 32px;
    font-weight: bold;
    color: #ffd700;
    text-shadow: 0 0 20px #ffd700;
    animation: jackpotText 0.3s infinite alternate;
}

@keyframes jackpotText {
    from { transform: scale(1); }
    to { transform: scale(1.1); }
}

.win-text {
    font-size: 28px;
    font-weight: bold;
    color: #4ade80;
    text-shadow: 0 0 10px #4ade80;
}

.lose-text {
    font-size: 28px;
    font-weight: bold;
    color: #f87171;
}

.result-amount {
    font-size: 24px;
    font-weight: bold;
    color: white;
}

.legend {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin-top: 15px;
    color: #888;
    font-size: 13px;
}

.progress-bar {
    width: 400px;
    height: 15px;
    background: #333;
    border-radius: 10px;
    margin: 15px auto 10px;
    overflow: hidden;
}

.progress {
    height: 100%;
    background: linear-gradient(90deg, #ffd700 0%, #ffaa00 100%);
    transition: width 0.1s;
}

.progress-text {
    color: #888;
    font-size: 14px;
}
</style>
