<template>
    <div class="host-scene">
        <!-- Mur et sol -->
        <div class="wall"></div>
        <div class="floor"></div>

        <!-- Étagère déco -->
        <div class="shelf">
            <div class="shelf-side left"></div>
            <div class="shelf-side right"></div>
            <div class="shelf-rail top"></div>
            <div class="shelf-rail mid"></div>
            <div class="shelf-items row1">
                <div class="bookend"></div>
                <div class="book b1"></div>
                <div class="book b2"></div>
                <div class="book b3"></div>
                <div class="book b4"></div>
                <div class="vase"></div>
            </div>
            <div class="shelf-items row2">
                <div class="book b5"></div>
                <div class="book b6"></div>
                <div class="bookend"></div>
                <div class="book b7"></div>
                <div class="book b8"></div>
            </div>
        </div>

        <!-- Plante -->
        <div class="plant">
            <div class="plant-leaves">
                <div class="leaf l1"></div>
                <div class="leaf l2"></div>
                <div class="leaf l3"></div>
                <div class="leaf l4"></div>
                <div class="leaf l5"></div>
                <div class="leaf l6"></div>
            </div>
            <div class="plant-soil"></div>
            <div class="plant-pot"></div>
        </div>

        <!-- Comptoir -->
        <div class="counter"></div>

        <!-- Boutons haut gauche -->
        <div class="top-left-buttons">
            <button class="leaderboard-btn" @click="$emit('show-leaderboard')" title="Classement">
                🏆
            </button>
            <button class="chat-btn" @click="$emit('show-chat')" title="Chat Global">
                💬
            </button>
            <button v-if="isAdmin" class="admin-btn" @click="$emit('show-admin')" title="Panel Admin">
                🛠️
            </button>
        </div>

        <!-- Infos utilisateur et balance -->
        <div class="top-right-panel">
            <div class="user-info" v-if="user">
                <span class="user-name">{{ user.name }}</span>
                <button class="logout-btn" @click="$emit('logout')">Déconnexion</button>
            </div>
            <div class="balance-display">
                <span class="balance-icon">$</span>
                <span class="balance-amount">{{ balance }}</span>
            </div>
        </div>

        <!-- Personnage -->
        <div class="character-wrap" :class="animClass" ref="characterRef">
            <div class="speech" :class="{ show: showSpeech }">{{ speechText }}</div>

            <svg class="char-svg" @click="onCharacterClick" viewBox="0 0 280 390">
                <!-- Corps -->
                <g class="torso-upper">
                    <rect x="104" y="176" width="72" height="88" rx="18" fill="var(--shirt)" stroke="var(--line)" stroke-width="6"/>
                </g>

                <!-- Main gauche flottante (style Rayman) -->
                <g class="hand-l">
                    <circle cx="70" cy="240" r="18" fill="var(--skin)" stroke="var(--line)" stroke-width="5"/>
                </g>

                <!-- Main droite flottante (style Rayman) -->
                <g class="hand-r">
                    <circle cx="210" cy="240" r="18" fill="var(--skin)" stroke="var(--line)" stroke-width="5"/>
                </g>

                <!-- Tête -->
                <g class="head-g">
                    <circle cx="140" cy="106" r="78" fill="var(--skin)" stroke="var(--line)" stroke-width="7"/>
                    <line class="brow-l" x1="100" y1="86" x2="122" y2="83" stroke="var(--line)" stroke-width="7" stroke-linecap="round"/>
                    <line class="brow-r" x1="158" y1="83" x2="180" y2="86" stroke="var(--line)" stroke-width="7" stroke-linecap="round"/>
                    <ellipse class="eye-l" cx="109" cy="110" rx="9" ry="6" fill="#000"/>
                    <ellipse class="eye-r" cx="168" cy="110" rx="9" ry="6" fill="#000"/>
                    <path class="mouth" d="M122 142 Q140 148 158 142" fill="none" stroke="#000" stroke-width="5" stroke-linecap="round"/>
                </g>
            </svg>
        </div>

        <!-- Menu catégories -->
        <div class="menu-overlay" :class="{ open: showMenu || showTickets }" @click="showMenu = false; showTickets = false; selectedCategory = null;"></div>
        <div class="menu" :class="{ open: showMenu }">
            <div class="menu-title">Choisissez une catégorie</div>
            <div class="menu-grid">
                <button
                    v-for="category in categories"
                    :key="category.id"
                    class="menu-btn category-btn"
                    :style="{ borderColor: category.color }"
                    @click="selectCategory(category)"
                >
                    <span class="category-icon">{{ category.icon }}</span>
                    <span class="category-name">{{ category.name }}</span>
                </button>
            </div>
        </div>

        <!-- Menu tickets de la catégorie -->
        <div class="menu" :class="{ open: showTickets }">
            <button class="back-btn" @click="backToCategories">← Retour</button>
            <div class="menu-title">
                <span v-if="selectedCategory">{{ selectedCategory.icon }} {{ selectedCategory.name }}</span>
            </div>
            <div class="menu-grid">
                <button
                    v-for="ticket in currentTickets"
                    :key="ticket.id"
                    class="menu-btn"
                    :class="{ disabled: balance < ticket.price }"
                    :style="{ borderColor: ticket.color }"
                    @click="selectTicket(ticket)"
                >
                    <span class="ticket-name">{{ ticket.name }}</span>
                    <span class="ticket-details">{{ ticket.price }}$ - {{ ticket.lossPercentage }}% risque</span>
                    <span class="ticket-gains">Gain: {{ ticket.baseGain }}$ | Jackpot: {{ ticket.jackpotGain }}$</span>
                </button>
            </div>
        </div>

        <!-- Bureau -->
        <div class="desk" ref="deskRef">
            <div class="desk-items">
                <div class="papers">
                    <div class="paper p1"></div>
                    <div class="paper p2"></div>
                </div>
                <div class="mug">
                    <div class="steam"><span></span><span></span><span></span></div>
                    <div class="mug-body"><div class="mug-handle"></div></div>
                </div>
            </div>

            <div class="desk-top"></div>
            <div class="desk-front">
                <div class="desk-panel left"></div>
                <div class="desk-panel center"></div>
                <div class="desk-panel right"></div>
                <div class="desk-handle h1"></div>
                <div class="desk-handle h2"></div>
                <div class="desk-handle h3"></div>
            </div>
            <div class="desk-leg-shadow"></div>
        </div>

        <div class="hint" :class="{ hidden: hintHidden }">Cliquez sur le personnage</div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    balance: {
        type: Number,
        required: true,
    },
    user: {
        type: Object,
        default: null,
    },
    isAdmin: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['buy-ticket', 'show-leaderboard', 'show-chat', 'show-admin', 'logout']);

