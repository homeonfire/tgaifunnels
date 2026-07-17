<script setup>
import { ref } from 'vue'
import { VueFlow } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'

// Временно создаем статические данные, которые повторяют наш сидер из БД.
// Позже мы заменим это на получение данных из Laravel API.
const nodes = ref([
  {
    id: '1',
    type: 'input',
    label: 'Start (Приветствие)',
    position: { x: 100, y: 200 },
  },
  {
    id: '2',
    label: 'AI Qualification',
    position: { x: 500, y: 200 },
  }
])

const edges = ref([
  {
    id: 'e1-2',
    source: '1',
    target: '2',
    animated: true, // Плавная анимация пунктирной линии ("марширующие муравьи")
    style: { stroke: '#94a3b8', strokeWidth: 2 } // Серый цвет в спокойном состоянии
  }
])
</script>

<template>
  <!-- Контейнер занимает 100vw и 100vh -->
  <div class="w-screen h-screen bg-slate-50 dark:bg-slate-900">
    <VueFlow :nodes="nodes" :edges="edges" :fit-view-on-init="true">
      
      <!-- Паттерн точек, меняющий цвет в зависимости от темы -->
      <Background 
        :pattern-color="'#cbd5e1'" 
        class="dark:pattern-color-slate-700"
      />

      <!-- Контролы зума в правом нижнем углу -->
      <Controls class="absolute bottom-6 right-6 shadow-lg rounded-xl overflow-hidden" />

      <!-- Плавающая кнопка добавления узла слева снизу -->
      <button 
        class="absolute bottom-6 left-6 w-14 h-14 bg-blue-500 hover:bg-blue-600 text-white rounded-full shadow-lg flex items-center justify-center text-3xl transition-transform hover:scale-105 z-10"
        title="Добавить узел"
      >
        +
      </button>

    </VueFlow>
  </div>
</template>

<style>
/* Немного подправим стандартные узлы, чтобы они были похожи на светлую тему из ТЗ */
.vue-flow__node {
  @apply bg-white border border-slate-200 text-slate-800 shadow-sm rounded-lg p-4;
}
.dark .vue-flow__node {
  @apply bg-slate-800 border-slate-700 text-slate-50;
}
</style>