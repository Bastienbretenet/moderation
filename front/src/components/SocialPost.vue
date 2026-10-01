<script setup lang="ts">
defineProps<{ commentCount: number }>()

const emit = defineEmits<{ comment: [] }>()
</script>

<template>
  <article class="overflow-hidden rounded-xl bg-paper shadow-[0_1px_2px_rgb(22_35_58/0.08)]" aria-labelledby="post-title">
    <header class="flex items-center gap-3 px-4 pt-4">
      <div
        class="grid size-10 place-items-center rounded-full bg-tide text-sm font-bold text-paper"
        style="font-stretch: 125%"
        aria-hidden="true"
      >
        SO
      </div>
      <div class="leading-tight">
        <p class="text-sm font-semibold">Sud Ouest</p>
        <p class="text-xs text-ink-soft">Il y a 2 heures</p>
      </div>
    </header>

    <p class="px-4 pt-3 pb-3 text-[0.9375rem] leading-relaxed">Les huîtres font leur retour sur le Bassin. Vous y croyez ?</p>

    <figure>
      <svg viewBox="0 0 640 200" class="block h-auto w-full" role="img" aria-label="Cabane tchanquée et parcs à huîtres sur le bassin d'Arcachon">
        <defs>
          <linearGradient id="post-sky" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#b9d0dc" />
            <stop offset="1" stop-color="#e9eef0" />
          </linearGradient>
          <linearGradient id="post-water" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#5b8aa5" />
            <stop offset="1" stop-color="#3d6f8c" />
          </linearGradient>
        </defs>
        <rect width="640" height="200" fill="url(#post-sky)" />
        <circle cx="492" cy="54" r="22" fill="#fbfcfc" opacity="0.85" />
        <path
          d="M0 104 L18 96 L30 100 L44 92 L60 99 L78 94 L96 100 L118 93 L138 99 L160 95 L182 101 L640 101 L640 108 L0 108 Z"
          fill="#2e4a3e"
          opacity="0.55"
        />
        <rect y="106" width="640" height="94" fill="url(#post-water)" />
        <path d="M0 162 C120 152 230 170 360 160 C470 152 560 162 640 156 L640 200 L0 200 Z" fill="#e8d9b5" />
        <g fill="#16233a" opacity="0.55">
          <rect x="60" y="138" width="110" height="2" />
          <rect x="70" y="146" width="130" height="2" />
          <rect x="54" y="154" width="120" height="2" />
          <rect x="440" y="140" width="120" height="2" />
          <rect x="452" y="148" width="100" height="2" />
        </g>
        <g fill="#16233a">
          <rect x="272" y="96" width="3" height="40" />
          <rect x="300" y="96" width="3" height="40" />
          <rect x="328" y="96" width="3" height="40" />
          <rect x="264" y="74" width="74" height="24" fill="#f3efe6" stroke="#16233a" stroke-width="2" />
          <rect x="294" y="82" width="12" height="16" />
          <path d="M258 76 L301 52 L344 76 Z" fill="#c2272d" />
        </g>
        <path d="M262 136 L344 136" stroke="#fbfcfc" stroke-width="2" opacity="0.5" />
      </svg>
      <figcaption class="border-b border-rule bg-fog/50 px-4 py-3">
        <p class="text-xs text-ink-soft">sudouest.fr</p>
        <h1 id="post-title" class="mt-0.5 font-text text-xl leading-snug font-semibold">
          Bassin d'Arcachon : une saison record pour les huîtres
        </h1>
      </figcaption>
    </figure>

    <footer class="flex items-center justify-between px-4 py-2 text-sm text-ink-soft">
      <span>214 réactions</span>
      <button type="button" class="rounded-md px-2 py-1 font-medium hover:bg-fog hover:text-ink" @click="emit('comment')">
        Commenter
        <span class="ml-1 text-ink-soft">{{ commentCount }}</span>
      </button>
    </footer>
  </article>
</template>
