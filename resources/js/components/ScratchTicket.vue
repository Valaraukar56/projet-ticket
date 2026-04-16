<template>
    <div class="scratch-overlay" @click.self="$emit('close')">
        <div class="scratch-modal">
            <button class="close-btn" @click="$emit('close')">X</button>

            <h2>{{ ticket.name }} - Prix: {{ ticket.price }}$</h2>

            <div class="scratch-zones">
                <div
                    v-for="(icon, index) in resultIcons"
                    :key="index"
                    class="scratch-zone"
                    :class="{
                        'revealed': revealedIcons[index],
                        'matching': revealedIcons[index] && isMatching(index)
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

            <div v-if="allRevealed" class="result-message" :class="resultClass">
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

            <div class="legend">
                <span>2 identiques = {{ ticket.baseGain }}$</span>
                <span>3 identiques = JACKPOT {{ ticket.jackpotGain }}$</span>
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
});

const emit = defineEmits(['close', 'result']);

const canvasRefs = ref([]);
const ctxRefs = ref([]);
const scratchPercentages = reactive([0, 0, 0]);
const revealedIcons = reactive([false, false, false]);
const resultIcons = ref([]);
const won = ref(false);
const jackpot = ref(false);
const resultEmitted = ref(false);

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

const revealedCount = computed(() => revealedIcons.filter(r => r).length);
const allRevealed = computed(() => revealedIcons.every(r => r));

const resultClass = computed(() => {
    if (jackpot.value) return 'jackpot';
    if (won.value) return 'win';
    return 'lose';
});

const isMatching = (index) => {
    const currentIcon = resultIcons.value[index].emoji;
    const matchCount = resultIcons.value.filter(i => i.emoji === currentIcon).length;
    return matchCount >= 2;
};

const generateResult = () => {
    const random = Math.random() * 100;
    const isWin = random > props.ticket.lossPercentage;

    if (isWin) {
        const isJackpot = Math.random() < 0.3;
        const winningIcon = icons[Math.floor(Math.random() * icons.length)];

        if (isJackpot) {
            resultIcons.value = [winningIcon, winningIcon, winningIcon];
            jackpot.value = true;
            won.value = true;
        } else {
            const otherIcon = icons.filter(i => i.emoji !== winningIcon.emoji)[Math.floor(Math.random() * (icons.length - 1))];
            const positions = [winningIcon, winningIcon, otherIcon];
            resultIcons.value = positions.sort(() => Math.random() - 0.5);
            won.value = true;
            jackpot.value = false;
        }
    } else {
        const shuffled = [...icons].sort(() => Math.random() - 0.5);
        resultIcons.value = shuffled.slice(0, 3);
        won.value = false;
        jackpot.value = false;
    }
};

const initCanvas = (index) => {
    const canvas = canvasRefs.value[index];
    if (!canvas) return;

    canvas.width = 100;
    canvas.height = 100;

    const ctx = canvas.getContext('2d');
    ctxRefs.value[index] = ctx;

    // Fond gris
    ctx.fillStyle = '#888';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Texture
    ctx.fillStyle = '#999';
    for (let i = 0; i < 30; i++) {
        const x = Math.random() * canvas.width;
        const y = Math.random() * canvas.height;
        ctx.fillRect(x, y, 2, 2);
    }

    // Point d'interrogation
    ctx.fillStyle = '#666';
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
            const winAmount = jackpot.value
                ? props.ticket.jackpotGain
                : (won.value ? props.ticket.baseGain : 0);

            emit('result', {
                won: won.value,
                jackpot: jackpot.value,
                amount: winAmount,
                icons: resultIcons.value,
            });
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
    background: linear-gradient(135deg, #2d2d5a 0%, #1e1e3f 100%);
    padding: 30px;
    border-radius: 20px;
    text-align: center;
    position: relative;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    min-width: 420px;
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
    gap: 30px;
    margin-top: 15px;
    color: #888;
    font-size: 13px;
}

.progress-info {
    margin-top: 10px;
    color: #ffd700;
    font-size: 14px;
}
</style>
