import { ref } from 'vue'

// 1. Проверяем сохраненную тему при первой загрузке
const isDark = ref(
  localStorage.getItem('theme') === 'dark' || 
  (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
)

// 2. Сразу применяем нужный класс к HTML (чтобы не было белых вспышек)
if (isDark.value) {
  document.documentElement.classList.add('dark')
} else {
  document.documentElement.classList.remove('dark')
}

export function useTheme() {
  const toggleTheme = () => {
    isDark.value = !isDark.value
    
    // 3. Жестко переключаем класс и сохраняем в память браузера
    if (isDark.value) {
      document.documentElement.classList.add('dark')
      localStorage.setItem('theme', 'dark')
    } else {
      document.documentElement.classList.remove('dark')
      localStorage.setItem('theme', 'light')
    }
  }

  return { isDark, toggleTheme }
}