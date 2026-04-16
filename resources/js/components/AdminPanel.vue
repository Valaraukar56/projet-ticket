<template>
    <div class="admin-overlay">
        <div class="admin-panel">
            <div class="admin-header">
                <h1>🛠️ Panel Admin</h1>
                <button class="close-btn" @click="$emit('close')">✕</button>
            </div>

            <!-- Stats -->
            <div class="stats-bar">
                <div class="stat-card">
                    <span class="stat-icon">👤</span>
                    <div class="stat-info">
                        <span class="stat-value">{{ stats.accounts_created }}</span>
                        <span class="stat-label">Comptes créés</span>
                    </div>
                </div>
                <div class="stat-card danger">
                    <span class="stat-icon">💀</span>
                    <div class="stat-info">
                        <span class="stat-value">{{ stats.accounts_destroyed }}</span>
                        <span class="stat-label">Comptes YOLO</span>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">✅</span>
                    <div class="stat-info">
                        <span class="stat-value">{{ stats.active_users }}</span>
                        <span class="stat-label">Utilisateurs actifs</span>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">🔑</span>
                    <div class="stat-info">
                        <span class="stat-value">{{ stats.total_logins }}</span>
                        <span class="stat-label">Connexions totales</span>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="admin-tabs">
                <button
                    :class="{ active: activeTab === 'users' }"
                    @click="activeTab = 'users'"
                >
                    👥 Utilisateurs
                </button>
                <button
                    :class="{ active: activeTab === 'tickets' }"
                    @click="activeTab = 'tickets'"
                >
                    🎫 Tickets
                </button>
            </div>

            <!-- Users Tab -->
            <div v-if="activeTab === 'users'" class="tab-content">
                <div v-if="loadingUsers" class="loading">Chargement...</div>
                <div v-else class="users-list">
                    <div
                        v-for="user in users"
                        :key="user.id"
                        class="user-card"
                        :class="{ admin: user.roles.includes('admin') }"
                    >
                        <div class="user-info">
                            <span class="user-name">
                                {{ user.name }}
                                <span v-if="user.roles.includes('admin')" class="admin-badge">ADMIN</span>
                            </span>
                            <span class="user-email">{{ user.email }}</span>
                            <span class="user-date">Inscrit le {{ user.created_at }}</span>
                        </div>
                        <div class="user-balance">
                            <input
                                type="number"
                                v-model.number="user.balance"
                                @change="updateBalance(user)"
                                :disabled="user.roles.includes('admin')"
                            />
                            <span class="currency">$</span>
                        </div>
                        <button
                            v-if="!user.roles.includes('admin')"
                            class="delete-btn"
                            @click="deleteUser(user)"
                        >
                            🗑️
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tickets Tab -->
            <div v-if="activeTab === 'tickets'" class="tab-content">
                <div v-if="loadingTickets" class="loading">Chargement...</div>
                <div v-else class="all-settings">
                    <!-- Section YOLO -->
                    <div class="settings-section">
                        <h3 class="section-title">🎰 Machine à Sous YOLO</h3>
                        <div class="slots-grid">
                            <div
                                v-for="(data, key) in slotSettings"
                                :key="key"
                                class="slot-card"
                                :class="{ 'slot-danger': key === 'bomb' || key === 'lose' }"
                            >
                                <div class="slot-icon">{{ data.icon }}</div>
                                <div class="slot-name">{{ data.name }}</div>
                                <div class="slot-field" v-if="key !== 'bomb' && key !== 'lose'">
                                    <label>Gain ($)</label>
                                    <input type="number" v-model.number="data.gain" min="0" />
                                </div>
                                <div class="slot-info" v-else>
                                    <span v-if="key === 'bomb'">Supprime le compte</span>
                                    <span v-else>Aucun gain</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Tickets -->
                    <div class="settings-section">
                        <h3 class="section-title">🎫 Tickets à Gratter</h3>
                        <div v-for="(tickets, category) in ticketSettings" :key="category" class="category-block">
                            <h4 class="category-name">{{ getCategoryIcon(category) }} {{ getCategoryName(category) }}</h4>
                            <div class="tickets-grid">
                                <div v-for="ticket in tickets" :key="ticket.id" class="ticket-card">
                                    <div class="ticket-name">{{ ticket.name }}</div>
                                    <div class="ticket-fields">
                                        <label>
                                            <span>Prix</span>
                                            <input type="number" v-model.number="ticket.price" min="1" />
                                        </label>
                                        <label>
                                            <span>% Perte</span>
                                            <input type="number" v-model.number="ticket.lossPercentage" min="0" max="100" />
                                        </label>
                                        <label>
                                            <span>Gain</span>
                                            <input type="number" v-model.number="ticket.baseGain" min="0" />
                                        </label>
                                        <label>
                                            <span>Jackpot</span>
                                            <input type="number" v-model.number="ticket.jackpotGain" min="0" />
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button class="save-btn" @click="saveTicketSettings" :disabled="savingTickets">
                        {{ savingTickets ? 'Sauvegarde...' : '💾 Sauvegarder tout' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

defineEmits(['close']);

const activeTab = ref('users');
const users = ref([]);
const loadingUsers = ref(true);
const slotSettings = ref({});
const ticketSettings = ref({});
const loadingTickets = ref(true);
const savingTickets = ref(false);
const stats = ref({
    accounts_created: 0,
    accounts_destroyed: 0,
    active_users: 0,
    total_logins: 0,
});

const getCSRFToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.content;
};

const getCategoryIcon = (category) => {
    const icons = { metro: '🚇', bus: '🚌', train: '🚄', loterie: '🎰' };
    return icons[category] || '🎫';
};

const getCategoryName = (category) => {
    const names = { metro: 'Ticket Métro', bus: 'Bus', train: 'Train', loterie: 'Loterie' };
    return names[category] || category;
};

const fetchStats = async () => {
    try {
        const response = await fetch('/api/admin/stats');
        const data = await response.json();
        stats.value = data;
    } catch (e) {
        console.error('Erreur chargement stats:', e);
    }
};

const fetchUsers = async () => {
    loadingUsers.value = true;
    try {
        const response = await fetch('/api/admin/users');
        const data = await response.json();
        users.value = data.users;
    } catch (e) {
        console.error('Erreur chargement users:', e);
    } finally {
        loadingUsers.value = false;
    }
};

const fetchTicketSettings = async () => {
    loadingTickets.value = true;
    try {
        const response = await fetch('/api/admin/tickets');
        const data = await response.json();
        slotSettings.value = data.slots;
        ticketSettings.value = data.tickets;
    } catch (e) {
        console.error('Erreur chargement tickets:', e);
    } finally {
        loadingTickets.value = false;
    }
};

const updateBalance = async (user) => {
    try {
        await fetch(`/api/admin/users/${user.id}/balance`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken(),
            },
            body: JSON.stringify({ balance: user.balance }),
        });
    } catch (e) {
        console.error('Erreur update balance:', e);
    }
};

