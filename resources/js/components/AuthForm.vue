<template>
    <div class="auth-overlay">
        <div class="auth-modal">
            <div class="auth-header">
                <span class="auth-icon">🎰</span>
                <h2>{{ isLogin ? t('auth.login') : t('auth.register') }}</h2>
            </div>

            <form @submit.prevent="submit">
                <div v-if="!isLogin" class="form-group">
                    <label>{{ t('auth.username') }}</label>
                    <input
                        v-model="form.name"
                        type="text"
                        :placeholder="t('auth.usernamePlaceholder')"
                        required
                    />
                </div>

                <div class="form-group">
                    <label>{{ t('auth.email') }}</label>
                    <input
                        v-model="form.email"
                        type="email"
                        placeholder="votre@email.com"
                        required
                    />
                </div>

                <div class="form-group">
                    <label>{{ t('auth.password') }}</label>
                    <input
                        v-model="form.password"
                        type="password"
                        placeholder="••••••••"
                        required
                    />
                </div>

                <p v-if="error" class="error">{{ error }}</p>

                <button type="submit" class="submit-btn" :disabled="loading">
                    {{ loading ? t('auth.loading') : (isLogin ? t('auth.loginBtn') : t('auth.registerBtn')) }}
                </button>
            </form>

            <p class="switch-mode">
                {{ isLogin ? t('auth.noAccount') : t('auth.alreadyAccount') }}
                <a href="#" @click.prevent="switchMode">
                    {{ isLogin ? t('auth.switchToRegister') : t('auth.switchToLogin') }}
                </a>
            </p>

            <div class="bonus-info">
                <span class="bonus-icon">🎁</span>
                <span>{{ t('auth.bonus') }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useI18n } from '../i18n.js';

const { t } = useI18n();

const emit = defineEmits(['authenticated']);

const isLogin = ref(true);
const loading = ref(false);
const error = ref('');

const form = reactive({
    name: '',
    email: '',
    password: '',
});

const getCSRFToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.content;
};

const switchMode = () => {
    isLogin.value = !isLogin.value;
    error.value = '';
};

const submit = async () => {
    loading.value = true;
    error.value = '';

    const url = isLogin.value ? '/api/login' : '/api/register';

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify(form),
        });

        const data = await response.json();

        if (!response.ok) {
            if (data.errors) {
                // Erreurs de validation Laravel
                const firstError = Object.values(data.errors)[0];
                error.value = Array.isArray(firstError) ? firstError[0] : firstError;
            } else {
                error.value = data.message || t('auth.errorOccurred');
            }
            return;
        }

        emit('authenticated', data.user);
    } catch (e) {
        error.value = t('auth.serverError');
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.auth-overlay {
    position: fixed;
    inset: 0;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
}

.auth-modal {
    background: linear-gradient(145deg, #2d2d44 0%, #1f1f35 100%);
    padding: 40px;
    border-radius: 20px;
    width: 100%;
    max-width: 400px;
    box-shadow:
        0 20px 60px rgba(0, 0, 0, 0.5),
        0 0 40px rgba(255, 215, 0, 0.1);
    border: 2px solid rgba(255, 215, 0, 0.2);
}

.auth-header {
    text-align: center;
    margin-bottom: 30px;
}

.auth-icon {
    font-size: 50px;
    display: block;
    margin-bottom: 10px;
}

h2 {
    color: #ffd700;
    font-size: 28px;
    margin: 0;
    text-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 600;
    font-size: 14px;
}

input {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    font-size: 16px;
    background: rgba(0, 0, 0, 0.3);
    color: white;
    transition: all 0.3s;
}

input::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

input:focus {
    outline: none;
    border-color: #ffd700;
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
}

.submit-btn {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #ffd700, #ffaa00);
    border: none;
    border-radius: 10px;
    font-size: 18px;
    font-weight: 700;
    color: #1a1a2e;
    cursor: pointer;
    margin-top: 10px;
    transition: all 0.3s;
}

.submit-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(255, 215, 0, 0.4);
}

.submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.error {
    color: #ff6b6b;
    text-align: center;
    margin: 15px 0;
    padding: 10px;
    background: rgba(255, 107, 107, 0.1);
    border-radius: 8px;
    font-size: 14px;
}

.switch-mode {
    text-align: center;
    margin-top: 25px;
    color: rgba(255, 255, 255, 0.6);
    font-size: 14px;
}

.switch-mode a {
    color: #ffd700;
    font-weight: 600;
    text-decoration: none;
    margin-left: 5px;
}

.switch-mode a:hover {
    text-decoration: underline;
}

.bonus-info {
    margin-top: 25px;
    text-align: center;
    padding: 15px;
    background: linear-gradient(135deg, rgba(39, 174, 96, 0.2), rgba(39, 174, 96, 0.1));
    border-radius: 10px;
    border: 1px solid rgba(39, 174, 96, 0.3);
}

.bonus-icon {
    font-size: 20px;
    margin-right: 8px;
}

.bonus-info span {
    color: #2ecc71;
    font-weight: 600;
}
</style>
