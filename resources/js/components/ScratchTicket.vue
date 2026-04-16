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
                    <template v-if="revealed">
                        <div v-if="won" class="win">
                            <span class="result-icon">🎉</span>
                            <span class="result-text">GAGNE!</span>
                            <span class="result-amount">+{{ ticket.potentialGain }}$</span>
                        </div>
                        <div v-else class="lose">
                            <span class="result-icon">😢</span>
                            <span class="result-text">PERDU</span>
                            <span class="result-amount">-500$</span>
                        </div>
                    </template>
                    <template v-else>
                        <span class="scratch-hint">Grattez ici!</span>
                    </template>
                </div>
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
const won = ref(false);

const resultClass = computed(() => {
    if (!revealed.value) return '';
    return won.value ? 'win-bg' : 'lose-bg';
});

onMounted(() => {
    const canvasEl = canvas.value;
    const area = scratchArea.value;

    canvasEl.width = area.offsetWidth;
    canvasEl.height = area.offsetHeight;

    ctx.value = canvasEl.getContext('2d');
    ctx.value.fillStyle = '#888';
    ctx.value.fillRect(0, 0, canvasEl.width, canvasEl.height);

    // Add scratch texture
    ctx.value.fillStyle = '#999';
    for (let i = 0; i < 100; i++) {
        const x = Math.random() * canvasEl.width;
        const y = Math.random() * canvasEl.height;
        ctx.value.fillRect(x, y, 3, 3);
    }

    // Determine result
    const random = Math.random() * 100;
    won.value = random > props.ticket.lossPercentage;
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
    ctx.value.arc(pos.x, pos.y, 25, 0, Math.PI * 2);
    ctx.value.fill();
    updatePercentage();
};

const scratch = (e) => {
    if (!isScratching.value || revealed.value) return;

    const pos = getPosition(e);
    ctx.value.globalCompositeOperation = 'destination-out';
    ctx.value.beginPath();
    ctx.value.arc(pos.x, pos.y, 25, 0, Math.PI * 2);
    ctx.value.fill();
    updatePercentage();
};

const scratchTouch = (e) => {
    if (!isScratching.value || revealed.value) return;

    const pos = getPosition(e);
    ctx.value.globalCompositeOperation = 'destination-out';
    ctx.value.beginPath();
    ctx.value.arc(pos.x, pos.y, 25, 0, Math.PI * 2);
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
        emit('result', {
            won: won.value,
            amount: won.value ? props.ticket.potentialGain : 0,
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
    width: 350px;
    height: 200px;
    position: relative;
    border-radius: 15px;
    overflow: hidden;
    margin: 0 auto;
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
    justify-content: center;
    align-items: center;
    background: linear-gradient(135deg, #333 0%, #222 100%);
    transition: background 0.5s;
}

.result-layer.win-bg {
    background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
}

.result-layer.lose-bg {
    background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
}

.scratch-hint {
    color: #666;
    font-size: 24px;
    font-weight: bold;
}

.win, .lose {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.result-icon {
    font-size: 50px;
}

.result-text {
    font-size: 32px;
    font-weight: bold;
    color: white;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

.result-amount {
    font-size: 24px;
    font-weight: bold;
    color: white;
}

.progress-bar {
    width: 350px;
    height: 20px;
    background: #333;
    border-radius: 10px;
    margin: 20px auto 10px;
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
