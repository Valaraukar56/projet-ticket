<template>
    <div class="casino-overlay">
        <!-- Popup gains offline -->
        <div v-if="showOfflinePopup" class="offline-popup" @click="showOfflinePopup = false">
            <div class="offline-content" @click.stop>
                <div class="offline-icon">💰</div>
                <h2>Gains pendant votre absence</h2>
                <div class="offline-amount">+{{ offlineEarnings }}$</div>
                <p>Vos machines ont travaillé pour vous !</p>
                <button class="offline-btn" @click="showOfflinePopup = false">Super !</button>
            </div>
        </div>

        <div class="casino-container">
            <!-- Bouton fermer -->
            <button class="close-btn" @click="$emit('close')">✕</button>

            <!-- Stats Bar -->
            <div class="stats-bar">
                Argent: <span class="money">{{ Math.floor(casinoMoney) }}</span> / <span class="money-cap">{{ maxMoney }}</span> $
            </div>

            <!-- Zone de jeu (Casino) -->
            <div class="casino-floor" ref="floorRef">
                <div
                    v-for="(machine, index) in machines"
                    :key="index"
                    class="slot-machine"
                    :class="{
                        'is-occupied': machine.occupied,
                        'is-next-upgrade': nextMachineToUpgrade === index
                    }"
                >
                    <div class="machine-level">Nv.{{ machine.level || 1 }}</div>
                    <div class="machine-earnings">{{ getMachineEarnings(machine) }}$/s</div>
                    <div class="gambler"></div>
                </div>
            </div>

            <!-- Menu latéral -->
            <div class="upgrade-menu">
                <h2>Gestion Casino</h2>

                <button
                    class="btn-upgrade btn-buy"
                    :disabled="!canBuyMachine"
                    @click="buySlotMachine"
                >
                    +1 Machine à sous (50$)
                </button>
                <p class="description">{{ machines.length }}/24 machines installées.</p>

                <button
                    class="btn-upgrade btn-hire"
                    :disabled="casinoMoney < 25 || !hasFreeMachine"
                    @click="hireGambler"
                >
                    Attirer un Joueur (25$)
                </button>
                <p class="description">Un client s'installe sur une machine libre.</p>

                <!-- Upgrade de la prochaine machine -->
                <div v-if="machines.length > 0 && nextMachineToUpgrade !== null" class="upgrade-section">
                    <div class="selected-info">
                        Prochaine : Machine #{{ nextMachineToUpgrade + 1 }}
                    </div>
                    <button
                        class="btn-upgrade btn-machine-upgrade"
                        :disabled="!canUpgradeNext"
                        @click="upgradeNextMachine"
                    >
                        ⬆ Upgrade ({{ nextUpgradeCost }}$)
                    </button>
                    <p class="description">
                        Nv.{{ machines[nextMachineToUpgrade].level || 1 }} → Nv.{{ (machines[nextMachineToUpgrade].level || 1) + 1 }}
                    </p>
                </div>

                <hr>

                <div class="transfer-buttons">
                    <button
                        class="btn-upgrade btn-collect"
                        :disabled="casinoMoney < 10"
                        @click="collectToBalance"
                    >
                        Retirer 10$ →
                    </button>
                    <button
                        class="btn-upgrade btn-deposit"
                        :disabled="!canDeposit"
                        @click="depositFromBalance"
                    >
                        ← Déposer 10$
                    </button>
                </div>
                <button
                    class="btn-upgrade btn-collect-all"
                    :disabled="casinoMoney < 1"
                    @click="collectAllToBalance"
                >
                    Retirer tout ({{ Math.floor(casinoMoney) }}$) →
                </button>
                <div class="custom-deposit">
                    <input
                        type="number"
                        v-model.number="customDepositAmount"
                        min="1"
                        :max="maxDepositAmount"
                        placeholder="Montant"
                        class="deposit-input"
                    />
                    <button
                        class="btn-upgrade btn-deposit-custom"
                        :disabled="!canCustomDeposit"
                        @click="customDeposit"
                    >
                        ← Déposer
                    </button>
                </div>
                <p class="description">Transférez entre votre solde et le casino.</p>

                <hr>

                <button
                    class="btn-upgrade btn-capacity"
                    :disabled="casinoMoney < capacityUpgradeCost"
                    @click="upgradeCapacity"
                >
                    +500$ Capacité ({{ capacityUpgradeCost }}$)
                </button>
                <p class="description">
                    Augmente le cap à {{ maxMoney + 500 }}$
                </p>

                <p class="status-msg">{{ statusMsg }}</p>
            </div>

            <!-- Textes flottants -->
            <div
                v-for="(text, index) in floatingTexts"
                :key="'float-' + index"
                class="floating-text"
                :style="{ left: text.x + 'px', top: text.y + 'px' }"
            >
                +{{ text.amount }}$
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    playerBalance: {
        type: Number,
        default: 0
    }
});