// Catégories de tickets
const categories = [
    { id: 'metro', name: 'Ticket Métro', icon: '🚇', color: '#4ade80' },
    { id: 'bus', name: 'Bus', icon: '🚌', color: '#60a5fa' },
    { id: 'train', name: 'Train', icon: '🚄', color: '#fbbf24' },
    { id: 'loterie', name: 'Loterie', icon: '🎰', color: '#a855f7' },
];

// Thèmes et couleurs par catégorie
const categoryMeta = {
    metro: { color: '#4ade80', theme: 'astro' },
    bus: { color: '#60a5fa', theme: 'cash' },
    train: { color: '#fbbf24', theme: 'gold' },
    loterie: { color: '#a855f7', theme: 'vegas' },
};

// Tickets par catégorie (chargés depuis le backend)
const ticketsByCategory = ref({
    metro: [
        { id: 'm1', name: 'Étoile Filante', price: 1, lossPercentage: 20, baseGain: 2, jackpotGain: 5, theme: 'astro', color: '#4ade80' },
        { id: 'm2', name: 'Constellation', price: 2, lossPercentage: 25, baseGain: 3, jackpotGain: 8, theme: 'astro', color: '#4ade80' },
        { id: 'm3', name: 'Galaxie', price: 3, lossPercentage: 30, baseGain: 5, jackpotGain: 12, theme: 'astro', color: '#4ade80' },
        { id: 'm4', name: 'Cosmos', price: 5, lossPercentage: 35, baseGain: 8, jackpotGain: 20, theme: 'astro', color: '#4ade80' },
    ],
    bus: [
        { id: 'b1', name: 'Petit Cash', price: 2, lossPercentage: 30, baseGain: 3, jackpotGain: 10, theme: 'cash', color: '#60a5fa' },
        { id: 'b2', name: 'Cash Express', price: 4, lossPercentage: 35, baseGain: 6, jackpotGain: 18, theme: 'cash', color: '#60a5fa' },
        { id: 'b3', name: 'Banco', price: 6, lossPercentage: 40, baseGain: 10, jackpotGain: 30, theme: 'cash', color: '#60a5fa' },
        { id: 'b4', name: 'Mega Cash', price: 10, lossPercentage: 45, baseGain: 18, jackpotGain: 50, theme: 'cash', color: '#60a5fa' },
    ],
    train: [
        { id: 't1', name: 'Pépite', price: 5, lossPercentage: 45, baseGain: 8, jackpotGain: 30, theme: 'gold', color: '#fbbf24' },
        { id: 't2', name: 'Lingot', price: 10, lossPercentage: 50, baseGain: 18, jackpotGain: 80, theme: 'gold', color: '#fbbf24' },
        { id: 't3', name: 'Trésor', price: 15, lossPercentage: 55, baseGain: 28, jackpotGain: 150, theme: 'gold', color: '#fbbf24' },
        { id: 't4', name: 'El Dorado', price: 25, lossPercentage: 60, baseGain: 50, jackpotGain: 300, theme: 'gold', color: '#fbbf24' },
    ],
    loterie: [
        { id: 'l1', name: 'Vegas Night', price: 5, lossPercentage: 70, baseGain: 15, jackpotGain: 100, theme: 'vegas', color: '#a855f7' },
        { id: 'l2', name: 'High Roller', price: 10, lossPercentage: 75, baseGain: 35, jackpotGain: 500, theme: 'vegas', color: '#a855f7' },
        { id: 'l3', name: 'Royal Flush', price: 20, lossPercentage: 85, baseGain: 100, jackpotGain: 2000, theme: 'vegas', color: '#a855f7' },
        { id: 'l4', name: 'YOLO', price: 50, lossPercentage: 90, baseGain: 5000, jackpotGain: 10000, cursed: true, theme: 'vegas', color: '#ef4444' },
    ],
});

