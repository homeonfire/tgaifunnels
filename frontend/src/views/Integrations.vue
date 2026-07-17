<script setup>
import { ref, onMounted, computed } from 'vue'
import { api } from '../api'
import Sidebar from '../components/Sidebar.vue'

// --- ВИЗУАЛЬНАЯ БАЗА ПРОВАЙДЕРОВ ---
// Здесь мы задаем витрину. Потом можно будет добавлять флаги isPremium, 
// чтобы блочить их для бесплатных пользователей.
const availableProviders = [
  { 
    id: 'deepseek', 
    name: 'DeepSeek', 
    description: 'Быстрая, недорогая и умная модель. Идеально для старта.', 
    icon: '🐋',
    badge: 'Рекомендуем'
  },
  { 
    id: 'openai', 
    name: 'OpenAI (ChatGPT)', 
    description: 'Доступ к GPT-4o и передовым алгоритмам анализа.', 
    icon: '⚡',
    badge: null
  },
  { 
    id: 'anthropic', 
    name: 'Anthropic (Claude)', 
    description: 'Модель Claude 3.5 Sonnet. Лучшая в написании текстов.', 
    icon: '🧠',
    badge: 'PRO'
  }
]

// --- СОСТОЯНИЕ ---
const savedKeys = ref([])
const activeInputs = ref({}) // Хранит вводимые токены { deepseek: 'sk-...', openai: '' }
const isSaving = ref(false)

// --- ЗАГРУЗКА ---
const fetchKeys = async () => {
  try {
    const { data } = await api.get('/ai-keys')
    savedKeys.value = data
  } catch (error) {
    console.error('Ошибка загрузки ключей:', error)
  }
}

// --- ЛОГИКА ПОИСКА ---
// Проверяет, есть ли уже сохраненный ключ для конкретного провайдера
const getKeyForProvider = (providerId) => {
  return savedKeys.value.find(k => k.provider === providerId)
}

// --- СОХРАНЕНИЕ ---
const saveKey = async (provider) => {
  const token = activeInputs.value[provider.id]
  if (!token || token.trim() === '') return
  
  isSaving.value = true
  try {
    const existingKey = getKeyForProvider(provider.id)
    
    if (existingKey) {
      // Обновляем (хотя в нашем UI мы скрываем поле ввода, если ключ есть, 
      // но оставляем логику на случай добавления кнопки "Редактировать")
      await api.put(`/ai-keys/${existingKey.id}`, { 
        name: existingKey.name, 
        provider: provider.id, 
        token 
      })
    } else {
      // Создаем новый
      await api.post('/ai-keys', { 
        name: `Ключ ${provider.name}`, 
        provider: provider.id, 
        token 
      })
    }
    
    activeInputs.value[provider.id] = '' // Очищаем поле ввода
    await fetchKeys() // Обновляем список
  } catch (error) {
    console.error('Ошибка сохранения:', error)
  } finally {
    isSaving.value = false
  }
}

// --- УДАЛЕНИЕ ---
const deleteKey = async (keyId) => {
  if (!confirm('Отключить этого провайдера? Вся генерация через него остановится.')) return
  try {
    await api.delete(`/ai-keys/${keyId}`)
    await fetchKeys()
  } catch (error) {
    console.error('Ошибка удаления:', error)
  }
}

onMounted(fetchKeys)
</script>

<template>
  <div class="flex h-screen bg-slate-50 dark:bg-slate-900">
    <Sidebar />

    <main class="flex-1 overflow-y-auto">
      <div class="p-8 max-w-6xl mx-auto">
        
        <div class="mb-8">
          <h1 class="text-2xl font-bold text-slate-800 dark:text-white">AI Интеграции</h1>
          <p class="text-slate-500 mt-2">Подключите API ключи нейросетей, чтобы боты могли общаться с клиентами.</p>
        </div>

        <!-- ВИТРИНА ПРОВАЙДЕРОВ -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="provider in availableProviders" 
            :key="provider.id" 
            class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden flex flex-col transition-all hover:shadow-md"
          >
            <!-- Шапка карточки -->
            <div class="p-6 border-b border-slate-100 dark:border-slate-700">
              <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 bg-slate-100 dark:bg-slate-700 rounded-xl flex items-center justify-center text-2xl">
                  {{ provider.icon }}
                </div>
                <!-- Бейджики -->
                <span v-if="provider.badge" :class="[
                  'text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-md',
                  provider.badge === 'PRO' ? 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400' : 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
                ]">
                  {{ provider.badge }}
                </span>
              </div>
              
              <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-1">{{ provider.name }}</h3>
              <p class="text-sm text-slate-500 leading-relaxed min-h-[40px]">{{ provider.description }}</p>
            </div>

            <!-- Тело карточки (Логика подключения) -->
            <div class="p-6 bg-slate-50 dark:bg-slate-800/50 flex-1 flex flex-col justify-end">
              
              <!-- СОСТОЯНИЕ 1: КЛЮЧ ПОДКЛЮЧЕН -->
              <div v-if="getKeyForProvider(provider.id)" class="w-full">
                <div class="flex items-center gap-2 text-sm text-emerald-600 dark:text-emerald-400 font-medium mb-3">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                  Активно
                </div>
                <div class="bg-white dark:bg-slate-900 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 font-mono text-sm text-slate-400 mb-4 truncate">
                  {{ getKeyForProvider(provider.id).token.substring(0, 8) }}••••••••••••
                </div>
                <button 
                  @click="deleteKey(getKeyForProvider(provider.id).id)" 
                  class="w-full py-2.5 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40 rounded-lg transition-colors"
                >
                  Отключить
                </button>
              </div>

              <!-- СОСТОЯНИЕ 2: КЛЮЧА НЕТ (НУЖНО ВВЕСТИ) -->
              <div v-else class="w-full">
                <input 
                  v-model="activeInputs[provider.id]" 
                  type="password" 
                  placeholder="Введите API ключ (sk-...)" 
                  class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-800 dark:text-white outline-none focus:ring-2 focus:ring-violet-500 mb-3 font-mono transition-shadow"
                />
                <button 
                  @click="saveKey(provider)"
                  :disabled="!activeInputs[provider.id] || isSaving"
                  class="w-full py-2.5 text-sm font-medium text-white bg-slate-800 hover:bg-slate-900 dark:bg-violet-600 dark:hover:bg-violet-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors shadow-sm"
                >
                  {{ isSaving ? 'Проверка...' : 'Подключить' }}
                </button>
              </div>

            </div>
          </div>
        </div>

      </div>
    </main>
  </div>
</template>