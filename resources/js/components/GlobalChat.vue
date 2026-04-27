<template>
    <div class="chat-overlay" @click.self="$emit('close')">
        <div class="chat-container">
            <div class="chat-header">
                <h2>Chat Global</h2>
                <button class="close-btn" @click="$emit('close')">✕</button>
            </div>

            <div class="chat-messages" ref="messagesContainer">
                <div
                    v-for="msg in messages"
                    :key="msg.id"
                    class="chat-message"
                    :class="{ 'own-message': msg.username === currentUsername }"
                >
                    <span class="msg-time">{{ msg.created_at }}</span>
                    <span class="msg-username">{{ msg.username }}</span>
                    <span class="msg-text">{{ msg.message }}</span>
                </div>
                <div v-if="messages.length === 0" class="no-messages">
                    Aucun message. Soyez le premier !
                </div>
            </div>

            <div v-if="sendError" class="send-error">{{ sendError }}</div>

            <form class="chat-input" @submit.prevent="sendMessage">
                <input
                    type="text"
                    v-model="newMessage"
                    placeholder="Votre message..."
                    maxlength="500"
                    :disabled="sending"
                />
                <button type="submit" :disabled="!newMessage.trim() || sending">
                    Envoyer
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    currentUsername: {
        type: String,
        default: ''
    }
});

defineEmits(['close']);

const messages = ref([]);
const newMessage = ref('');
const sending = ref(false);
const sendError = ref('');
const messagesContainer = ref(null);
let pollInterval = null;
let lastMessageId = 0;

const getCSRFToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.content;
};

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

const loadMessages = async () => {
    try {
        const response = await fetch(`/api/chat?last_id=${lastMessageId}`, {
            credentials: 'same-origin'
        });
        if (response.ok) {
            const data = await response.json();
            if (data.messages.length > 0) {
                if (lastMessageId === 0) {
                    messages.value = data.messages;
                } else {
                    messages.value.push(...data.messages);
                }
                lastMessageId = data.messages[data.messages.length - 1].id;
                scrollToBottom();
            }
        }
    } catch (e) {
        console.error('Erreur chargement messages:', e);
    }
};

const sendMessage = async () => {
    if (!newMessage.value.trim() || sending.value) return;

    sending.value = true;
    sendError.value = '';
    try {
        const response = await fetch('/api/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ message: newMessage.value }),
        });

        const data = await response.json();

        if (response.ok) {
            messages.value.push(data.message);
            lastMessageId = data.message.id;
            newMessage.value = '';
            scrollToBottom();
        } else if (response.status === 401) {
            sendError.value = 'You must be logged in to send messages.';
        } else if (response.status === 422 && data.errors) {
            const firstError = Object.values(data.errors)[0];
            sendError.value = Array.isArray(firstError) ? firstError[0] : firstError;
        } else {
            sendError.value = data.error ?? 'Failed to send message.';
        }
    } catch (e) {
        console.error('Erreur envoi message:', e);
        sendError.value = 'Network error. Please try again.';
    } finally {
        sending.value = false;
    }
};

onMounted(() => {
    loadMessages();
    // Polling toutes les 2 secondes
    pollInterval = setInterval(loadMessages, 2000);
});

onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
});
</script>

<style scoped>
.chat-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    z-index: 400;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.chat-container {
    width: 90%;
    max-width: 500px;
    height: 70vh;
    max-height: 600px;
    background: linear-gradient(135deg, #1a1a2e, #16213e);
    border-radius: 20px;
    border: 3px solid #4ade80;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 0 40px rgba(74, 222, 128, 0.3);
}

.chat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: rgba(74, 222, 128, 0.2);
    border-bottom: 2px solid #4ade80;
}

.chat-header h2 {
    margin: 0;
    color: #4ade80;
    font-size: 1.2rem;
}

.close-btn {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: rgba(255, 100, 100, 0.8);
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.2s;
}

.close-btn:hover {
    background: #ff4444;
    transform: scale(1.1);
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.chat-messages::-webkit-scrollbar {
    width: 8px;
}

.chat-messages::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
}

.chat-messages::-webkit-scrollbar-thumb {
    background: #4ade80;
    border-radius: 4px;
}

.chat-message {
    padding: 8px 12px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    border-left: 3px solid #4ade80;
}

.chat-message.own-message {
    background: rgba(74, 222, 128, 0.15);
    border-left-color: #ffd700;
}

.msg-time {
    font-size: 0.7rem;
    color: #666;
    margin-right: 8px;
}

.msg-username {
    font-weight: bold;
    color: #4ade80;
    margin-right: 8px;
}

.own-message .msg-username {
    color: #ffd700;
}

.msg-text {
    color: #fff;
    word-break: break-word;
}

.no-messages {
    text-align: center;
    color: #666;
    font-style: italic;
    margin-top: 20px;
}

.send-error {
    padding: 8px 15px;
    background: rgba(255, 80, 80, 0.15);
    border-top: 1px solid rgba(255, 80, 80, 0.4);
    color: #ff6b6b;
    font-size: 0.85rem;
    text-align: center;
}

.chat-input {
    display: flex;
    gap: 10px;
    padding: 15px;
    background: rgba(0, 0, 0, 0.3);
    border-top: 2px solid rgba(74, 222, 128, 0.3);
}

.chat-input input {
    flex: 1;
    padding: 12px 15px;
    border: 2px solid #4ade80;
    border-radius: 25px;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    font-size: 0.95rem;
    outline: none;
    transition: all 0.2s;
}

.chat-input input:focus {
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 0 15px rgba(74, 222, 128, 0.3);
}

.chat-input input::placeholder {
    color: #888;
}

.chat-input button {
    padding: 12px 25px;
    background: linear-gradient(135deg, #4ade80, #22c55e);
    border: none;
    border-radius: 25px;
    color: #1a1a2e;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
}

.chat-input button:hover:not(:disabled) {
    transform: scale(1.05);
    box-shadow: 0 0 20px rgba(74, 222, 128, 0.5);
}

.chat-input button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
