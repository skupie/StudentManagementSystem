@props([
  'label',
  'active' => false,
  'width' => 'w-64',
  'align' => 'left', // left | right
])

@php
  $origin = $align === 'right' ? 'origin-top-right right-0' : 'origin-top-left left-0';

  $btnBase = 'inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-[13px] font-medium transition focus:outline-none focus:ring-2 focus:ring-indigo-500/20';
  $btn = $active
      ? $btnBase.' text-gray-900 bg-gray-50'
      : $btnBase.' text-gray-600 hover:text-gray-900 hover:bg-gray-50';

  $link = 'flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition';
@endphp

<div class="relative" x-data="{ open:false }" @click.away="open=false" @keydown.escape.window="open=false">
  <button type="button" @click="open=!open" class="{{ $btn }}" :aria-expanded="open.toString()">
    <span>{{ $label }}</span>
    <svg class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
      <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08z" clip-rule="evenodd"/>
    </svg>
  </button>

  <div
    x-show="open"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-1 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-120"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 translate-y-1 scale-95"
    class="absolute z-50 mt-3 {{ $width }} {{ $origin }}"
    style="display:none;"
  >
    <div class="rounded-2xl bg-white shadow-xl ring-1 ring-black/5 p-2">
      {{-- Slot content can use $link style by calling it: --}}
      {{ $slot }}

      {{-- Helpers if you want them --}}
      @isset($footer)
        <div class="mt-2 border-t border-gray-100 pt-2">
          {{ $footer }}
        </div>
      @endisset
    </div>
  </div>
</div>
