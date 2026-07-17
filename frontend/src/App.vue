<script setup>
import { ref } from 'vue'
import { VueFlow } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import FunnelNode from './components/FunnelNode.vue'

// Данные, приближенные к нашему бэкенду
const nodes = ref([
  {
    id: '1',
    type: 'funnelNode', // Указываем наш кастомный тип
    position: { x: 100, y: 200 },
    data: { 
      label: 'Start (Приветствие)', 
      description: 'Привет! Расскажи немного о проекте...',
      useAi: false,
      handles: [{ id: 'default', label: 'Далее' }]
    },
  },
  {
    id: '2',
    type: 'funnelNode',
    position: { x: 500, y: 200 },
    data: { 
      label: 'AI Qualification', 
      description: 'AI генерирует ответ и извлекает нишу и бюджет.',
      useAi: true,
      handles: [
        { id: 'ready', label: 'Клиент готов' },
        { id: 'not_ready', label: 'Не готов' }
      ]
    },
  }
])

const edges = ref([
  {
    id: 'e1-2',
    source: '1',
    target: '2',
    sourceHandle: 'default', // Привязываемся к конкретному выходу
    animated: true,
    style: { stroke: '#3b82f6', strokeWidth: 2 } // Синий цвет связи
  }
])
</script>

<template>
  <div class="w-screen h-screen bg-slate-50 dark:bg-slate-900">
    <VueFlow :nodes="nodes" :edges="edges" :fit-view-on-init="true">
      
      <!-- Регистрируем наш кастомный компонент узла -->
      <template #node-funnelNode="props">
        <FunnelNode :id="props.id" :data="props.data" />
      </template>
      
      <Background :pattern-color="'#cbd5e1'" class="dark:pattern-color-slate-700" />
      <Controls class="absolute bottom-6 right-6 shadow-lg rounded-xl overflow-hidden" />
      
      <button 
        class="absolute bottom-6 left-6 w-14 h-14 bg-blue-500 hover:bg-blue-600 text-white rounded-full shadow-lg flex items-center justify-center text-3xl transition-transform hover:scale-105 z-10"
        title="Добавить узел"
      >
        +
      </button>

    </VueFlow>
  </div>
</template>