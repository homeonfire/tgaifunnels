<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useTheme } from '../composables/useTheme'
import { api } from '../api'

const router = useRouter()
const { isDark, toggleTheme } = useTheme()

// Теперь это не моки, а пустой массив, который заполнится с бэкенда
const funnels = ref([])
const isLoading = ref(true)

// Функция загрузки данных из Laravel
const fetchFunnels = async () => {
  try {
    const response = await api.get('/funnels')
    funnels.value = response.data
  } catch (error) {
    console.error('Ошибка при загрузке воронок:', error)
  } finally {
    isLoading.value = false
  }
}

// Запускаем при загрузке компонента
onMounted(() => {
  fetchFunnels()
})

const isCreating = ref(false)

// Функция создания новой воронки
const createNewFunnel = async () => {
  if (isCreating.value) return
  isCreating.value = true
  
  try {
    const response = await api.post('/funnels')
    const newFunnelId = response.data.id
    // Сразу кидаем пользователя в редактор новой воронки
    router.push(`/editor/${newFunnelId}`)
  } catch (error) {
    console.error('Ошибка при создании воронки:', error)
    alert('Не удалось создать воронку')
  } finally {
    isCreating.value = false
  }
}

const openEditor = (id) => {
  router.push(`/editor/${id}`)
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 flex">
    
    <!-- Боковое меню навигации (Sidebar) -->
    <aside class="w-64 bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 p-6">
      <div class="text-2xl font-bold text-slate-800 dark:text-white mb-8">AI Funnels</div>
      <nav class="space-y-2">
        <a href="#" class="block px-4 py-2 rounded-lg bg-blue-50 text-blue-600 dark:bg-slate-700 dark:text-white font-medium">Воронки</a>
        <a href="#" class="block px-4 py-2 rounded-lg text-slate-600 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-700 transition-colors">Боты</a>
        <a href="#" class="block px-4 py-2 rounded-lg text-slate-600 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-700 transition-colors">Настройки</a>
      </nav>
    </aside>

    <!-- Главная рабочая область -->
    <main class="flex-1 p-8">
      <div class="max-w-5xl mx-auto">
        
        <!-- Шапка: Заголовок и кнопки -->
        <div class="flex justify-between items-center mb-8">
          <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Мои воронки</h1>
          
          <div class="flex items-center gap-4">
            <!-- Кнопка переключения темы -->
            <button @click="toggleTheme" class="p-2 text-slate-500 hover:text-amber-500 dark:hover:text-blue-400 transition-colors">
              <svg v-if="!isDark" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
              <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
              </svg>
            </button>

            <button 
              @click="createNewFunnel"
              :disabled="isCreating"
              class="bg-blue-500 hover:bg-blue-600 disabled:bg-blue-400 text-white px-5 py-2.5 rounded-lg font-medium shadow-sm transition-colors flex items-center"
            >
              <!-- Показываем спиннер загрузки, если идет создание -->
              <svg v-if="isCreating" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <!-- Обычная иконка плюса -->
              <svg v-else class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              {{ isCreating ? 'Создаем...' : 'Создать воронку' }}
            </button>
          </div>
        </div>

        <!-- Сетка карточек воронок -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="funnel in funnels" 
            :key="funnel.id"
            @click="openEditor(funnel.id)"
            class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-6 cursor-pointer hover:shadow-md hover:border-blue-300 dark:hover:border-blue-500 transition-all group"
          >
            <div class="flex justify-between items-start mb-4">
              <div 
                class="px-2.5 py-1 rounded-full text-xs font-medium"
                :class="funnel.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
              >
                {{ funnel.is_active ? 'Активна' : 'Черновик' }}
              </div>
              <!-- Иконка перехода появляется при наведении -->
              <svg class="w-5 h-5 text-slate-400 group-hover:text-blue-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
              </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-1">{{ funnel.name }}</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">{{ funnel.bot?.name || 'Бот не привязан' }}</p>
            <div class="text-xs text-slate-400 dark:text-slate-500 flex items-center">
              <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
              </svg>
              Узлов: {{ funnel.nodes_count || 0 }}
            </div>
          </div>
        </div>

      </div>
    </main>

  </div>
</template>