// Charger les tickets depuis le backend
const loadTicketSettings = async () => {
    try {
        const response = await fetch('/api/admin/tickets', { credentials: 'same-origin' });
        if (response.ok) {
            const data = await response.json();
            if (data.tickets) {
                // Fusionner avec les métadonnées (couleur, thème)
                for (const category in data.tickets) {
                    if (ticketsByCategory.value[category]) {
                        ticketsByCategory.value[category] = data.tickets[category].map(ticket => ({
                            ...ticket,
                            color: ticket.cursed ? '#ef4444' : categoryMeta[category].color,
                            theme: categoryMeta[category].theme,
                        }));
                    }
                }
            }
        }
    } catch (e) {
        console.log('Utilisation des tickets par défaut');
    }
};

const characterRef = ref(null);
const deskRef = ref(null);
const animClass = ref('anim-idle');
const showSpeech = ref(false);
const speechText = ref('Bonjour !');
const showMenu = ref(false);
const showTickets = ref(false);
const selectedCategory = ref(null);
const hintHidden = ref(false);
const greeted = ref(false);

const currentTickets = computed(() => {
    if (!selectedCategory.value) return [];
    return ticketsByCategory.value[selectedCategory.value.id] || [];
});

let animTimer = null;

const alignCharacter = () => {
    if (!characterRef.value || !deskRef.value) return;
    const deskRect = deskRef.value.getBoundingClientRect();
    // Positionner le personnage derrière le bureau (plus bas pour que le bureau le cache partiellement)
    const bottomPx = window.innerHeight - deskRect.top;
    characterRef.value.style.bottom = (bottomPx - 160) + 'px';
};

