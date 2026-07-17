<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  selectedNode: Object
})

const emit = defineEmits(['close', 'save'])

// Доступные операторы для логики
const operators = [
  { value: '==', label: 'Равно (==)' },
  { value: '!=', label: 'Не равно (!=)' },
  { value: '>', label: 'Больше (>)' },
  { value: '<', label: 'Меньше (<)' },
  { value: '>=', label: 'Больше или равно (>=)' },
  { value: '<=', label: 'Меньше или равно (<=)' },
  { value: 'filled', label: 'Заполнено' },
  { value: 'empty', label: 'Не заполнено' }
]

const formData = ref({
  label: '',
  description: '',
  useAi: false,
  aiPrompt: '',
  extractedVariables: [], // <--- НОВЫЙ МАССИВ ДЛЯ ПЕРЕМЕННЫХ
  handles: [] // Ветки теперь будут хранить внутри себя rules (условия)
})

watch(() => props.selectedNode, (newNode) => {
  if (newNode && newNode.data) {
    formData.value = JSON.parse(JSON.stringify(newNode.data))
    if (!formData.value.handles) formData.value.handles = [{ id: 'default', label: 'Далее', rules: [] }]
    if (!formData.value.extractedVariables) formData.value.extractedVariables = []
  }
}, { immediate: true })

// Управление ПЕРЕМЕННЫМИ
const addVariable = () => {
  formData.value.extractedVariables.push({
    id: 'var_' + Math.random().toString(36).substr(2, 6),
    name: '', // например 'budget'
    type: 'string', // string, number, boolean
    description: '' // описание для LLM
  })
}
const removeVariable = (index) => formData.value.extractedVariables.splice(index, 1)

// Управление ВЕТКАМИ И УСЛОВИЯМИ
const addHandle = () => {
  formData.value.handles.push({
    id: 'handle_' + Math.random().toString(36).substr(2, 6),
    label: 'Новое условие',
    rules: []
  })
}
const removeHandle = (index) => formData.value.handles.splice(index, 1)

const addRule = (handleIndex) => {
  if (!formData.value.handles[handleIndex].rules) {
    formData.value.handles[handleIndex].rules = []
  }
  formData.value.handles[handleIndex].rules.push({
    variable: '',
    operator: '==',
    value: ''
  })
}
const removeRule = (handleIndex, ruleIndex) => {
  formData.value.handles[handleIndex].rules.splice(ruleIndex, 1)
}

const saveAndClose = () => emit('save', formData.value)
</script>

