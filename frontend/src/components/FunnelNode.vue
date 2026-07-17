<script setup>
import { Handle, Position } from '@vue-flow/core'

const props = defineProps({
  id: { type: String, required: true },
  data: { type: Object, required: true }
})
</script>

<template>
  <div 
    class="bg-white dark:bg-slate-800 rounded-xl w-64 flex flex-col border transition-colors shadow-sm"
    :class="data.useAi ? 'border-violet-200 dark:border-violet-800/50' : 'border-slate-200 dark:border-slate-700'"
  >
    <!-- Единственный ВХОД слева по центру -->
    <Handle 
      type="target" 
      :position="Position.Left" 
      id="input" 
      class="w-3 h-3 bg-slate-300 dark:bg-slate-600 border-2 border-white dark:border-slate-800 !left-[-6px] !top-1/2 z-10" 
    />

    <!-- Шапка карточки -->
    <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700/50 flex items-center gap-3">
      <!-- Иконка зависит от того, включен ли AI -->
      <div 
        class="p-2 rounded-lg flex-shrink-0"
        :class="data.useAi ? 'bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400' : 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'"
      >
        <svg v-if="data.useAi" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
        <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
        </svg>
      </div>
      <div class="font-semibold text-sm text-slate-800 dark:text-white truncate" :title="data.label">
        {{ data.label }}
      </div>
    </div>

    <!-- Тело (Описание или Промпт) -->
    <div class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
      {{ data.useAi ? (data.aiPrompt || 'AI промпт не задан...') : (data.description || 'Текст сообщения пуст...') }}
    </div>

    <!-- Динамические ВЫХОДЫ (Handles) -->
    <div v-if="data.handles && data.handles.length > 0" class="border-t border-slate-100 dark:border-slate-700/50 bg-slate-50 dark:bg-slate-900/30 rounded-b-xl flex flex-col">
      <!-- Каждое условие рендерится как отдельная строка со своим Handle -->
      <div 
        v-for="handle in data.handles" 
        :key="handle.id" 
        class="relative px-4 py-2 flex items-center justify-end border-b border-slate-100 dark:border-slate-700/50 last:border-0"
      >
        <span class="text-[11px] font-medium text-slate-600 dark:text-slate-400 mr-1">{{ handle.label }}</span>
        
        <Handle 
          type="source" 
          :position="Position.Right" 
          :id="handle.id" 
          class="w-3 h-3 border-2 border-white dark:border-slate-800 !right-[-6px] !top-1/2 z-10"
          :class="data.useAi ? 'bg-violet-500' : 'bg-blue-500'"
        />
      </div>
    </div>
  </div>
</template>