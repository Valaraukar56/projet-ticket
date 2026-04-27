<template>
    <div class="welcome-screen" @click="handleClick">
        <div class="door-frame">
            <div class="door" :class="{ opening: isOpening }">
                <div class="door-handle"></div>
                <div class="door-sign">
                    <span class="neon-text">TICKET SCRATCH</span>
                    <span class="subtitle">{{ t('welcome.tagline') }}</span>
                </div>
            </div>
        </div>
        <p class="hint" :class="{ hidden: isOpening }">{{ t('welcome.hint') }}</p>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from '../i18n.js';

const { t } = useI18n();

const emit = defineEmits(['open']);
const isOpening = ref(false);

const handleClick = () => {
    if (isOpening.value) return;
    isOpening.value = true;
    setTimeout(() => {
        emit('open');
    }, 1000);
};
</script>

<style scoped>
.welcome-screen {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background: linear-gradient(180deg, #0f0f23 0%, #1a1a3e 100%);
    cursor: pointer;
}

.door-frame {
    width: 280px;
    height: 450px;
    background: #2d2d44;
    border-radius: 8px 8px 0 0;
    padding: 15px;
    box-shadow: 0 0 50px rgba(255, 215, 0, 0.3);
}

.door {
    width: 100%;
    height: 100%;
    background: linear-gradient(180deg, #8B4513 0%, #654321 100%);
    border-radius: 4px 4px 0 0;
    position: relative;
    transform-origin: left center;
    transition: transform 1s ease-in-out;
    box-shadow: inset -5px 0 15px rgba(0, 0, 0, 0.3);
}

.door.opening {
    transform: perspective(1000px) rotateY(-85deg);
}

.door-handle {
    position: absolute;
    right: 20px;
    top: 50%;
    width: 15px;
    height: 40px;
    background: linear-gradient(180deg, #ffd700 0%, #b8860b 100%);
    border-radius: 3px;
    box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
}

.door-sign {
    position: absolute;
    top: 30%;
    left: 50%;
    transform: translateX(-50%);
    text-align: center;
}

.neon-text {
    font-size: 24px;
    font-weight: bold;
    color: #fff;
    text-shadow:
        0 0 10px #ff00ff,
        0 0 20px #ff00ff,
        0 0 30px #ff00ff,
        0 0 40px #ff00ff;
    display: block;
    letter-spacing: 2px;
}

.subtitle {
    display: block;
    margin-top: 10px;
    font-size: 14px;
    color: #ffd700;
    text-shadow: 0 0 10px #ffd700;
}

.hint {
    margin-top: 30px;
    color: #888;
    font-size: 16px;
    animation: pulse 2s infinite;
    transition: opacity 0.3s;
}

.hint.hidden {
    opacity: 0;
}

@keyframes pulse {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 1; }
}
</style>