const emit = defineEmits(['close', 'collect', 'deposit']);

const casinoMoney = ref(100);
const maxMoney = ref(1000);
const machines = ref([]);
const statusMsg = ref('Chargement...');
const floatingTexts = ref([]);
const floorRef = ref(null);
const isLoaded = ref(false);
const offlineEarnings = ref(0);
const showOfflinePopup = ref(false);
const customDepositAmount = ref(null);

let earningIntervals = [];
let saveTimeout = null;

// Récupérer le token CSRF
const getCSRFToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.content;
};

// Charger les données depuis la BDD
const loadCasinoData = async () => {
    try {
        const response = await fetch('/api/casino', { credentials: 'same-origin' });
        if (response.ok) {
            const data = await response.json();
            casinoMoney.value = data.money;
            maxMoney.value = data.max_money || 1000;
            machines.value = data.machines || [{ occupied: false, level: 1 }];

            // Afficher les gains offline s'il y en a
            if (data.offline_earnings > 0) {
                offlineEarnings.value = data.offline_earnings;
                showOfflinePopup.value = true;
                statusMsg.value = `+${data.offline_earnings}$ gagnés pendant votre absence !`;
            } else {
                statusMsg.value = 'Bienvenue dans votre casino !';
            }

            // Relancer les gains pour les machines occupées
            machines.value.forEach((machine, index) => {
                if (machine.occupied) {
                    startEarning(index);
                }
            });
        }
    } catch (e) {
        console.error('Erreur chargement casino:', e);
        statusMsg.value = 'Erreur de chargement';
    } finally {
        isLoaded.value = true;
    }
};

// Sauvegarder les données en BDD
const saveCasinoData = async () => {
    if (!isLoaded.value) return;

    try {
        await fetch('/api/casino', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                money: casinoMoney.value,
                max_money: maxMoney.value,
                machines: machines.value,
            }),
        });
    } catch (e) {
        console.error('Erreur sauvegarde casino:', e);
    }
};

// Sauvegarde automatique avec debounce
const debouncedSave = () => {
    if (saveTimeout) clearTimeout(saveTimeout);
    saveTimeout = setTimeout(saveCasinoData, 1000);
};

// Watcher pour auto-save quand les données changent (seulement après chargement initial)
watch([casinoMoney, maxMoney, machines], () => {
    if (isLoaded.value) {
        debouncedSave();
    }
}, { deep: true });

// Coût de l'amélioration de capacité (fixe à 500$)
const capacityUpgradeCost = 500;

// Peut-on déposer ? (solde > 10 et pas au cap)
const canDeposit = computed(() => {
    return props.playerBalance >= 10 && casinoMoney.value < maxMoney.value;
});

// Montant max qu'on peut déposer
const maxDepositAmount = computed(() => {
    const spaceLeft = maxMoney.value - casinoMoney.value;
    return Math.min(props.playerBalance, spaceLeft);
});

