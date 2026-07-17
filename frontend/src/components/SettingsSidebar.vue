<script setup>
import { computed } from 'vue'

// Принимаем выбранный узел и функцию закрытия
const props = defineProps({
  selectedNode: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close'])

// Создаем удобный доступ к данным узла (чтобы сразу менять их на холсте)
const nodeData = computed(() => props.selectedNode?.data || {})
</script>

<template>
  <!-- Панель выезжает справа. Добавлена анимация transform -->
  <div 
    class="fixed top-0 right-0 h-screen w-[400px] bg-white dark:bg-slate-800 shadow-2xl border-l border-slate-200 dark:border-slate-700 transition-transform duration-300 z-50 flex flex-col"
    :class="selectedNode ? 'translate-x-0' : 'translate-x-full'"
  >
    <!-- Шапка панели -->
    <div class="flex items-center justify-between p-4 border-b border-slate-100 dark:border-slate-700">
      <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-50">Настройки этапа</h2>
      <button 
        @click="emit('close')"
        class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
      >
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Тело панели -->
    <div class="flex-1 overflow-y-auto p-4 space-y-6" v-if="selectedNode">
      
      <!-- Поле: Название -->
      <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Название шага</label>
        <input 
          v-model="nodeData.label"
          type="text" 
          class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-white"
        >
      </div>

      <!-- Поле: Текст сообщения (если нет AI) -->
      <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Текст сообщения</label>
        <textarea 
          v-model="nodeData.description"
          rows="3"
          class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-white"
          placeholder="Что бот должен написать?"
        ></textarea>
      </div>

      <hr class="border-slate-100 dark:border-slate-700">

      <!-- Тоггл: Использовать AI -->
      <div class="flex items-center justify-between">
        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Использовать AI (LLM)</label>
        <button 
          @click="nodeData.useAi = !nodeData.useAi"
          class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none"
          :class="nodeData.useAi ? 'bg-violet-500' : 'bg-slate-300 dark:bg-slate-600'"
        >
          <span 
            class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
            :class="nodeData.useAi ? 'translate-x-6' : 'translate-x-1'"
          />
        </button>
      </div>

      <!-- Редактор промпта (показывается только если включен AI) -->
      <div v-if="nodeData.useAi" class="animate-fade-in">
        <label class="block text-sm font-medium text-violet-600 dark:text-violet-400 mb-1">Системный промпт (AI Prompt)</label>
        <textarea 
          v-model="nodeData.aiPrompt"
          rows="6"
          class="w-full px-3 py-2 bg-slate-800 text-slate-50 font-mono text-sm rounded-md focus:outline-none focus:ring-2 focus:ring-violet-500"
          placeholder="Ты квалификатор лидов. Твоя задача..."
        ></textarea>
        <p class="mt-1 text-xs text-slate-500">Используйте моноширинный шрифт для промптов.</p>
      </div>

    </div>
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>