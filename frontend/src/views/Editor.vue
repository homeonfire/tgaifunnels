<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { VueFlow, useVueFlow } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import FunnelNode from '../components/FunnelNode.vue'
import SettingsSidebar from '../components/SettingsSidebar.vue'

const router = useRouter()
// Достаем функцию project для перевода координат мыши в координаты холста
const { project } = useVueFlow() 

// Состояние холста и интерфейса
const selectedNode = ref(null)
const isDark = ref(false)
const funnelName = ref('Lead Qualification')
const isActive = ref(true)

// Состояние контекстного меню (ПКМ)
const contextMenu = ref({
  show: false,
  x: 0,
  y: 0,
  canvasX: 0,
  canvasY: 0
})

// Моки данных
const nodes = ref([
  {
    id: '1',
    type: 'funnelNode',
    position: { x: 100, y: 200 },
    data: { 
      label: 'Start (Приветствие)', 
      description: 'Привет! Расскажи немного о проекте...',
      useAi: false,
      aiPrompt: '',
      handles: [{ id: 'default', label: 'Далее' }]
    },
  }
])
const edges = ref([])

// Скрытие меню при любом левом клике
const hideContextMenu = () => {
  contextMenu.value.show = false
}

const onNodeClick = (event) => {
  hideContextMenu()
  selectedNode.value = event.node
}

const onPaneClick = () => {
  hideContextMenu()
  selectedNode.value = null
}

// Обработка правого клика по холсту
const onPaneContextMenu = (event) => {
  event.preventDefault() // Блокируем стандартное меню браузера
  
  // Вычисляем координаты для холста (вычитаем 56px - высоту хедера)
  const projectedPosition = project({ 
    x: event.clientX, 
    y: event.clientY - 56 
  })

  // Показываем наше меню ровно под курсором
  contextMenu.value = {
    show: true,
    x: event.clientX,
    y: event.clientY,
    canvasX: projectedPosition.x,
    canvasY: projectedPosition.y
  }
}

// Функция добавления узла из контекстного меню (по координатам мыши)
const addNodeFromMenu = () => {
  const newNodeId = (nodes.value.length + 1).toString()
  
  nodes.value.push({
    id: newNodeId,
    type: 'funnelNode',
    position: { x: contextMenu.value.canvasX, y: contextMenu.value.canvasY },
    data: {
      label: 'Новый этап',
      description: 'Что делает этот этап?',
      useAi: false,
      aiPrompt: '',
      handles: [{ id: 'default', label: 'Далее' }]
    }
  })
  
  hideContextMenu()
}

// Классическая кнопка + (добавляет по центру со сдвигом)
const addNewNode = () => {
  const newNodeId = (nodes.value.length + 1).toString()
  nodes.value.push({
    id: newNodeId,
    type: 'funnelNode',
    position: { x: 350 + (nodes.value.length * 20), y: 200 + (nodes.value.length * 20) },
    data: {
      label: 'Новый шаг',
      description: 'Описание шага...',
      useAi: false,
      aiPrompt: '',
      handles: [{ id: 'default', label: 'Далее' }]
    }
  })
}

const toggleTheme = () => {
  isDark.value = !isDark.value
  if (isDark.value) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
}

const goBack = () => router.push('/')

onMounted(() => {
  if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    isDark.value = true
    document.documentElement.classList.add('dark')
  }
})
</script>

<template>
  <!-- Добавляем @click="hideContextMenu" на главный контейнер, чтобы клик по хедеру тоже закрывал меню -->
  <div @click="hideContextMenu" class="w-screen h-screen bg-slate-50 dark:bg-slate-900 overflow-hidden relative flex">
    
    <!-- ПЛАВАЮЩИЙ HEADER -->
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
        <button @click.stop="toggleTheme" class="p-2 text-slate-500 hover:text-amber-500 dark:hover:text-blue-400 transition-colors">
          <svg v-if="!isDark" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
          </svg>
        </button>

        <button class="px-4 py-1.5 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 rounded-lg transition-colors flex items-center">
          <svg class="w-4 h-4 mr-1.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
          </svg>
          Тестировать
        </button>
        
        <button class="px-4 py-1.5 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-lg shadow-sm transition-colors">
          Сохранить схему
        </button>
      </div>
    </header>

    <!-- ГЛАВНЫЙ КОНТЕЙНЕР ХОЛСТА -->
    <div class="h-full transition-all duration-300 pt-14" :class="selectedNode ? 'w-[calc(100%-400px)]' : 'w-full'">
      <!-- Добавлен слушатель @pane-context-menu -->
      <VueFlow 
        :nodes="nodes" 
        :edges="edges" 
        :fit-view-on-init="true"
        @node-click="onNodeClick"
        @pane-click="onPaneClick"
        @pane-context-menu="onPaneContextMenu"
      >
        <template #node-funnelNode="props">
          <div :class="{ 'ring-2 ring-blue-500 shadow-lg rounded-lg': selectedNode?.id === props.id }">
            <FunnelNode :id="props.id" :data="props.data" />
          </div>
        </template>
        
        <Background :pattern-color="isDark ? '#334155' : '#cbd5e1'" />
        
        <!-- Контролы масштаба жестко привязаны к правому нижнему углу через параметр position -->
        <Controls position="bottom-right" class="m-6 shadow-lg rounded-xl overflow-hidden bg-white dark:bg-slate-800 border-none" />
        
        <!-- Кнопка + жестко привязана к левому нижнему углу -->
        <button 
          @click.stop="addNewNode"
          class="absolute bottom-6 left-6 w-14 h-14 bg-blue-500 hover:bg-blue-600 text-white rounded-full shadow-lg flex items-center justify-center text-3xl transition-transform hover:scale-105 z-10"
          title="Добавить узел (в центр)"
        >
          +
        </button>
      </VueFlow>
    </div>

    <!-- ВСПЛЫВАЮЩЕЕ МЕНЮ (ПКМ) -->
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

    <!-- БОКОВАЯ ПАНЕЛЬ -->
    <SettingsSidebar 
      :selected-node="selectedNode" 
      @close="selectedNode = null" 
    />

  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.15s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
</style>