// Peut-on faire un dépôt personnalisé ?
const canCustomDeposit = computed(() => {
    const amount = customDepositAmount.value;
    return amount > 0 && amount <= props.playerBalance && (casinoMoney.value + amount) <= maxMoney.value;
});

const hasFreeMachine = computed(() => {
    return machines.value.some(m => !m.occupied);
});

const maxMachines = 24; // 8 colonnes x 3 lignes

const canBuyMachine = computed(() => {
    return casinoMoney.value >= 50 && machines.value.length < maxMachines;
});

// Calcul des gains d'une machine selon son niveau
const getMachineEarnings = (machine) => {
    const level = machine.level || 1;
    // Niveau 1 = 1$, niveau 2 = 3$, niveau 3 = 5$, etc.
    return 1 + (level - 1) * 2;
};

// Trouver la prochaine machine à upgrader (gauche à droite, niveau le plus bas)
const nextMachineToUpgrade = computed(() => {
    if (machines.value.length === 0) return null;

    // Trouver le niveau minimum
    const minLevel = Math.min(...machines.value.map(m => m.level || 1));

    // Trouver la première machine (gauche à droite) avec ce niveau
    const index = machines.value.findIndex(m => (m.level || 1) === minLevel);

    return index >= 0 ? index : null;
});

// Coût pour upgrader la prochaine machine
const nextUpgradeCost = computed(() => {
    if (nextMachineToUpgrade.value === null) return 0;
    const machine = machines.value[nextMachineToUpgrade.value];
    const level = machine.level || 1;
    // Coût qui scale : 100 * niveau²
    return 100 * level * level;
});

// Peut-on upgrader la prochaine machine ?
const canUpgradeNext = computed(() => {
    if (nextMachineToUpgrade.value === null) return false;
    return casinoMoney.value >= nextUpgradeCost.value;
});

// Upgrader la prochaine machine
const upgradeNextMachine = () => {
    if (!canUpgradeNext.value) return;

    const index = nextMachineToUpgrade.value;
    const machine = machines.value[index];
    const cost = nextUpgradeCost.value;
    const newLevel = (machine.level || 1) + 1;

    casinoMoney.value -= cost;
    machine.level = newLevel;

    statusMsg.value = `Machine #${index + 1} améliorée au niveau ${newLevel} !`;
};

const buySlotMachine = () => {
    if (casinoMoney.value >= 50 && machines.value.length < maxMachines) {
        casinoMoney.value -= 50;
        machines.value.push({ occupied: false, level: 1 });
        statusMsg.value = `Machine installée ! (${machines.value.length}/${maxMachines})`;
    } else if (machines.value.length >= maxMachines) {
        statusMsg.value = 'Nombre maximum de machines atteint !';
    }
};

const hireGambler = () => {
    if (casinoMoney.value >= 25 && hasFreeMachine.value) {
        casinoMoney.value -= 25;
        const freeIndex = machines.value.findIndex(m => !m.occupied);
        if (freeIndex !== -1) {
            machines.value[freeIndex].occupied = true;
            statusMsg.value = 'Un joueur est arrivé !';
            startEarning(freeIndex);
        }
    } else if (!hasFreeMachine.value) {
        statusMsg.value = 'Toutes les machines sont occupées !';
    }
};

const startEarning = (machineIndex) => {
    const interval = setInterval(() => {
        const machine = machines.value[machineIndex];
        if (machine?.occupied) {
            // Respecter le cap
            if (casinoMoney.value < maxMoney.value) {
                const earnings = getMachineEarnings(machine);
                casinoMoney.value += earnings;
                // Re-vérifier le cap après ajout
                if (casinoMoney.value > maxMoney.value) {
                    casinoMoney.value = maxMoney.value;
                }
                showFloatingText(machineIndex, earnings);
            }
        }
    }, 1500);
    earningIntervals.push(interval);
};

