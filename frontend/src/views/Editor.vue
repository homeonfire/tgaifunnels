<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { VueFlow, useVueFlow, addEdge } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import FunnelNode from '../components/FunnelNode.vue'
import SettingsSidebar from '../components/SettingsSidebar.vue'
import { useTheme } from '../composables/useTheme'
import { api } from '../api'

const router = useRouter()
const route = useRoute() // Нужен для получения ID из URL (/editor/1)
const { project } = useVueFlow() 
const { isDark, toggleTheme } = useTheme() 

const funnelId = route.params.id // ID текущей воронки
const selectedNode = ref(null)
const funnelName = ref('Загрузка...')
const isActive = ref(false)
const funnelBotId = ref(null)

const showSettingsModal = ref(false) // Показывать ли модалку настроек
const bots = ref([]) // Список ботов с бэкенда

const contextMenu = ref({ show: false, x: 0, y: 0, canvasX: 0, canvasY: 0 })

// Теперь массивы изначально пустые
const nodes = ref([])
const edges = ref([])

// ID активного узла
const activeStepId = ref(null) 

// Функция загрузки списка ботов
const fetchBots = async () => {
  try {
    const { data } = await api.get('/bots')
    bots.value = data
  } catch (error) {
    console.error('Ошибка загрузки ботов:', error)
  }
}

// ФУНКЦИЯ ЗАГРУЗКИ ДАННЫХ ИЗ LARAVEL
const loadFunnel = async () => {
  try {
    const { data } = await api.get(`/funnels/${funnelId}`)
    
    // 1. Устанавливаем настройки воронки
    funnelName.value = data.funnel.name
    isActive.value = data.funnel.is_active
    funnelBotId.value = data.funnel.bot_id

    // 2. Мапим шаги из БД в узлы Vue Flow (Nodes)
    nodes.value = data.funnel.steps.map(step => ({
      id: step.id.toString(),
      type: 'funnelNode',
      position: { x: step.pos_x, y: step.pos_y },
      data: { 
        label: step.name, 
        description: step.message_text || '',
        useAi: step.use_ai,
        aiPrompt: step.ai_prompt || '',
        handles: step.handles && step.handles.length > 0 
          ? step.handles 
          : [{ id: 'default', label: 'Далее', rules: [] }],
        // Подтягиваем переменные из базы или ставим пустой массив
        extractedVariables: step.extracted_variables || [] 
      }
    }))

    // 3. Мапим переходы из БД в связи Vue Flow (Edges)
    edges.value = data.transitions.map(t => ({
      id: `e${t.from_step_id}-${t.to_step_id}`,
      source: t.from_step_id.toString(),
      target: t.to_step_id.toString(),
      sourceHandle: t.source_handle || 'default',
      animated: true,
      style: { stroke: '#3b82f6', strokeWidth: 2 }
    }))

  } catch (error) {
    console.error('Ошибка загрузки воронки:', error)
  }
}

// ФУНКЦИЯ СОХРАНЕНИЯ В LARAVEL
const isSaving = ref(false)

// СОСТОЯНИЕ УВЕДОМЛЕНИЙ (TOAST)
const toast = ref({
  show: false,
  message: '',
  type: 'success' // 'success' или 'error'
})

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  // Прячем через 3 секунды
  setTimeout(() => {
    toast.value.show = false
  }, 3000)
}

const saveFunnel = async () => {
  isSaving.value = true
  console.log("Данные, которые улетают на сервер:", edges.value);
  try {
    await api.post(`/funnels/${funnelId}/schema`, {
      name: funnelName.value,
      is_active: isActive.value,
      bot_id: funnelBotId.value,
      nodes: nodes.value,
      edges: edges.value
    })
    
    // Заменили алерт!
    showToast('Схема успешно сохранена!')
    
    await loadFunnel() 
    
  } catch (error) {
    console.error('Ошибка при сохранении:', error)
    // Заменили алерт!
    showToast('Ошибка при сохранении схемы', 'error')
  } finally {
    isSaving.value = false
  }
}

// Функция-обертка: обновляет узел, прячет панель и пушит данные в БД
const saveNodeAndClose = async (updatedData) => {
  // Находим активный узел в нашем массиве Vue Flow
  const nodeIndex = nodes.value.findIndex(n => n.id === selectedNode.value.id)
  
  if (nodeIndex !== -1) {
    // Реактивно обновляем данные узла
    nodes.value[nodeIndex].data = { ...updatedData }
  }

  selectedNode.value = null // Скрываем сайдбар
  await saveFunnel()        // Отправляем схему в Laravel
}

