<template>
    <div class="error-chaos">
        <div
            v-for="(error, index) in errors"
            :key="index"
            class="windows-error"
            :style="error.style"
        >
            <div class="error-titlebar">
                <span class="error-icon">⚠️</span>
                <span class="error-title">{{ error.title }}</span>
                <button class="error-close" @click="addMoreErrors">✕</button>
            </div>
            <div class="error-content">
                <div class="error-icon-big">❌</div>
                <div class="error-message">{{ error.message }}</div>
            </div>
            <div class="error-buttons">
                <button @click="addMoreErrors">OK</button>
                <button @click="addMoreErrors">Annuler</button>
                <button @click="addMoreErrors">Réessayer</button>
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

const addMoreErrors = () => {
    for (let i = 0; i < 3; i++) {
        setTimeout(() => addError(), i * 100);
    }
};

const addError = () => {
    const randomError = errorMessages[Math.floor(Math.random() * errorMessages.length)];
    errors.value.push({
        ...randomError,
        style: {
            left: Math.random() * 60 + 10 + '%',
            top: Math.random() * 60 + 10 + '%',
            transform: `rotate(${Math.random() * 20 - 10}deg)`,
            zIndex: errors.value.length + 10,
        }
    });
};

onMounted(() => {
    // Spawn errors progressively
    let errorCount = 0;
    const errorInterval = setInterval(() => {
        addError();
        errorCount++;
        if (errorCount >= 15) {
            clearInterval(errorInterval);
            setTimeout(() => {
                showBSOD.value = true;
                startBSOD();
            }, 1000);
        }
    }, 200);
});

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
    animation: popIn 0.2s ease-out;
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
