import { inject } from 'vue';
import en from './locales/en.js';
import fr from './locales/fr.js';

const locales = { en, fr };

export function useI18n() {
    const locale = inject('locale');

    const t = (key) => {
        const keys = key.split('.');
        let val = locales[locale.value] ?? locales.fr;
        for (const k of keys) {
            if (val == null) return key;
            val = val[k];
        }
        return val ?? key;
    };

    return { t, locale };
}