// Запускаем при открытии страницы
onMounted(() => {
  loadFunnel()
})

const hideContextMenu = () => { contextMenu.value.show = false }

const onNodeClick = (event) => {
  hideContextMenu()
  selectedNode.value = event.node
}

const onPaneClick = () => {
  hideContextMenu()
  selectedNode.value = null
}

const onPaneContextMenu = (event) => {
  event.preventDefault() 
  const projectedPosition = project({ x: event.clientX, y: event.clientY - 56 })
  contextMenu.value = {
    show: true, x: event.clientX, y: event.clientY, canvasX: projectedPosition.x, canvasY: projectedPosition.y
  }
}

// Генерация временного ID для новых узлов (чтобы не было конфликтов с БД)
const generateTempId = () => 'temp_' + Math.random().toString(36).substr(2, 9)

const addNodeFromMenu = () => {
  nodes.value.push({
    id: generateTempId(),
    type: 'funnelNode',
    position: { x: contextMenu.value.canvasX, y: contextMenu.value.canvasY },
    data: { label: 'Новый этап', description: '', useAi: false, aiPrompt: '', handles: [{ id: 'default', label: 'Далее' }] }
  })
  hideContextMenu()
}

const addNewNode = () => {
  nodes.value.push({
    id: generateTempId(),
    type: 'funnelNode',
    position: { x: 350 + (nodes.value.length * 20), y: 200 + (nodes.value.length * 20) },
    data: { label: 'Новый шаг', description: '', useAi: false, aiPrompt: '', handles: [{ id: 'default', label: 'Далее' }] }
  })
}

const onConnect = (params) => {
  params.animated = true
  params.style = { stroke: '#3b82f6', strokeWidth: 2 }
  edges.value = addEdge(params, edges.value)
}

const saveSettingsAndClose = async () => {
  showSettingsModal.value = false
  await saveFunnel()
}

// --- ЛОГИКА ЭМУЛЯТОРА (СИМУЛЯТОР ЧАТА) ---
const isSimulatorOpen = ref(false)
const simulatorMessages = ref([])
const simulatorInput = ref('')
const isSimulating = ref(false)
// Генерируем случайный ID для текущей тестовой сессии
const simulatorClientId = ref('sim_' + Math.random().toString(36).substr(2, 9))

const toggleSimulator = () => {
  if (!funnelBotId.value) {
    alert('Сначала привяжите бота в настройках воронки и сохраните её!')
    return
  }
  isSimulatorOpen.value = !isSimulatorOpen.value
  // Если открыли и чат пустой, можно добавить приветствие
  if (isSimulatorOpen.value && simulatorMessages.value.length === 0) {
    simulatorMessages.value.push({ role: 'bot', text: 'Эмулятор запущен. Напишите сообщение...' })
  }
}

const sendSimulatorMessage = async () => {
  if (!simulatorInput.value.trim() || isSimulating.value) return

  const userText = simulatorInput.value
  simulatorMessages.value.push({ role: 'user', text: userText })
  simulatorInput.value = ''
  isSimulating.value = true

  try {
    const { data } = await api.post('/simulator/chat', {
      bot_id: funnelBotId.value,
      client_id: simulatorClientId.value,
      message: userText
    })

    simulatorMessages.value.push({ role: 'bot', text: data.reply })
    activeStepId.value = data.current_step_id ? data.current_step_id.toString() : null
  } catch (error) {
    console.error('Ошибка симулятора:', error)
    simulatorMessages.value.push({ role: 'bot', text: '❌ Ошибка ответа сервера.' })
  } finally {
    isSimulating.value = false
  }
}

const resetSimulator = () => {
  simulatorMessages.value = []
  activeStepId.value = null // Сбрасываем подсветку
  simulatorClientId.value = 'sim_' + Math.random().toString(36).substr(2, 9)
  simulatorMessages.value.push({ role: 'bot', text: 'Сессия сброшена.' })
}

const getNodeClass = (node) => {
  return node.id === activeStepId.value ? 'active-node-highlight' : ''
}

onMounted(() => {
  loadFunnel()
  fetchBots() // <-- Загружаем ботов при открытии редактора
})

const goBack = () => router.push('/')
</script>