const showFloatingText = (machineIndex, amount = 1) => {
    if (!floorRef.value) return;

    const machineElements = floorRef.value.querySelectorAll('.slot-machine');
    const machineEl = machineElements[machineIndex];
    if (!machineEl) return;

    const rect = machineEl.getBoundingClientRect();
    const containerRect = floorRef.value.parentElement.getBoundingClientRect();

    const text = {
        x: rect.left - containerRect.left + 25,
        y: rect.top - containerRect.top - 10,
        amount: amount,
        id: Date.now() + Math.random()
    };

    floatingTexts.value.push(text);

    setTimeout(() => {
        floatingTexts.value = floatingTexts.value.filter(t => t.id !== text.id);
    }, 800);
};

const collectToBalance = () => {
    if (casinoMoney.value >= 10) {
        casinoMoney.value -= 10;
        emit('collect', 10);
        statusMsg.value = '10$ transférés vers votre solde !';
    }
};

const collectAllToBalance = () => {
    const amount = Math.floor(casinoMoney.value);
    if (amount >= 1) {
        casinoMoney.value = 0;
        emit('collect', amount);
        statusMsg.value = `${amount}$ transférés vers votre solde !`;
    }
};

const depositFromBalance = () => {
    if (props.playerBalance >= 10 && casinoMoney.value < maxMoney.value) {
        const spaceLeft = Math.min(10, maxMoney.value - casinoMoney.value);
        if (spaceLeft > 0) {
            casinoMoney.value += spaceLeft;
            emit('deposit', spaceLeft);
            statusMsg.value = `${spaceLeft}$ déposés depuis votre solde !`;
        }
    }
};

const customDeposit = () => {
    const amount = customDepositAmount.value;
    if (amount > 0 && amount <= props.playerBalance && (casinoMoney.value + amount) <= maxMoney.value) {
        casinoMoney.value += amount;
        emit('deposit', amount);
        statusMsg.value = `${amount}$ déposés depuis votre solde !`;
        customDepositAmount.value = null;
    }
};

const upgradeCapacity = () => {
    if (casinoMoney.value >= capacityUpgradeCost) {
        casinoMoney.value -= capacityUpgradeCost;
        maxMoney.value += 500;
        statusMsg.value = `Capacité augmentée à ${maxMoney.value}$ !`;
    }
};

onMounted(() => {
    loadCasinoData();
});

onUnmounted(() => {
    // Arrêter les intervalles
    earningIntervals.forEach(interval => clearInterval(interval));
    // Sauvegarde finale
    if (saveTimeout) clearTimeout(saveTimeout);
    saveCasinoData();
});
</script>

<style scoped>
.casino-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.9);
    z-index: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.casino-container {
    --bg-floor: #802233;
    --bg-floor-light: #a12d41;
    --border-color: #1a1a1a;
    --gold: #ffd700;
    --machine-color: #5d2e8c;
    --skin-tone: #ffdbac;

    display: flex;
    width: 95vw;
    height: 90vh;
    max-width: 1400px;
    background: #222;
    border-radius: 20px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 0 60px rgba(255, 215, 0, 0.3);
    border: 3px solid var(--gold);
}

.close-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 0, 0, 0.8);
    border: 2px solid #fff;
    color: white;
    font-size: 20px;
    cursor: pointer;
    z-index: 600;
    transition: all 0.2s;
}

.close-btn:hover {
    background: #ff0000;
    transform: scale(1.1);
}

.stats-bar {
    position: absolute;
    top: 20px;
    left: 20px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 10px 20px;
    border-radius: 50px;
    border: 2px solid var(--gold);
    z-index: 100;
    font-size: 1.2rem;
    font-weight: bold;
}

.money {
    color: var(--gold);
}

.money-cap {
    color: #aaa;
    font-size: 0.9em;
}

