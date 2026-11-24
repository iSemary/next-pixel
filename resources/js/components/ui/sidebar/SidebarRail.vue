<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { SIDEBAR_DEFAULT_WIDTH, SIDEBAR_MAX_WIDTH, SIDEBAR_MIN_WIDTH, useSidebar } from "./utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

const { isMobile, state, sidebarWidth, setSidebarWidth, isResizing, toggleSidebar } = useSidebar()

let startX = 0
let startWidth = SIDEBAR_DEFAULT_WIDTH
let hasMoved = false

const onMouseDown = (e: MouseEvent) => {
  if (isMobile.value || state.value === "collapsed") {
    return
  }

  e.preventDefault()
  startX = e.clientX
  startWidth = sidebarWidth.value
  hasMoved = false
  isResizing.value = true

  const onMouseMove = (moveEvent: MouseEvent) => {
    const delta = moveEvent.clientX - startX
    if (Math.abs(delta) > 3) {
      hasMoved = true
    }
    const newWidth = Math.min(Math.max(startWidth + delta, SIDEBAR_MIN_WIDTH), SIDEBAR_MAX_WIDTH)
    setSidebarWidth(newWidth)
  }

  const onMouseUp = () => {
    isResizing.value = false
    window.removeEventListener("mousemove", onMouseMove)
    window.removeEventListener("mouseup", onMouseUp)
  }

  window.addEventListener("mousemove", onMouseMove)
  window.addEventListener("mouseup", onMouseUp)
}

const onDoubleClick = () => {
  if (isMobile.value || state.value === "collapsed") {
    return
  }
  setSidebarWidth(SIDEBAR_DEFAULT_WIDTH)
}

const onClick = () => {
  if (!hasMoved) {
    toggleSidebar()
  }
}
</script>

<template>
  <button
    data-sidebar="rail"
    data-slot="sidebar-rail"
    aria-label="Resize or toggle sidebar"
    :tabindex="-1"
    title="Drag to resize, double-click to reset, or click to toggle"
    :class="cn(
      'hover:after:bg-primary/60 active:after:bg-primary absolute inset-y-0 z-20 hidden w-4 -translate-x-1/2 transition-all ease-linear group-data-[side=left]:-right-4 group-data-[side=right]:left-0 after:absolute after:inset-y-0 after:left-1/2 after:w-[2px] after:rounded-full after:transition-all sm:flex',
      state === 'expanded' ? 'cursor-col-resize hover:after:w-[4px] hover:after:bg-primary/70' : 'cursor-e-resize',
      props.class,
    )"
    @mousedown="onMouseDown"
    @dblclick="onDoubleClick"
    @click="onClick"
  >
    <slot />
  </button>
</template>
