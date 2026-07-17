<script setup>
import { Handle, Position } from '@vue-flow/core'

// Получаем данные, которые передадим из главного графа
defineProps({
  id: { type: String, required: true },
  data: { type: Object, required: true }
})
</script>

<template>
  <!-- Главный контейнер карточки. Цвета из ТЗ для светлой и темной тем -->
  <div class="w-64 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-sm transition-all hover:shadow-md">
    
    <!-- Шапка карточки -->
    <div class="flex items-center p-3 border-b border-slate-100 dark:border-slate-700">
      <!-- Плашка с иконкой (цвет меняем в зависимости от наличия AI) -->
      <div 
        class="w-8 h-8 rounded flex items-center justify-center mr-3 text-white shrink-0"
        :class="data.useAi ? 'bg-violet-500' : 'bg-emerald-500'"
      >
        <svg v-if="data.useAi" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
        <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
        </svg>
      </div>
      <!-- Название с отсечением (ellipsis) -->
      <div class="font-semibold text-slate-800 dark:text-slate-50 truncate flex-1 text-sm">
        {{ data.label }}
      </div>
    </div>

    <!-- Тело карточки: краткое описание -->
    <div class="p-3 text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
      {{ data.description || 'Нет описания' }}
    </div>

    <!-- Единый ВХОД слева по центру всей карточки -->
    <Handle 
      type="target" 
      :position="Position.Left" 
      class="w-3 h-3 bg-slate-400 border-2 border-white dark:border-slate-800" 
    />

    <!-- Блок с ВЫХОДАМИ (Multi-Handles) -->
    <div class="bg-slate-50 dark:bg-slate-900/50 rounded-b-lg border-t border-slate-100 dark:border-slate-700 flex flex-col py-1">
      <div 
        v-for="(handle, index) in data.handles" 
        :key="index"
        class="relative flex justify-end items-center py-1.5 px-3 text-xs text-slate-600 dark:text-slate-300"
      >
        <span>{{ handle.label }}</span>
        <!-- Динамические выходы -->
        <Handle 
          type="source" 
          :id="handle.id" 
          :position="Position.Right" 
          class="w-3 h-3 bg-blue-500 border-2 border-white dark:border-slate-800 !-right-1.5" 
          :style="{ top: '50%' }"
        />
      </div>
    </div>

  </div>
</template>

<style scoped>
/* Убираем дефолтные стили Handle от Vue Flow, чтобы они выглядели аккуратнее */
.vue-flow__handle {
  border-radius: 50%;
}
</style>