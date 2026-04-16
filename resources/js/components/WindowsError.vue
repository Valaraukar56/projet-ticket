<template>
    <div class="error-chaos">
        <div
            v-for="(error, index) in errors"
            :key="error.id"
            class="windows-error"
            :style="error.style"
        >
            <div class="error-titlebar">
                <span class="error-icon">⚠️</span>
                <span class="error-title">{{ error.title }}</span>
                <button class="error-close" @click="onClickButton(error.id)">✕</button>
            </div>
            <div class="error-content">
                <div class="error-icon-big">❌</div>
                <div class="error-message">{{ error.message }}</div>
            </div>
            <div class="error-buttons">
                <button @click="onClickButton(error.id)">OK</button>
                <button @click="onClickButton(error.id)">Annuler</button>
            </div>
        </div>

        <div v-if="showBSOD" class="bsod">
            <div class="bsod-content">
                <div class="bsod-sad">:(</div>
                <h1>Your PC ran into a problem and needs to restart.</h1>
                <p>We're just collecting some error info, and then we'll restart for you.</p>
                <p class="bsod-percent">{{ bsodPercent }}% complete</p>
                <br>
                <p class="bsod-code">Stop code: TICKET_SCRATCH_YOLO_FAILED</p>
                <p class="bsod-info">If you call a support person, give them this info:</p>
                <p class="bsod-info">Error: YOU_SHOULD_NOT_HAVE_PLAYED_YOLO</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const emit = defineEmits(['chaos-complete']);

const errors = ref([]);
const showBSOD = ref(false);
const bsodPercent = ref(0);
const clickCount = ref(0);
const errorIdCounter = ref(0);
const isExploding = ref(false);

const errorMessages = [
    { title: 'Erreur Critique', message: 'Votre portefeuille a été vidé avec succès.' },
    { title: 'Alerte Sécurité', message: 'Tentative de gain détectée. Accès refusé.' },
    { title: 'Erreur Système', message: 'Le fichier argent.dll est corrompu.' },
    { title: 'Avertissement', message: 'Vous avez choisi YOLO. Vous assumez.' },
    { title: 'Erreur Fatale', message: 'Exception: TropDeConfianceException' },
    { title: 'Erreur 404', message: 'Gains introuvables.' },
    { title: 'Erreur de Calcul', message: '20$ - 20$ = Vous êtes pauvre.' },
    { title: 'Virus Détecté', message: 'Virus: YOLO.exe - Quarantaine impossible' },
    { title: 'Mémoire Insuffisante', message: 'Pas assez de chance pour continuer.' },
    { title: 'Erreur Réseau', message: 'Connexion avec la fortune perdue.' },
    { title: 'Erreur Windows', message: 'Task failed successfully.' },
    { title: 'Crash Report', message: 'Cause: Excès de confiance en soi.' },
];

const getRandomError = () => {
    return errorMessages[Math.floor(Math.random() * errorMessages.length)];
};

const getRandomPosition = () => ({
    left: Math.random() * 70 + 5 + '%',
    top: Math.random() * 60 + 5 + '%',
    transform: `rotate(${Math.random() * 10 - 5}deg)`,
});

const addError = () => {
    const randomError = getRandomError();
    errorIdCounter.value++;
    errors.value.push({
        id: errorIdCounter.value,
        ...randomError,
        style: {
            ...getRandomPosition(),
            zIndex: errors.value.length + 10,
        }
    });
};

const onClickButton = (errorId) => {
    if (isExploding.value) return;

    // Supprimer l'erreur cliquée
    errors.value = errors.value.filter(e => e.id !== errorId);
    clickCount.value++;

    // Multiplication exponentielle
    let newErrorCount = 0;

    if (clickCount.value === 1) {
        newErrorCount = 2;
    } else if (clickCount.value === 2) {
        newErrorCount = 4;
    } else if (clickCount.value === 3) {
        newErrorCount = 8;
    } else {
        // EXPLOSION TOTALE
        isExploding.value = true;
        explode();
        return;
    }

    // Ajouter les nouvelles erreurs avec délai
    for (let i = 0; i < newErrorCount; i++) {
        setTimeout(() => addError(), i * 100);
    }
};

const explode = () => {
    // Spawn massif d'erreurs
    let spawned = 0;
    const explosionInterval = setInterval(() => {
        for (let i = 0; i < 5; i++) {
            addError();
        }
        spawned += 5;

        if (spawned >= 50) {
            clearInterval(explosionInterval);
            // Lancer le BSOD après un court délai
            setTimeout(() => {
                showBSOD.value = true;
                startBSOD();
            }, 1500);
        }
    }, 100);
};

const startBSOD = () => {
    const bsodInterval = setInterval(() => {
        bsodPercent.value += Math.floor(Math.random() * 15) + 5;
        if (bsodPercent.value >= 100) {
            bsodPercent.value = 100;
            clearInterval(bsodInterval);
            setTimeout(() => {
                emit('chaos-complete');
            }, 1500);
        }
    }, 400);
};

onMounted(() => {
    // Première erreur au démarrage
    addError();
});
</script>

<style scoped>
.error-chaos {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    pointer-events: all;
}

.windows-error {
    position: absolute;
    width: 350px;
    background: #f0f0f0;
    border: 2px solid #0078d4;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    font-family: 'Segoe UI', Tahoma, sans-serif;
    animation: popIn 0.15s ease-out;
}

@keyframes popIn {
    from { transform: scale(0.5); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.error-titlebar {
    background: #0078d4;
    color: white;
    padding: 8px 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    border-radius: 6px 6px 0 0;
}

.error-title {
    flex: 1;
    font-size: 13px;
}

.error-close {
    background: transparent;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 16px;
    padding: 2px 8px;
    border-radius: 4px;
}

.error-close:hover {
    background: #c42b1c;
}

.error-content {
    padding: 20px;
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.error-icon-big {
    font-size: 40px;
}

.error-message {
    color: #333;
    font-size: 14px;
    line-height: 1.5;
}

.error-buttons {
    padding: 15px 20px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid #ddd;
}

.error-buttons button {
    padding: 8px 20px;
    background: #f0f0f0;
    border: 1px solid #ccc;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
}

.error-buttons button:hover {
    background: #e0e0e0;
}

.bsod {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #0078d7;
    z-index: 99999;
    display: flex;
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.bsod-content {
    color: white;
    font-family: 'Segoe UI', sans-serif;
    max-width: 800px;
    padding: 40px;
}

.bsod-sad {
    font-size: 120px;
    margin-bottom: 20px;
}

.bsod-content h1 {
    font-size: 28px;
    font-weight: 300;
    margin-bottom: 20px;
}

.bsod-content p {
    font-size: 16px;
    margin-bottom: 10px;
    font-weight: 300;
}

.bsod-percent {
    font-size: 18px;
    margin-top: 30px;
}

.bsod-code {
    margin-top: 40px;
    font-size: 14px;
}

.bsod-info {
    font-size: 12px;
    opacity: 0.8;
}
</style>
