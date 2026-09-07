import { ref } from 'vue';

const currentLang = ref(localStorage.getItem('app_lang') || 'kh');

export function useLang() {
  const setLang = (lang) => {
    currentLang.value = lang;
    localStorage.setItem('app_lang', lang);
  };

  const toggleLang = () => {
    setLang(currentLang.value === 'kh' ? 'en' : 'kh');
  };

  return {
    lang: currentLang,
    setLang,
    toggleLang
  };
}
