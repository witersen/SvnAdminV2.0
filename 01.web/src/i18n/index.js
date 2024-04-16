import Vue from 'vue'
import VueI18n from 'vue-i18n'
import iView from 'view-design'

import customZhCn from './zh-CN'
import customEnUs from './en-US'
import zhCN from 'view-design/dist/locale/zh-CN'
import enUS from 'view-design/dist/locale/en-US'

Vue.use(VueI18n)

const messages = {
    'zh': Object.assign(zhCN, customZhCn),
    'en': Object.assign(enUS, customEnUs),
}
const navLang = navigator.language.substring(0, 2)//自动识别浏览器语言
const localLang = navLang || false
let lang = localLang || 'zh'

const i18n = new VueI18n({
    messages,
    locale: lang,
    fallbackLocale: 'en',
    // silentTranslationWarn: true,
})

Vue.use(iView, { 
    i18n: (key, value) => i18n.t(key, value)
})

export default i18n