.casino-floor {
    flex-grow: 1;
    background-color: var(--bg-floor);
    background-image: radial-gradient(var(--bg-floor-light) 20%, transparent 20%);
    background-size: 60px 60px;
    display: grid;
    grid-template-columns: repeat(8, 100px);
    grid-template-rows: repeat(3, 120px);
    grid-gap: 20px;
    padding: 80px 30px 50px;
    justify-content: center;
    align-content: center;
    overflow: hidden;
    position: relative;
    border-right: 4px solid var(--border-color);
}

.slot-machine {
    width: 80px;
    height: 50px;
    background: var(--machine-color);
    border: 4px solid var(--border-color);
    border-radius: 8px;
    position: relative;
    box-shadow: 0 6px 0 rgba(0, 0, 0, 0.4);
    transition: transform 0.1s;
}

.slot-machine::after {
    content: "";
    position: absolute;
    top: 5px;
    left: 10px;
    right: 10px;
    height: 15px;
    background: #333;
    border: 2px solid var(--border-color);
}

.gambler {
    width: 45px;
    height: 45px;
    background: var(--skin-tone);
    border: 4px solid var(--border-color);
    border-radius: 50%;
    position: absolute;
    top: 55px;
    left: 14px;
    display: none;
    z-index: 2;
    box-shadow: 0 4px 0 rgba(0, 0, 0, 0.2);
}

.is-occupied .gambler {
    display: block;
    animation: bobble 2s infinite ease-in-out;
}

.slot-machine.is-next-upgrade {
    box-shadow: 0 0 12px 4px rgba(255, 215, 0, 0.7);
    animation: pulseGlow 1.5s ease-in-out infinite;
}

@keyframes pulseGlow {
    0%, 100% { box-shadow: 0 0 12px 4px rgba(255, 215, 0, 0.5); }
    50% { box-shadow: 0 0 20px 8px rgba(255, 215, 0, 0.9); }
}

.machine-level {
    position: absolute;
    top: -18px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #ffd700, #ffaa00);
    color: #1a1a1a;
    font-size: 10px;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 8px;
    border: 2px solid var(--border-color);
}

.machine-earnings {
    position: absolute;
    bottom: -16px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.7);
    color: #4ade80;
    font-size: 9px;
    font-weight: bold;
    padding: 2px 5px;
    border-radius: 4px;
    white-space: nowrap;
}

@keyframes bobble {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(3px); }
}

.floating-text {
    position: absolute;
    color: var(--gold);
    font-weight: bold;
    font-size: 1.5rem;
    pointer-events: none;
    z-index: 1000;
    text-shadow: 2px 2px 0 #000;
    animation: floatUp 0.8s ease-out forwards;
}

@keyframes floatUp {
    0% { transform: translateY(0); opacity: 1; }
    100% { transform: translateY(-50px); opacity: 0; }
}

.upgrade-menu {
    width: 300px;
    background: #f4f4f4;
    padding: 20px 15px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    box-shadow: -5px 0 15px rgba(0, 0, 0, 0.2);
    overflow-y: auto;
    max-height: 100%;
}

.upgrade-menu::-webkit-scrollbar {
    width: 8px;
}

.upgrade-menu::-webkit-scrollbar-track {
    background: #ddd;
    border-radius: 4px;
}

.upgrade-menu::-webkit-scrollbar-thumb {
    background: #999;
    border-radius: 4px;
}

.upgrade-menu::-webkit-scrollbar-thumb:hover {
    background: #777;
}

.upgrade-menu h2 {
    margin: 0 0 8px 0;
    color: #333;
    border-bottom: 3px solid var(--border-color);
    padding-bottom: 8px;
    font-size: 1.1rem;
}

.btn-upgrade {
    padding: 10px 12px;
    font-size: 0.9rem;
    font-weight: bold;
    color: #fff;
    background: #27ae60;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    cursor: pointer;
    transition: 0.2s;
    box-shadow: 0 3px 0 var(--border-color);
}

.btn-upgrade:hover:not(:disabled) {
    transform: translateY(-2px);
}

.btn-upgrade:active:not(:disabled) {
    transform: translateY(2px);
    box-shadow: 0 0 0;
}

