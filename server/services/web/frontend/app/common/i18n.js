import i18next from 'i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import resourcesToBackend from 'i18next-resources-to-backend';

await i18next
    .use(resourcesToBackend((lang, namespace) => import(`/assets/i18n/${lang}/${namespace}.json`)))
    .use(LanguageDetector)
    .on('failedLoading', (lng, ns, msg) => console.error(msg))
    .init({
        'supportedLngs': ['en', 'de'],
        'fallbackLng': 'en',
        'ns': ['common', 'layout', 'dashboard', 'events', 'sensors', 'services', 'platforms', 'settings', 'accounts', 'tasks', 'logs', 'info', 'setup'],
        'detection': {
            order: ['localStorage', 'navigator']
        }
    });

export default {
    t: i18next.t,
    setLanguage: (lang) => i18next.changeLanguage(lang),
    getLanguage: () => i18next.resolvedLanguage
};