const deleteUser = async (user) => {
    if (!confirm(`Supprimer le compte de ${user.name} ?`)) return;

    try {
        await fetch(`/api/admin/users/${user.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': getCSRFToken(),
            },
        });
        users.value = users.value.filter(u => u.id !== user.id);
    } catch (e) {
        console.error('Erreur delete user:', e);
    }
};

const saveTicketSettings = async () => {
    savingTickets.value = true;
    try {
        await fetch('/api/admin/tickets', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken(),
            },
            body: JSON.stringify({
                slots: slotSettings.value,
                tickets: ticketSettings.value,
            }),
        });
        alert('Paramètres sauvegardés !');
    } catch (e) {
        console.error('Erreur save tickets:', e);
    } finally {
        savingTickets.value = false;
    }
};

onMounted(() => {
    fetchStats();
    fetchUsers();
    fetchTicketSettings();
});
</script>

<style scoped>
.admin-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 300;
    padding: 20px;
}

.admin-panel {
    background: linear-gradient(145deg, #1e1e2e 0%, #2d2d44 100%);
    border: 2px solid #ffd700;
    border-radius: 20px;
    width: 100%;
    max-width: 900px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    border-bottom: 1px solid rgba(255, 215, 0, 0.2);
}

.admin-header h1 {
    color: #ffd700;
    font-size: 24px;
    margin: 0;
}

.close-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    transition: all 0.2s;
}

.close-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Stats Bar */
.stats-bar {
    display: flex;
    gap: 15px;
    padding: 15px 25px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    overflow-x: auto;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 18px;
    background: rgba(255, 215, 0, 0.1);
    border: 1px solid rgba(255, 215, 0, 0.3);
    border-radius: 12px;
    min-width: fit-content;
}

.stat-card.danger {
    background: rgba(255, 0, 0, 0.1);
    border-color: rgba(255, 0, 0, 0.3);
}

.stat-card.danger .stat-value {
    color: #ff4444;
}

.stat-icon {
    font-size: 24px;
}

.stat-info {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 22px;
    font-weight: bold;
    color: #ffd700;
}

.stat-label {
    font-size: 11px;
    color: rgba(255, 255, 255, 0.5);
    white-space: nowrap;
}

.admin-tabs {
    display: flex;
    gap: 10px;
    padding: 15px 25px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.admin-tabs button {
    padding: 12px 24px;
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid transparent;
    border-radius: 10px;
    color: rgba(255, 255, 255, 0.6);
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.admin-tabs button.active {
    background: rgba(255, 215, 0, 0.1);
    border-color: #ffd700;
    color: #ffd700;
}

.tab-content {
    flex: 1;
    overflow-y: auto;
    padding: 20px 25px;
}

.loading {
    text-align: center;
    padding: 40px;
    color: rgba(255, 255, 255, 0.5);
}

/* Users List */
.users-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.user-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 20px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.user-card.admin {
    background: rgba(255, 215, 0, 0.1);
    border-color: rgba(255, 215, 0, 0.3);
}

.user-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.user-name {
    color: white;
    font-weight: 600;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.admin-badge {
    background: #ffd700;
    color: #1a1a2e;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: bold;
}

.user-email {
    color: rgba(255, 255, 255, 0.5);
    font-size: 13px;
}

.user-date {
    color: rgba(255, 255, 255, 0.3);
    font-size: 11px;
}

.user-balance {
    display: flex;
    align-items: center;
    gap: 5px;
}

.user-balance input {
    width: 100px;
    padding: 8px 12px;
    background: rgba(0, 0, 0, 0.3);
    border: 2px solid rgba(255, 215, 0, 0.3);
    border-radius: 8px;
    color: #ffd700;
    font-size: 16px;
    font-weight: bold;
    text-align: right;
}

.user-balance input:focus {
    outline: none;
    border-color: #ffd700;
}

.user-balance input:disabled {
    opacity: 0.5;
}

.currency {
    color: #ffd700;
    font-weight: bold;
    font-size: 18px;
}

.delete-btn {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(255, 0, 0, 0.2);
    border: 1px solid rgba(255, 0, 0, 0.3);
    font-size: 18px;
    cursor: pointer;
    transition: all 0.2s;
}

.delete-btn:hover {
    background: rgba(255, 0, 0, 0.4);
}

/* Slots Settings */
.slots-settings {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.section-title {
    color: #ffd700;
    font-size: 20px;
    text-align: center;
    margin: 0;
}

.slots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
}

.slot-card {
    background: rgba(0, 0, 0, 0.4);
    border-radius: 15px;
    padding: 20px;
    border: 2px solid rgba(255, 215, 0, 0.3);
    text-align: center;
    transition: all 0.2s;
}

.slot-card:hover {
    border-color: #ffd700;
    transform: translateY(-2px);
}

.slot-card.slot-danger {
    border-color: rgba(255, 0, 0, 0.3);
}

.slot-card.slot-danger:hover {
    border-color: #ff4444;
}

.slot-icon {
    font-size: 50px;
    margin-bottom: 10px;
}

.slot-name {
    color: white;
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 15px;
}

.slot-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.slot-field label {
    color: rgba(255, 255, 255, 0.6);
    font-size: 12px;
}

.slot-field input {
    width: 100%;
    padding: 10px;
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 215, 0, 0.3);
    border-radius: 8px;
    color: #ffd700;
    font-size: 18px;
    font-weight: bold;
    text-align: center;
}

.slot-field input:focus {
    outline: none;
    border-color: #ffd700;
}

.slot-info {
    color: rgba(255, 255, 255, 0.4);
    font-size: 12px;
    font-style: italic;
}

/* All Settings */
.all-settings {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.settings-section {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 15px;
    padding: 20px;
}

/* Category blocks */
.category-block {
    margin-bottom: 20px;
}

.category-name {
    color: #ffd700;
    font-size: 16px;
    margin: 0 0 12px 0;
    padding-bottom: 8px;
    border-bottom: 1px solid rgba(255, 215, 0, 0.2);
}

.tickets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
}

.ticket-card {
    background: rgba(0, 0, 0, 0.3);
    border-radius: 10px;
    padding: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.ticket-name {
    color: white;
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 10px;
    text-align: center;
}

.ticket-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.ticket-fields label {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.ticket-fields label span {
    color: rgba(255, 255, 255, 0.5);
    font-size: 10px;
}

.ticket-fields input {
    width: 100%;
    padding: 6px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 5px;
    color: #ffd700;
    font-size: 12px;
    text-align: center;
    font-weight: bold;
}

.ticket-fields input:focus {
    outline: none;
    border-color: #ffd700;
}

.save-btn {
    padding: 15px 30px;
    background: linear-gradient(135deg, #ffd700, #ffaa00);
    border: none;
    border-radius: 10px;
    color: #1a1a2e;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
    align-self: center;
}

.save-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(255, 215, 0, 0.3);
}

.save-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>