.btn-upgrade:disabled {
    background: #95a5a6;
    cursor: not-allowed;
    opacity: 0.7;
}

.btn-hire {
    background: #e67e22;
}

.btn-hire:hover:not(:disabled) {
    background: #f39c12;
}

.btn-collect {
    background: #3498db;
}

.btn-collect:hover:not(:disabled) {
    background: #5dade2;
}

.btn-deposit {
    background: #9b59b6;
}

.btn-deposit:hover:not(:disabled) {
    background: #a66bbe;
}

.btn-capacity {
    background: linear-gradient(135deg, #f39c12, #e74c3c);
}

.btn-capacity:hover:not(:disabled) {
    background: linear-gradient(135deg, #f5ab35, #ec6e59);
}

.transfer-buttons {
    display: flex;
    gap: 8px;
}

.transfer-buttons .btn-upgrade {
    flex: 1;
    padding: 12px 8px;
    font-size: 0.85rem;
}

.btn-collect-all {
    width: 100%;
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    margin-top: 8px;
}

.btn-collect-all:hover:not(:disabled) {
    background: linear-gradient(135deg, #2ecc71, #58d68d);
}

.custom-deposit {
    display: flex;
    gap: 6px;
    margin-top: 8px;
}

.deposit-input {
    flex: 1;
    padding: 8px 10px;
    border: 2px solid #ccc;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: bold;
    text-align: center;
    min-width: 0;
}

.deposit-input:focus {
    outline: none;
    border-color: #9b59b6;
}

.btn-deposit-custom {
    background: #9b59b6;
    flex-shrink: 0;
    padding: 8px 12px;
    font-size: 0.85rem;
}

.btn-deposit-custom:hover:not(:disabled) {
    background: #a66bbe;
}

.upgrade-section {
    background: rgba(255, 215, 0, 0.1);
    border: 2px solid #ffd700;
    border-radius: 8px;
    padding: 8px;
    margin: 5px 0;
}

.selected-info {
    font-weight: bold;
    color: #ffd700;
    text-align: center;
    margin-bottom: 8px;
    text-shadow: 1px 1px 0 rgba(0,0,0,0.3);
}

.btn-machine-upgrade {
    width: 100%;
    background: linear-gradient(135deg, #ffd700, #ff8c00);
}

.btn-machine-upgrade:hover:not(:disabled) {
    background: linear-gradient(135deg, #ffdd33, #ffa500);
}


.description {
    font-size: 0.8rem;
    color: #666;
    margin: 0 0 5px 0;
}

.upgrade-menu hr {
    border: none;
    border-top: 2px solid #ddd;
    margin: 5px 0;
}

.status-msg {
    font-style: italic;
    font-size: 0.85rem;
    color: #555;
    margin-top: auto;
}

/* Popup gains offline */
.offline-popup {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.8);
    z-index: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

.offline-content {
    background: linear-gradient(135deg, #1a1a2e, #2d2d44);
    border: 3px solid #ffd700;
    border-radius: 20px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 0 60px rgba(255, 215, 0, 0.4);
    animation: popIn 0.4s ease;
}

@keyframes popIn {
    from { transform: scale(0.8); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.offline-icon {
    font-size: 60px;
    margin-bottom: 15px;
    animation: bounce 1s ease infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.offline-content h2 {
    color: #fff;
    margin: 0 0 20px 0;
    font-size: 24px;
}

.offline-amount {
    font-size: 48px;
    font-weight: bold;
    color: #ffd700;
    text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
    margin-bottom: 15px;
}

.offline-content p {
    color: #aaa;
    margin-bottom: 25px;
}

.offline-btn {
    background: linear-gradient(135deg, #ffd700, #ffaa00);
    border: none;
    padding: 15px 40px;
    font-size: 18px;
    font-weight: bold;
    color: #1a1a2e;
    border-radius: 30px;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}

.offline-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 0 30px rgba(255, 215, 0, 0.6);
}
</style>
