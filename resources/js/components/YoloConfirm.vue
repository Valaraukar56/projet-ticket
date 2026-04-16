<template>
    <div class="confirm-overlay">
        <div class="confirm-modal">
            <div class="confirm-icon">⚠️</div>
            <h2>ATTENTION</h2>
            <p class="warning-text">Tu es sur le point d'acheter un ticket <span class="yolo-text">YOLO</span></p>

            <div class="info-box">
                <p>✅ 2 icônes = <strong>1 000$</strong></p>
                <p>✅ 3 icônes = <strong>10 000$</strong></p>
                <p class="danger">❌ Perte = <strong>CHAOS TOTAL</strong></p>
            </div>

            <p class="small-text">90% de chances de déclencher l'apocalypse sur ton écran.</p>
            <p class="small-text">On t'aura prévenu...</p>

            <div class="confirm-buttons">
                <button class="btn-cancel" @click="$emit('cancel')">
                    Non, j'ai peur
                </button>
                <button class="btn-confirm" @click="$emit('confirm')">
                    YOLO 🔥
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
defineEmits(['confirm', 'cancel']);
</script>

<style scoped>
.confirm-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.85);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 200;
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.confirm-modal {
    background: linear-gradient(135deg, #1a1a2e 0%, #2d2d5a 100%);
    border: 3px solid #a855f7;
    border-radius: 20px;
    padding: 40px;
    text-align: center;
    max-width: 450px;
    box-shadow: 0 0 50px rgba(168, 85, 247, 0.5);
    animation: popIn 0.3s ease-out;
}

@keyframes popIn {
    from { transform: scale(0.8); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.confirm-icon {
    font-size: 60px;
    margin-bottom: 15px;
    animation: shake 0.5s infinite;
}

@keyframes shake {
    0%, 100% { transform: rotate(-5deg); }
    50% { transform: rotate(5deg); }
}

h2 {
    color: #ff4757;
    font-size: 32px;
    margin-bottom: 15px;
    text-shadow: 0 0 20px rgba(255, 71, 87, 0.5);
}

.warning-text {
    color: #fff;
    font-size: 18px;
    margin-bottom: 20px;
}

.yolo-text {
    color: #a855f7;
    font-weight: bold;
    text-shadow: 0 0 10px rgba(168, 85, 247, 0.8);
}

.info-box {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
}

.info-box p {
    color: #fff;
    font-size: 16px;
    margin: 10px 0;
}

.info-box .danger {
    color: #ff4757;
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

.small-text {
    color: #888;
    font-size: 14px;
    margin: 5px 0;
}

.confirm-buttons {
    display: flex;
    gap: 15px;
    margin-top: 25px;
    justify-content: center;
}

.btn-cancel {
    padding: 15px 30px;
    background: #444;
    border: none;
    border-radius: 10px;
    color: #fff;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-cancel:hover {
    background: #555;
    transform: scale(1.05);
}

.btn-confirm {
    padding: 15px 30px;
    background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%);
    border: none;
    border-radius: 10px;
    color: #fff;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 5px 20px rgba(168, 85, 247, 0.4);
}

.btn-confirm:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 30px rgba(168, 85, 247, 0.6);
}
</style>
