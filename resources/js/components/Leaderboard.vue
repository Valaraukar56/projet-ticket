<template>
    <div class="leaderboard-overlay" @click.self="$emit('close')">
        <div class="leaderboard-modal">
            <button class="close-btn" @click="$emit('close')">✕</button>

            <div class="leaderboard-header">
                <span class="trophy-icon">🏆</span>
                <h2>{{ t('leaderboard.title') }}</h2>
            </div>

            <div v-if="loading" class="loading">
                <span class="spinner"></span>
                {{ t('leaderboard.loading') }}
            </div>

            <div v-else class="leaderboard-list">
                <div
                    v-for="(player, index) in players"
                    :key="player.id"
                    class="player-row"
                    :class="{
                        'rank-1': index === 0,
                        'rank-2': index === 1,
                        'rank-3': index === 2,
                        'current-user': player.id === currentUserId
                    }"
                >
                    <span class="rank">
                        <template v-if="index === 0">🥇</template>
                        <template v-else-if="index === 1">🥈</template>
                        <template v-else-if="index === 2">🥉</template>
                        <template v-else>{{ index + 1 }}</template>
                    </span>
                    <span class="name">{{ player.name }}</span>
                    <span class="balance">{{ player.balance }}$</span>
                </div>

                <div v-if="players.length === 0" class="no-players">
                    {{ t('leaderboard.noPlayers') }}
                </div>
            </div>

            <button class="refresh-btn" @click="fetchLeaderboard" :disabled="loading">
                {{ t('leaderboard.refresh') }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from '../i18n.js';

const { t } = useI18n();

const props = defineProps({
    currentUserId: {
        type: Number,
        default: null,
    },
});

defineEmits(['close']);

const players = ref([]);
const loading = ref(true);

const fetchLeaderboard = async () => {
    loading.value = true;
    try {
        const response = await fetch('/api/leaderboard', { credentials: 'same-origin' });
        const data = await response.json();
        players.value = data.leaderboard;
    } catch (e) {
        console.error('Erreur leaderboard:', e);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchLeaderboard);
</script>

<style scoped>
.leaderboard-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 200;
    padding: 20px;
}

.leaderboard-modal {
    background: linear-gradient(145deg, #2d2d44 0%, #1f1f35 100%);
    padding: 30px;
    border-radius: 20px;
    width: 100%;
    max-width: 450px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    position: relative;
    border: 2px solid rgba(255, 215, 0, 0.2);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}

.close-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 35px;
    height: 35px;
    background: rgba(255, 255, 255, 0.1);
    border: none;
    border-radius: 50%;
    font-size: 18px;
    cursor: pointer;
    color: white;
    transition: all 0.2s;
}

.close-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

.leaderboard-header {
    text-align: center;
    margin-bottom: 25px;
}

.trophy-icon {
    font-size: 40px;
    display: block;
    margin-bottom: 10px;
}

h2 {
    color: #ffd700;
    font-size: 24px;
    margin: 0;
    text-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
}

.loading {
    text-align: center;
    padding: 40px;
    color: rgba(255, 255, 255, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 215, 0, 0.3);
    border-top-color: #ffd700;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.leaderboard-list {
    flex: 1;
    overflow-y: auto;
    margin-bottom: 20px;
}

.player-row {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    border-radius: 10px;
    margin-bottom: 8px;
    background: rgba(255, 255, 255, 0.05);
    transition: all 0.2s;
}

.player-row:hover {
    background: rgba(255, 255, 255, 0.1);
}

.player-row.rank-1 {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 215, 0, 0.1));
    border: 1px solid rgba(255, 215, 0, 0.3);
}

.player-row.rank-2 {
    background: linear-gradient(135deg, rgba(192, 192, 192, 0.2), rgba(192, 192, 192, 0.1));
    border: 1px solid rgba(192, 192, 192, 0.3);
}

.player-row.rank-3 {
    background: linear-gradient(135deg, rgba(205, 127, 50, 0.2), rgba(205, 127, 50, 0.1));
    border: 1px solid rgba(205, 127, 50, 0.3);
}

.player-row.current-user {
    background: linear-gradient(135deg, rgba(100, 200, 255, 0.2), rgba(100, 200, 255, 0.1));
    border: 1px solid rgba(100, 200, 255, 0.4);
}

.rank {
    width: 40px;
    font-size: 18px;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.8);
    text-align: center;
}

.name {
    flex: 1;
    color: white;
    font-weight: 600;
    font-size: 16px;
}

.balance {
    font-weight: 700;
    font-size: 16px;
    color: #2ecc71;
}

.no-players {
    text-align: center;
    padding: 40px;
    color: rgba(255, 255, 255, 0.5);
}

.refresh-btn {
    width: 100%;
    padding: 14px;
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    color: white;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.refresh-btn:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.3);
}

.refresh-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
