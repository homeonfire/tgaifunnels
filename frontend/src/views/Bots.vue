<script setup>
import { ref, onMounted, watch } from 'vue'
import { api } from '../api'
import Sidebar from '../components/Sidebar.vue'

const bots = ref([])
const availableKeys = ref([])
const showModal = ref(false)
const isEditing = ref(false)

// Форма бота
const botForm = ref({ id: null, name: '', telegram_token: '', ai_key_id: null, ai_model: '' })

// Состояние для списка моделей
const apiModels = ref([])
const isLoadingModels = ref(false)

const fetchData = async () => {
  try {
    const [botsRes, keysRes] = await Promise.all([
      api.get('/bots'),
      api.get('/ai-keys')
    ])
    bots.value = botsRes.data
    availableKeys.value = keysRes.data
  } catch (error) {
    console.error('Ошибка загрузки:', error)
  }
}

// Запрашиваем модели у API провайдера
const fetchModelsForKey = async (keyId) => {
  if (!keyId) {
    apiModels.value = []
    botForm.value.ai_model = ''
    return
  }
  
  isLoadingModels.value = true
  try {
    const { data } = await api.get(`/ai-keys/${keyId}/models`)
    apiModels.value = data
    // Если у бота еще не выбрана модель, ставим первую из списка
    if (!botForm.value.ai_model && data.length > 0) {
      botForm.value.ai_model = data[0]
    }
  } catch (error) {
    console.error('Ошибка получения моделей:', error)
    apiModels.value = []
  } finally {
    isLoadingModels.value = false
  }
}

// Следим за изменением ключа в форме
watch(() => botForm.value.ai_key_id, (newKeyId, oldKeyId) => {
  // Если мы только открыли форму редактирования, не сбрасываем модель
  if (newKeyId && newKeyId !== oldKeyId) {
    fetchModelsForKey(newKeyId)
  }
})

const openModal = async (bot = null) => {
  isEditing.value = !!bot
  botForm.value = bot 
    ? { ...bot } 
    : { id: null, name: '', telegram_token: '', ai_key_id: null, ai_model: '' }
  
  apiModels.value = []
  showModal.value = true

  // Если открываем редактирование и есть ключ, сразу грузим список моделей
  if (bot && bot.ai_key_id) {
    await fetchModelsForKey(bot.ai_key_id)
  }
}

const saveBot = async () => {
  try {
    if (isEditing.value) {
      await api.put(`/bots/${botForm.value.id}`, botForm.value)
    } else {
      await api.post('/bots', botForm.value)
    }
    showModal.value = false
    fetchData()
  } catch (e) { console.error(e) }
}

const deleteBot = async (id) => {
  if (!confirm('Точно удалить бота?')) return
  try {
    await api.delete(`/bots/${id}`)
    fetchData()
  } catch (e) { console.error(e) }
}

onMounted(fetchData)
</script>

<template>
  <div class="flex h-screen bg-slate-50 dark:bg-slate-900">
    <Sidebar />

    <main class="flex-1 overflow-y-auto">
      <div class="p-8 max-w-5xl mx-auto">
        <div class="flex justify-between items-center mb-8">
          <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Telegram Боты</h1>
            <p class="text-slate-500 mt-2">Управляйте ботами и назначайте им ИИ-мозги.</p>
          </div>
          <button @click="openModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">+ Создать бота</button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="bot in bots" :key="bot.id" class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm relative group">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
              🤖 {{ bot.name }}
            </h3>
            
            <div class="space-y-3 mb-6 text-sm">
              <div class="flex justify-between items-center pb-2 border-b border-slate-100 dark:border-slate-700/50">
                <span class="text-slate-500">TG Token:</span>
                <span class="font-mono text-slate-700 dark:text-slate-300">{{ bot.telegram_token ? '••••' + bot.telegram_token.slice(-4) : 'Нет' }}</span>
              </div>
              <div class="flex justify-between items-center pb-2 border-b border-slate-100 dark:border-slate-700/50">
                <span class="text-slate-500">Ключ AI:</span>
                <span v-if="bot.ai_key" class="font-medium text-violet-500">{{ bot.ai_key.name }}</span>
                <span v-else class="text-red-400">Не выбран</span>
              </div>
              <div class="flex justify-between items-center pb-2 border-b border-slate-100 dark:border-slate-700/50">
                <span class="text-slate-500">Модель:</span>
                <span v-if="bot.ai_model" class="font-mono text-xs px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded text-slate-600 dark:text-slate-300">{{ bot.ai_model }}</span>
                <span v-else class="text-slate-400">—</span>
              </div>
            </div>

            <div class="flex gap-2">
              <button @click="openModal(bot)" class="flex-1 px-3 py-2 text-sm bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg transition-colors font-medium">Настроить</button>
              <button @click="deleteBot(bot.id)" class="px-3 py-2 text-sm text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40 rounded-lg transition-colors">Удалить</button>
            </div>
          </div>
        </div>

        <!-- МОДАЛКА -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
          <div class="bg-white dark:bg-slate-800 w-full max-w-md rounded-2xl p-6 shadow-2xl mx-4 border border-slate-200 dark:border-slate-700">
            <h3 class="text-xl font-bold mb-6 dark:text-white">{{ isEditing ? 'Настройки бота' : 'Новый бот' }}</h3>
            
            <div class="space-y-4 mb-8">
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Название бота</label>
                <input v-model="botForm.name" placeholder="Sales Bot" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 dark:text-white transition-shadow" />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Telegram Token</label>
                <input v-model="botForm.telegram_token" placeholder="123456789:AAH..." class="w-full px-4 py-2.5 font-mono text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 dark:text-white transition-shadow" />
              </div>
              
              <div class="pt-4 border-t border-slate-100 dark:border-slate-700">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ключ нейросети</label>
                <select v-model="botForm.ai_key_id" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg outline-none focus:ring-2 focus:ring-violet-500 dark:text-white appearance-none transition-shadow">
                  <option :value="null">Без ИИ</option>
                  <option v-for="key in availableKeys" :key="key.id" :value="key.id">🔑 {{ key.name }}</option>
                </select>
              </div>

              <!-- ВЫБОР МОДЕЛИ (показываем только если выбран ключ) -->
              <div v-if="botForm.ai_key_id">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1 flex justify-between">
                  <span>Модель (Live API)</span>
                  <span v-if="isLoadingModels" class="text-violet-500 text-xs animate-pulse">Загрузка...</span>
                </label>
                <select 
                  v-model="botForm.ai_model" 
                  :disabled="isLoadingModels || apiModels.length === 0"
                  class="w-full px-4 py-2.5 font-mono text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg outline-none focus:ring-2 focus:ring-violet-500 dark:text-white appearance-none disabled:opacity-50 transition-shadow"
                >
                  <option value="" disabled>Выберите модель...</option>
                  <option v-for="model in apiModels" :key="model" :value="model">{{ model }}</option>
                </select>
              </div>
            </div>

            <div class="flex justify-end gap-3">
              <button @click="showModal = false" class="px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700 rounded-lg transition-colors">Отмена</button>
              <button @click="saveBot" class="px-5 py-2.5 text-sm font-medium bg-blue-500 hover:bg-blue-600 text-white rounded-lg shadow-sm transition-colors">Сохранить</button>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>
</template>