onMounted(() => {
    alignCharacter();
    window.addEventListener('resize', alignCharacter);
    loadTicketSettings();
});

const setIdle = () => {
    animClass.value = 'anim-idle';
};

const greetThenMenu = () => {
    clearTimeout(animTimer);
    showMenu.value = false;
    animClass.value = 'anim-greet';
    speechText.value = 'Bienvenue ! Choisissez un ticket !';
    showSpeech.value = true;

    animTimer = setTimeout(() => {
        showSpeech.value = false;
        setIdle();
        showMenu.value = true;
    }, 1900);
};

const onCharacterClick = () => {
    hintHidden.value = true;

    if (!greeted.value) {
        greeted.value = true;
        greetThenMenu();
        return;
    }

    showMenu.value = !showMenu.value;
};

const selectCategory = (category) => {
    selectedCategory.value = category;
    showMenu.value = false;
    showTickets.value = true;
};

const backToCategories = () => {
    showTickets.value = false;
    selectedCategory.value = null;
    showMenu.value = true;
};

const selectTicket = (ticket) => {
    if (props.balance < ticket.price) return;
    showTickets.value = false;
    selectedCategory.value = null;
    emit('buy-ticket', ticket);
};
</script>

<style scoped>
:root {
    --line: #111111;
    --skin: #f1d2ac;
    --shirt: #d8651e;
    --wood-top: #e1ae74;
    --wood-top-2: #c9864f;
    --wood-front: #bf7a44;
    --wood-front-2: #9d5c2f;
    --wood-dark: #6f3e1f;
    --floor-a: #d0a06b;
    --floor-b: #ae7445;
    --leaf: #4f9d3d;
    --leaf-light: #7ac95c;
    --leaf-dark: #2b5f21;
    --pot: #9c6341;
    --menu: #fff9ef;
    --menu-border: #d8b88c;
    --menu-hover: #f3e3c8;
}

.host-scene {
    --line: #111111;
    --skin: #f1d2ac;
    --shirt: #d8651e;
    --wood-top: #e1ae74;
    --wood-top-2: #c9864f;
    --wood-front: #bf7a44;
    --wood-front-2: #9d5c2f;
    --wood-dark: #6f3e1f;
    --floor-a: #d0a06b;
    --floor-b: #ae7445;
    --leaf: #4f9d3d;
    --leaf-light: #7ac95c;
    --leaf-dark: #2b5f21;
    --pot: #9c6341;
    --menu: #fff9ef;
    --menu-border: #d8b88c;
    --menu-hover: #f3e3c8;

    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.wall {
    position: absolute;
    inset: 0 0 26% 0;
    background:
        radial-gradient(ellipse 56% 38% at 50% 0%, rgba(255,245,215,.55) 0%, transparent 100%),
        linear-gradient(180deg, #7d4e36 0 6.5%, #9d6847 6.5% 8%, #c89467 8% 100%);
}

.wall::after {
    content: '';
    position: absolute;
    inset: 0;
    background: repeating-linear-gradient(180deg, transparent 0 56px, rgba(60,30,10,.20) 56px 59px);
}

.floor {
    position: absolute;
    left: 0; right: 0; bottom: 0;
    height: 26%;
    background: linear-gradient(180deg, var(--floor-a) 0%, var(--floor-b) 100%);
    border-top: 7px solid #6d3f1e;
}

/* Boutons haut gauche */
.top-left-buttons {
    position: absolute;
    top: 20px;
    left: 20px;
    z-index: 50;
    display: flex;
    gap: 10px;
}

.leaderboard-btn {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ffd700, #ffaa00);
    border: 3px solid #d4a200;
    font-size: 28px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 5px 20px rgba(255, 215, 0, 0.4);
    transition: all 0.2s;
}

.leaderboard-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 8px 30px rgba(255, 215, 0, 0.6);
}

.chat-btn {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4ade80, #22c55e);
    border: 3px solid #16a34a;
    font-size: 28px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 5px 20px rgba(74, 222, 128, 0.4);
    transition: all 0.2s;
}

.chat-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 8px 30px rgba(74, 222, 128, 0.6);
}

