<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Временные мок-данные для списка воронок
const funnels = ref([
  { id: 1, name: 'Lead Qualification', status: 'active', bot: 'Demo AI Router', nodes: 5 },
  { id: 2, name: 'Webinar Onboarding', status: 'draft', bot: 'GetCourse Bot', nodes: 12 }
])

// Переход в редактор по клику на карточку
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
        
        <!-- Шапка: Заголовок и кнопка создания -->
        <div class="flex justify-between items-center mb-8">
          <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Мои воронки</h1>
          <button class="bg-blue-500 hover:bg-blue-600 text-white px-5 py-2.5 rounded-lg font-medium shadow-sm transition-colors flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Создать воронку
          </button>
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
                :class="funnel.status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
              >
                {{ funnel.status === 'active' ? 'Активна' : 'Черновик' }}
              </div>
              <!-- Иконка перехода появляется при наведении -->
              <svg class="w-5 h-5 text-slate-400 group-hover:text-blue-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
              </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-1">{{ funnel.name }}</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">{{ funnel.bot }}</p>
            <div class="text-xs text-slate-400 dark:text-slate-500 flex items-center">
              <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
              </svg>
              Узлов: {{ funnel.nodes }}
            </div>
          </div>
        </div>

      </div>
    </main>

  </div>
</template>