<template>
  <div @click="hideContextMenu" class="w-screen h-screen bg-slate-50 dark:bg-slate-900 overflow-hidden relative flex">
    <!-- ВСПЛЫВАЮЩЕЕ УВЕДОМЛЕНИЕ (TOAST) -->
    <div 
      class="fixed top-20 right-6 z-50 transition-all duration-300 transform"
      :class="toast.show ? 'translate-y-0 opacity-100' : '-translate-y-4 opacity-0 pointer-events-none'"
    >
      <div 
        class="flex items-center px-4 py-3 rounded-lg shadow-lg border backdrop-blur-sm"
        :class="toast.type === 'success' 
          ? 'bg-emerald-50/90 dark:bg-emerald-900/80 border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400' 
          : 'bg-red-50/90 dark:bg-red-900/80 border-red-200 dark:border-red-800 text-red-700 dark:text-red-400'"
      >
        <!-- Иконка успеха -->
        <svg v-if="toast.type === 'success'" class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <!-- Иконка ошибки -->
        <svg v-else class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <span class="font-medium text-sm">{{ toast.message }}</span>
      </div>
    </div>
    <header class="absolute top-0 left-0 w-full h-14 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-700 z-40 flex items-center justify-between px-4 transition-colors">
      <div class="flex items-center gap-4">
        <button @click="goBack" class="p-1.5 text-slate-400 hover:text-slate-800 dark:hover:text-white transition-colors">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </button>
        <input v-model="funnelName" class="bg-transparent font-semibold text-lg text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 rounded px-1 w-64" />
        <div @click.stop="isActive = !isActive" class="cursor-pointer px-2.5 py-1 rounded-full text-xs font-medium transition-colors select-none" :class="isActive ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'">
          {{ isActive ? 'Active' : 'Inactive' }}
        </div>
      </div>

      <div class="flex items-center gap-3">
      <!-- Кнопка Настроек (Шестеренка) -->
        <button @click.stop="showSettingsModal = true" class="p-2 text-slate-500 hover:text-blue-500 dark:hover:text-blue-400 transition-colors" title="Настройки воронки">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </button>
        <button @click.stop="toggleTheme" class="p-2 text-slate-500 hover:text-amber-500 dark:hover:text-blue-400 transition-colors">
          <svg v-if="!isDark" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
          </svg>
        </button>
        <!-- Кнопка тестирования -->
        <!-- Кнопка тестирования -->
        <button 
          @click="toggleSimulator" 
          class="bg-indigo-100 hover:bg-indigo-200 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          Тест воронки
        </button>
        <button 
          @click="saveFunnel"
          :disabled="isSaving"
          class="px-4 py-1.5 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 disabled:bg-blue-400 rounded-lg shadow-sm transition-colors"
        >
          {{ isSaving ? 'Сохранение...' : 'Сохранить схему' }}
        </button>
      </div>
    </header>

    <div class="h-full transition-all duration-300 pt-14" :class="selectedNode ? 'w-[calc(100%-400px)]' : 'w-full'">
      <VueFlow 
        v-model:nodes="nodes" 
        v-model:edges="edges" 
        :fit-view-on-init="true"
        :max-zoom="1"
        :min-zoom="0.2"
        :node-class="getNodeClass"
        @node-click="onNodeClick"
        @pane-click="onPaneClick"
        @pane-context-menu="onPaneContextMenu"
        @connect="onConnect"
      >
        <template #node-funnelNode="props">
          <div :class="{ 'ring-2 ring-blue-500 shadow-lg rounded-lg': selectedNode?.id === props.id }">
            <FunnelNode :id="props.id" :data="props.data" />
          </div>
        </template>
        
        <Background :pattern-color="isDark ? '#333538' : '#cbd5e1'" />
        
        <Controls position="bottom-right" class="m-6 shadow-lg rounded-xl overflow-hidden bg-white dark:bg-slate-800 border-none" />
        
        <button 
          @click.stop="addNewNode"
          class="absolute bottom-6 left-6 w-14 h-14 bg-blue-500 hover:bg-blue-600 text-white rounded-full shadow-lg flex items-center justify-center text-3xl transition-transform hover:scale-105 z-10"
        >
          +
        </button>
      </VueFlow>
    </div>

    <div 
      v-if="contextMenu.show"
      :style="{ top: contextMenu.y + 'px', left: contextMenu.x + 'px' }"
      class="fixed z-50 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl rounded-lg py-2 w-48 animate-fade-in"
    >
      <button 
        @click.stop="addNodeFromMenu"
        class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 flex items-center transition-colors"
      >
        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Добавить этап
      </button>
    </div>

    <SettingsSidebar 
      :selected-node="selectedNode" 
      @close="selectedNode = null" 
      @save="saveNodeAndClose" 
    />

  </div>
  <!-- МОДАЛКА НАСТРОЕК ВОРОНКИ -->
    <div v-if="showSettingsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm animate-fade-in">
      <div class="bg-white dark:bg-slate-800 w-full max-w-md rounded-xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-700 mx-4">
        
        <!-- Шапка модалки -->
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
          <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Настройки воронки</h3>
          <button @click="showSettingsModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <!-- Тело модалки -->
        <div class="p-6 space-y-5">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Название воронки</label>
            <input 
              v-model="funnelName" 
              class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition-colors" 
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Привязка к Telegram боту</label>
            <select 
              v-model="funnelBotId" 
              class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition-colors appearance-none"
            >
              <option :value="null">Без бота (черновик)</option>
              <option v-for="bot in bots" :key="bot.id" :value="bot.id">
                🤖 {{ bot.name }}
              </option>
            </select>
          </div>
        </div>
        
        <!-- Подвал с кнопками -->
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-700 flex justify-end gap-3">
          <button 
            @click="showSettingsModal = false" 
            class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors"
          >
            Отмена
          </button>
          <button 
            @click="saveSettingsAndClose" 
            class="px-4 py-2 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-lg shadow-sm transition-colors"
          >
            Сохранить настройки
          </button>
        </div>

      </div>
    </div>

    <!-- ЭМУЛЯТОР ЧАТА (ОКНО) -->
    <div 
      v-if="isSimulatorOpen" 
      class="fixed bottom-6 right-6 w-80 md:w-96 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 flex flex-col z-50 overflow-hidden transition-all"
      style="height: 500px;"
    >
      <!-- Шапка чата -->
      <div class="bg-indigo-500 p-4 flex justify-between items-center text-white">
        <div class="flex items-center gap-2">
          <span class="text-xl">🤖</span>
          <div>
            <h3 class="font-bold text-sm">Симулятор</h3>
            <p class="text-[10px] text-indigo-100 opacity-80">Test Client: {{ simulatorClientId.substring(0,8) }}...</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button @click="resetSimulator" class="p-1 hover:bg-indigo-600 rounded text-xs" title="Сбросить сессию">🔄</button>
          <button @click="isSimulatorOpen = false" class="p-1 hover:bg-indigo-600 rounded text-xs" title="Закрыть">✖</button>
        </div>
      </div>

      <!-- Сообщения -->
      <div class="flex-1 p-4 overflow-y-auto bg-slate-50 dark:bg-slate-900 flex flex-col gap-3">
        <div 
          v-for="(msg, idx) in simulatorMessages" 
          :key="idx" 
          class="max-w-[85%] rounded-xl px-3 py-2 text-sm shadow-sm"
          :class="msg.role === 'user' ? 'bg-blue-500 text-white self-end rounded-tr-none' : 'bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-100 self-start rounded-tl-none border border-slate-100 dark:border-slate-600'"
        >
          {{ msg.text }}
        </div>
        
        <div v-if="isSimulating" class="text-xs text-slate-400 italic self-start mt-2 animate-pulse">
          Нейросеть печатает...
        </div>
      </div>

      <!-- Поле ввода -->
      <div class="p-3 border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 flex gap-2">
        <input 
          v-model="simulatorInput" 
          @keyup.enter="sendSimulatorMessage"
          type="text" 
          placeholder="Напишите сообщение..." 
          class="flex-1 px-3 py-2 bg-slate-100 dark:bg-slate-900 border-none rounded-lg text-sm outline-none focus:ring-2 focus:ring-indigo-500 dark:text-white"
          :disabled="isSimulating"
        />
        <button 
          @click="sendSimulatorMessage"
          :disabled="isSimulating || !simulatorInput.trim()"
          class="bg-indigo-500 hover:bg-indigo-600 disabled:opacity-50 text-white p-2 rounded-lg transition-colors"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
        </button>
      </div>
    </div>
</template>

<style scoped>
.active-node-highlight {
  outline: 2px solid #3b82f6 !important;
  box-shadow: 0 0 15px rgba(59, 130, 246, 0.5) !important;
  border-radius: 8px;
}
.animate-fade-in {
  animation: fadeIn 0.15s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
</style>