<template>
  <div 
    class="absolute top-0 right-0 w-[450px] h-full bg-white/95 dark:bg-slate-800/95 backdrop-blur-md border-l border-slate-200 dark:border-slate-700 shadow-2xl z-40 transform transition-transform duration-300 flex flex-col pt-14"
    :class="selectedNode ? 'translate-x-0' : 'translate-x-full'"
  >
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/80">
      <h2 class="text-lg font-semibold text-slate-800 dark:text-white">Настройки этапа</h2>
      <button @click="emit('close')" class="text-slate-400 hover:text-slate-600 transition-colors">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
      </button>
    </div>

    <div class="flex-1 overflow-y-auto p-5 space-y-6" v-if="selectedNode">
      
      <!-- Основные настройки -->
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Название этапа</label>
          <input v-model="formData.label" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <label class="flex items-center cursor-pointer p-3 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
          <div class="relative">
            <input type="checkbox" v-model="formData.useAi" class="sr-only">
            <div class="block bg-slate-200 dark:bg-slate-700 w-10 h-6 rounded-full transition-colors" :class="{'!bg-violet-500': formData.useAi}"></div>
            <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform" :class="{'transform translate-x-4': formData.useAi}"></div>
          </div>
          <div class="ml-3 text-sm font-medium text-slate-700 dark:text-slate-300">Использовать AI (LLM)</div>
        </label>

        <div v-if="!formData.useAi">
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Текст сообщения</label>
          <textarea v-model="formData.description" rows="3" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
        </div>

        <div v-else>
          <label class="block text-sm font-medium text-violet-600 dark:text-violet-400 mb-1">Системный промпт (Prompt)</label>
          <textarea v-model="formData.aiPrompt" rows="4" placeholder="Ты менеджер по продажам..." class="w-full px-3 py-2 bg-violet-50 dark:bg-slate-900 border border-violet-200 dark:border-slate-700 rounded-lg outline-none focus:ring-2 focus:ring-violet-500 resize-none font-mono text-sm"></textarea>
        </div>
      </div>

      <!-- БЛОК 1: СБОР ДАННЫХ (Только если включен AI) -->
      <div v-if="formData.useAi" class="pt-4 border-t border-slate-100 dark:border-slate-700">
        <div class="flex justify-between items-center mb-3">
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Сохраняемые переменные (JSON Schema)</label>
          <button @click="addVariable" class="p-1 text-emerald-500 hover:bg-emerald-50 dark:hover:bg-slate-700 rounded transition-colors"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg></button>
        </div>
        
        <div class="space-y-3">
          <div v-for="(variable, index) in formData.extractedVariables" :key="variable.id" class="p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg group relative">
            <button @click="removeVariable(index)" class="absolute -top-2 -right-2 w-6 h-6 bg-red-100 text-red-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
            <div class="flex gap-2 mb-2">
              <input v-model="variable.name" placeholder="Ключ (e.g. age)" class="w-1/2 px-2 py-1 text-xs font-mono bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded outline-none focus:border-violet-500" />
              <select v-model="variable.type" class="w-1/2 px-2 py-1 text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded outline-none appearance-none">
                <option value="string">Текст (String)</option>
                <option value="number">Число (Number)</option>
                <option value="boolean">Логика (Boolean)</option>
              </select>
            </div>
            <input v-model="variable.description" placeholder="Описание для нейросети (e.g. Возраст клиента)" class="w-full px-2 py-1 text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded outline-none focus:border-violet-500" />
          </div>
          <div v-if="formData.extractedVariables.length === 0" class="text-xs text-slate-500 italic text-center py-2">Переменные не заданы. AI будет просто отвечать текстом.</div>
        </div>
      </div>

      <!-- БЛОК 2: ВЕТКИ И УСЛОВИЯ ПЕРЕХОДОВ -->
      <div class="pt-4 border-t border-slate-100 dark:border-slate-700">
        <div class="flex justify-between items-center mb-3">
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Логика переходов (Ветки)</label>
          <button @click="addHandle" class="p-1 text-blue-500 hover:bg-blue-50 dark:hover:bg-slate-700 rounded transition-colors"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg></button>
        </div>

        <div class="space-y-4">
          <div v-for="(handle, hIndex) in formData.handles" :key="handle.id" class="p-3 border border-slate-200 dark:border-slate-700 rounded-lg relative bg-white dark:bg-slate-800">
            <div class="flex items-center gap-2 mb-3">
              <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
              <input v-model="handle.label" class="flex-1 px-2 py-1 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded outline-none focus:border-blue-500" placeholder="Название выхода..." />
              <button @click="removeHandle(hIndex)" class="p-1 text-slate-400 hover:text-red-500 transition-colors"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
            </div>

            <!-- Условия внутри ветки -->
            <div class="pl-4 space-y-2 border-l-2 border-slate-100 dark:border-slate-700">
              <div v-for="(rule, rIndex) in handle.rules" :key="rIndex" class="flex items-center gap-2">
                <input v-model="rule.variable" placeholder="Переменная" class="w-1/3 px-2 py-1 text-xs font-mono bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded outline-none" />
                <select v-model="rule.operator" class="w-1/3 px-2 py-1 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded outline-none appearance-none">
                  <option v-for="op in operators" :key="op.value" :value="op.value">{{ op.label }}</option>
                </select>
                <input v-if="!['filled', 'empty'].includes(rule.operator)" v-model="rule.value" placeholder="Значение" class="w-1/3 px-2 py-1 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded outline-none" />
                <button @click="removeRule(hIndex, rIndex)" class="text-slate-400 hover:text-red-500"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
              </div>
              <button @click="addRule(hIndex)" class="text-xs text-blue-500 hover:text-blue-600 font-medium flex items-center mt-1">
                <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg> Добавить условие
              </button>
            </div>
            
            <div v-if="!handle.rules || handle.rules.length === 0" class="pl-4 mt-2 text-[10px] text-slate-400 uppercase tracking-wider font-semibold">
              Сработает по умолчанию (Fallback)
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Подвал панели -->
    <div class="p-5 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 mt-auto">
      <button @click="saveAndClose" class="w-full px-4 py-2.5 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-lg shadow-sm transition-colors flex justify-center items-center">
        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
        Сохранить настройки
      </button>
    </div>
  </div>
</template>