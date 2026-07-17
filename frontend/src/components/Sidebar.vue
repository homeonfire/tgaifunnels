<script setup>
// Компонент полностью независим, маршрутизация работает через $route во Vue
</script>

<template>
  <aside class="w-64 bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 p-6 flex-shrink-0 h-screen overflow-y-auto sticky top-0">
    <div class="text-2xl font-bold text-slate-800 dark:text-white mb-8">AI Funnels</div>
    
    <nav class="space-y-2">
      <!-- Главная (Воронки) -->
      <router-link 
        to="/" 
        class="block px-4 py-2 rounded-lg transition-colors"
        :class="$route.path === '/' ? 'bg-blue-50 text-blue-600 dark:bg-slate-700 dark:text-white font-medium' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-700'"
      >
        Воронки
      </router-link>

      <!-- Интеграции -->
      <router-link 
        to="/integrations" 
        class="block px-4 py-2 rounded-lg transition-colors"
        :class="$route.path.startsWith('/integrations') ? 'bg-blue-50 text-blue-600 dark:bg-slate-700 dark:text-white font-medium' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-700'"
      >
        Интеграции
      </router-link>

      <!-- Настройки -->
      <a href="#" class="block px-4 py-2 rounded-lg text-slate-600 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-700 transition-colors">
        Настройки
      </a>
    </nav>
  </aside>
</template>