.admin-btn {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    border: 3px solid #a93226;
    font-size: 28px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 5px 20px rgba(231, 76, 60, 0.4);
    transition: all 0.2s;
}

.admin-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 8px 30px rgba(231, 76, 60, 0.6);
}

/* Panel haut droite */
.top-right-panel {
    position: absolute;
    top: 20px;
    right: 20px;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
    z-index: 50;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(0, 0, 0, 0.3);
    padding: 8px 16px;
    border-radius: 25px;
}

.user-name {
    color: white;
    font-weight: 600;
    font-size: 14px;
}

.logout-btn {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 6px 14px;
    border-radius: 15px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.logout-btn:hover {
    background: rgba(255, 255, 255, 0.25);
}

.balance-display {
    background: linear-gradient(135deg, #ffd700 0%, #ffaa00 100%);
    padding: 12px 25px;
    border-radius: 50px;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 5px 20px rgba(255, 215, 0, 0.4);
}

.balance-icon, .balance-amount {
    font-size: 24px;
    font-weight: bold;
    color: #1a1a2e;
}

/* Étagère */
.shelf {
    position: absolute;
    left: 6%;
    top: 13%;
    width: 30%;
    max-width: 290px;
}

.shelf-rail {
    position: absolute;
    left: 8px; right: 8px;
    height: 10px;
    background: linear-gradient(180deg, #9a6030 0%, #6e3e18 100%);
    border-radius: 6px;
    box-shadow: 0 3px 6px rgba(0,0,0,.25);
}

.shelf-rail.top { top: 56px; }
.shelf-rail.mid { top: 114px; }

.shelf-side {
    position: absolute;
    top: 0;
    width: 9px;
    background: linear-gradient(90deg, #6e3e18, #9a6030);
    border-radius: 5px;
    height: 124px;
}

.shelf-side.left { left: 0; }
.shelf-side.right { right: 0; }

.shelf-items {
    position: absolute;
    left: 18px; right: 18px;
    display: flex;
    gap: 6px;
    align-items: flex-end;
    bottom: auto;
}

.shelf-items.row1 { top: 2px; height: 54px; }
.shelf-items.row2 { top: 66px; height: 48px; }

.book {
    width: 14px;
    border-radius: 3px 1px 1px 3px;
    border: 2px solid rgba(0,0,0,.35);
    flex-shrink: 0;
}

.b1 { height: 44px; background: linear-gradient(180deg, #c0392b, #962d22); }
.b2 { height: 52px; background: linear-gradient(180deg, #2980b9, #1a6091); }
.b3 { height: 38px; background: linear-gradient(180deg, #27ae60, #1a7a42); }
.b4 { height: 48px; background: linear-gradient(180deg, #8e44ad, #6c2f85); }
.b5 { height: 42px; background: linear-gradient(180deg, #e67e22, #b05e14); }
.b6 { height: 36px; background: linear-gradient(180deg, #1abc9c, #148a70); }
.b7 { height: 46px; background: linear-gradient(180deg, #e74c3c, #a92c1e); }
.b8 { height: 40px; background: linear-gradient(180deg, #f39c12, #b87809); }

.bookend {
    width: 10px;
    height: 38px;
    background: #5a5a5a;
    border-radius: 3px;
    border: 2px solid #333;
    flex-shrink: 0;
}

.vase {
    width: 18px;
    height: 38px;
    background: linear-gradient(135deg, #d4a853, #a07030);
    border: 2px solid #7a5020;
    border-radius: 5px 5px 3px 3px;
    flex-shrink: 0;
}

/* Plante */
.plant {
    position: absolute;
    right: 8%;
    bottom: 26%;
    width: 110px;
    animation: plantSway 4.8s ease-in-out infinite;
    transform-origin: 50% 100%;
    z-index: 4;
}

.plant-pot {
    position: relative;
    margin: 0 auto;
    width: 68px;
    height: 54px;
    background: linear-gradient(170deg, #c08060 10%, var(--pot) 100%);
    border: 4px solid #6e3e22;
    clip-path: polygon(8% 0%, 92% 0%, 100% 100%, 0% 100%);
    border-radius: 0 0 10px 10px;
}

.plant-soil {
    margin: 0 auto;
    width: 56px;
    height: 11px;
    background: #5a3210;
    border: 3px solid #3e2008;
    border-radius: 999px 999px 0 0;
    position: relative;
    top: 4px;
}

.plant-leaves {
    position: relative;
    height: 110px;
    width: 110px;
}

.leaf {
    position: absolute;
    bottom: 0;
    width: 16px;
    border: 3px solid var(--leaf-dark);
    border-radius: 50% 50% 48% 52% / 70% 70% 30% 30%;
    transform-origin: 50% 100%;
    background: linear-gradient(180deg, var(--leaf-light), var(--leaf));
}

.l1 { left: 12px; height: 62px; transform: rotate(-40deg); }
.l2 { left: 28px; height: 76px; transform: rotate(-18deg); }
.l3 { left: 46px; height: 82px; transform: rotate(2deg); }
.l4 { left: 64px; height: 72px; transform: rotate(24deg); }
.l5 { left: 78px; height: 58px; transform: rotate(42deg); }
.l6 { left: 40px; height: 60px; transform: rotate(-6deg); }

/* Comptoir */
.counter {
    position: absolute;
    left: 0; right: 0;
    bottom: 26%;
    height: 13%;
    background: linear-gradient(180deg, #e2bb88 0 10%, #a86838 10% 100%);
    border-top: 7px solid #7a4522;
}

/* Bureau */
.desk {
    position: absolute;
    left: 50%;
    bottom: 4%;
    transform: translateX(-50%);
    width: min(920px, 90vw);
    z-index: 12;
}

.desk-top {
    position: relative;
    height: 74px;
    background: linear-gradient(180deg, var(--wood-top) 0%, var(--wood-top-2) 100%);
    border: 6px solid var(--wood-dark);
    border-bottom-width: 4px;
    border-radius: 14px 14px 8px 8px;
    box-shadow: inset 0 3px 0 rgba(255,255,255,.18), 0 8px 14px rgba(0,0,0,.08);
}

.desk-front {
    position: relative;
    height: 252px;
    margin-top: -2px;
    background: linear-gradient(180deg, var(--wood-front) 0%, var(--wood-front-2) 100%);
    border: 6px solid var(--wood-dark);
    border-top: none;
    border-radius: 0 0 16px 16px;
    overflow: hidden;
}

.desk-panel {
    position: absolute;
    top: 24px;
    bottom: 24px;
    border: 4px solid rgba(83,43,20,.6);
    border-radius: 10px;
    background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(0,0,0,.05));
}

.desk-panel.left { left: 22px; width: 22%; }
.desk-panel.center { left: 26%; right: 26%; }
.desk-panel.right { right: 22px; width: 22%; }

.desk-handle {
    position: absolute;
    width: 26px;
    height: 12px;
    border: 3px solid #7b4b22;
    border-radius: 999px;
    background: linear-gradient(180deg, #e6c178, #b98737);
}

.desk-handle.h1 { left: 11%; top: 43%; }
.desk-handle.h2 { left: calc(50% - 13px); top: 43%; }
.desk-handle.h3 { right: 11%; top: 43%; }

.desk-leg-shadow {
    height: 18px;
    margin: 0 9%;
    border-radius: 999px;
    background: rgba(0,0,0,.18);
    filter: blur(11px);
}

.desk-items {
    position: absolute;
    left: 0; right: 0;
    top: 8px;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    padding: 0 5% 0 8%;
    pointer-events: none;
    z-index: 16;
}

/* Objets bureau */
.mug { width: 38px; position: relative; flex-shrink: 0; }
.mug-body {
    width: 38px;
    height: 42px;
    background: linear-gradient(180deg, #f8f8f8, #d8d8d8);
    border: 3px solid #888;
    border-radius: 6px 6px 9px 9px;
    position: relative;
}

.mug-handle {
    position: absolute;
    right: -11px;
    top: 8px;
    width: 13px;
    height: 20px;
    border: 3px solid #888;
    border-radius: 0 8px 8px 0;
    border-left: none;
}

.steam {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 5px;
}

.steam span {
    width: 3px;
    height: 14px;
    background: rgba(200,200,200,.7);
    border-radius: 999px;
    animation: steamRise 1.8s ease-in-out infinite;
}

.steam span:nth-child(2) { animation-delay: .6s; }
.steam span:nth-child(3) { animation-delay: 1.2s; }

.laptop { flex-shrink: 0; position: relative; margin-right: 2%; }
.laptop-screen {
    width: 136px;
    height: 86px;
    background: linear-gradient(160deg, #1a1a2e, #16213e);
    border: 4px solid #333;
    border-radius: 7px 7px 0 0;
    position: relative;
    overflow: hidden;
}

.code-lines {
    position: absolute;
    inset: 8px 6px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.code-line {
    height: 4px;
    border-radius: 2px;
    background: rgba(100,255,180,.35);
}

.cl1 { width: 55%; }
.cl2 { width: 78%; }
.cl3 { width: 40%; margin-left: 10px; }
.cl4 { width: 62%; }
.cl5 { width: 48%; margin-left: 10px; }
.cl6 { width: 30%; }

.laptop-base {
    width: 156px;
    height: 10px;
    background: linear-gradient(180deg, #555, #333);
    border: 3px solid #222;
    border-top: none;
    border-radius: 0 0 5px 5px;
    margin-left: -10px;
}

.papers { flex-shrink: 0; position: relative; width: 52px; height: 34px; }
.paper {
    position: absolute;
    width: 48px;
    height: 34px;
    background: #faf8f2;
    border: 2px solid #ccc;
    border-radius: 2px;
}

.p1 { transform: rotate(-5deg); top: 2px; left: 0; }
.p2 { transform: rotate(2deg); top: 0; left: 3px; }

/* Personnage */
.character-wrap {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    width: 280px;
    height: 390px;
    z-index: 11;
}

.char-svg {
    width: 100%;
    height: 100%;
    overflow: visible;
    cursor: pointer;
}

.head-g, .hand-l, .hand-r, .mouth, .eye-l, .eye-r, .brow-l, .brow-r {
    transform-box: fill-box;
    transform-origin: center;
}

.hand-l, .hand-r {
    transition: transform 0.3s ease;
}

/* Bulle */
.speech {
    position: absolute;
    left: calc(100% + 12px);
    bottom: 62%;
    min-width: 140px;
    max-width: 280px;
    padding: 12px 16px;
    background: #fffdf5;
    border: 4px solid var(--line);
    border-radius: 18px;
    font-weight: 700;
    font-size: 16px;
    color: #1a1a1a;
    opacity: 0;
    transform: scale(.82) translateY(6px);
    transition: opacity .22s ease, transform .22s ease;
    pointer-events: none;
    z-index: 20;
    filter: drop-shadow(0 4px 10px rgba(0,0,0,.15));
}

.speech.show {
    opacity: 1;
    transform: scale(1) translateY(0);
}

.speech::before {
    content: '';
    position: absolute;
    left: -14px;
    bottom: 18px;
    border-top: 10px solid transparent;
    border-bottom: 10px solid transparent;
    border-right: 14px solid var(--line);
}

.speech::after {
    content: '';
    position: absolute;
    left: -8px;
    bottom: 21px;
    border-top: 7px solid transparent;
    border-bottom: 7px solid transparent;
    border-right: 10px solid #fffdf5;
}

/* Menu */
.menu {
    position: fixed;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%) scale(0.95);
    width: min(700px, 92vw);
    background: var(--menu);
    border: 3px solid var(--menu-border);
    border-radius: 18px;
    box-shadow: 0 14px 28px rgba(0,0,0,.25);
    padding: 24px;
    z-index: 100;
    opacity: 0;
    pointer-events: none;
    transition: opacity .25s ease, transform .25s ease;
}

.menu.open {
    opacity: 1;
    pointer-events: auto;
    transform: translate(-50%, -50%) scale(1);
}

.menu-title {
    font-size: 20px;
    font-weight: 700;
    color: #4b2b16;
    margin-bottom: 16px;
    text-align: center;
}

.menu-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

@media (max-width: 500px) {
    .menu-grid {
        grid-template-columns: 1fr;
    }
}

.menu-btn {
    appearance: none;
    border: none;
    width: 100%;
    text-align: left;
    background: #fff;
    border: 3px solid #ead2ad;
    border-radius: 12px;
    padding: 12px 14px;
    cursor: pointer;
    transition: all .15s ease;
}

.menu-btn:hover:not(.disabled) {
    background: var(--menu-hover);
    transform: translateY(-2px);
}

.menu-btn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.ticket-name {
    display: block;
    font-size: 16px;
    font-weight: 700;
    color: #4a2b15;
}

.ticket-details {
    display: block;
    font-size: 13px;
    color: #666;
    margin-top: 2px;
}

.ticket-gains {
    display: block;
    font-size: 12px;
    color: #27ae60;
    margin-top: 4px;
}

.category-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    gap: 8px;
}

.category-icon {
    font-size: 36px;
}

.category-name {
    font-size: 16px;
    font-weight: 700;
    color: #4a2b15;
}

.back-btn {
    position: absolute;
    top: 12px;
    left: 12px;
    background: none;
    border: none;
    color: #4a2b15;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    padding: 6px 12px;
    border-radius: 8px;
    transition: background 0.2s;
}

.back-btn:hover {
    background: var(--menu-hover);
}

.menu-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 99;
    opacity: 0;
    pointer-events: none;
    transition: opacity .25s ease;
}

.menu-overlay.open {
    opacity: 1;
    pointer-events: auto;
}

.hint {
    position: absolute;
    bottom: 14px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 30;
    padding: 8px 18px;
    border-radius: 999px;
    background: rgba(255,253,245,.82);
    color: #3a2010;
    font-weight: 700;
    font-size: 13px;
    box-shadow: 0 4px 16px rgba(0,0,0,.14);
    transition: opacity .4s ease;
}

.hint.hidden { opacity: 0; }

/* Animations */
.anim-idle .head-g { animation: idleHead 3.4s ease-in-out infinite; }
.anim-idle .eye-l, .anim-idle .eye-r { animation: idleBlink 4.5s 1.2s infinite; }

.anim-greet .head-g { animation: greetHead 1.9s ease-in-out 1; }
.anim-greet .hand-r { animation: handWave 0.4s ease-in-out 4; }
.anim-greet .mouth { animation: greetSmile .3s ease-in-out 5; }
.anim-greet .eye-l, .anim-greet .eye-r { animation: idleBlink 1.1s infinite; }

/* Animation idle pour les mains */
.anim-idle .hand-l { animation: handFloat 2.5s ease-in-out infinite; }
.anim-idle .hand-r { animation: handFloat 2.5s ease-in-out infinite 0.3s; }

@keyframes idleHead {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(3px); }
}

@keyframes idleBlink {
    0%, 45%, 53%, 100% { transform: scaleY(1); }
    49% { transform: scaleY(.06); }
}

@keyframes plantSway {
    0%, 100% { transform: rotate(0deg); }
    50% { transform: rotate(2.5deg); }
}

@keyframes steamRise {
    0% { transform: translateY(0) scaleY(1); opacity: .7; }
    100% { transform: translateY(-12px) scaleY(1.4); opacity: 0; }
}

@keyframes greetHead {
    0%, 100% { transform: translateY(0); }
    35% { transform: translateY(-3px); }
    60% { transform: translateY(1px); }
}

@keyframes handWave {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    25% { transform: translateY(-55px) rotate(-15deg); }
    50% { transform: translateY(-50px) rotate(15deg); }
    75% { transform: translateY(-55px) rotate(-10deg); }
}

@keyframes handFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(5px); }
}

@keyframes greetSmile {
    0%, 100% { transform: scale(1); }
    50% { transform: scaleX(1.18) scaleY(1.12); }
}
</style>
