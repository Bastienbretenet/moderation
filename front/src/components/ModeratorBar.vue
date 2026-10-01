<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api } from '@/api/client'
import type { ViewMode } from '@/viewMode'

const viewMode = defineModel<ViewMode>({ required: true })

const isApiReachable = ref<boolean | null>(null)

const viewModeOptions: { value: ViewMode; label: string }[] = [
  { value: 'moderator', label: 'Modérateur' },
  { value: 'reader', label: 'Lecteur' },
]

onMounted(async () => {
  try {
    await api.health()
    isApiReachable.value = true
  } catch {
    isApiReachable.value = false
  }
})
</script>

<template>
  <header class="sticky top-0 z-10 bg-ink text-paper shadow-[0_1px_0_rgb(0_0_0/0.2)]">
    <div class="mx-auto flex max-w-2xl flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-2.5">
      <div class="flex items-center gap-3">
        <span class="text-base font-extrabold" style="font-stretch: 125%">Espace modération</span>
        <span class="flex items-center gap-1.5 text-xs text-paper/70" role="status">
          <span
            class="inline-block size-2 rounded-full"
            :class="{
              'bg-paper/40': isApiReachable === null,
              'bg-[#7fc49b]': isApiReachable === true,
              'bg-[#f08a8d]': isApiReachable === false,
            }"
            aria-hidden="true"
          ></span>
          <template v-if="isApiReachable === null">Connexion…</template>
          <template v-else-if="isApiReachable">API connectée</template>
          <template v-else>API injoignable</template>
        </span>
      </div>

      <div class="flex items-center gap-4">
        <fieldset class="flex rounded-full bg-paper/10 p-0.5 text-xs">
          <legend class="sr-only">Afficher la page en tant que</legend>
          <label
            v-for="option in viewModeOptions"
            :key="option.value"
            class="cursor-pointer rounded-full px-3 py-1 transition-colors has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-paper"
            :class="viewMode === option.value ? 'bg-paper font-semibold text-ink' : 'text-paper/80 hover:text-paper'"
          >
            <input v-model="viewMode" type="radio" name="view-mode" :value="option.value" class="sr-only" />
            {{ option.label }}
          </label>
        </fieldset>

        <div class="flex items-center gap-2">
          <div class="text-right leading-tight">
            <p class="text-sm">John Doe</p>
            <p class="text-xs text-paper/70">Modérateur</p>
          </div>
          <div class="grid size-8 place-items-center rounded-full bg-paper text-xs font-bold text-ink" aria-hidden="true">
            JD
          </div>
        </div>
      </div>
    </div>
  </header>
</template>
