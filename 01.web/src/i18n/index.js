import Vue from 'vue'
import VueI18n from 'vue-i18n'

import zh from './zh'
import en from './en'

Vue.use(VueI18n)

const messages = {
    zh,
    en
}
const i18n = new VueI18n({
    messages,
    locale: navigator.language.substring(0, 2),
    fallbackLocale: 'en',
})
export default i18n
