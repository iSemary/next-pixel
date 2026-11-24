<script setup lang="ts">
import type { HTMLAttributes, Ref } from "vue"
import { defaultDocument, useEventListener, useMediaQuery, useVModel } from "@vueuse/core"
import { TooltipProvider } from "reka-ui"
import { computed, ref } from "vue"
import { cn } from "@/lib/utils"
import { provideSidebarContext, SIDEBAR_COOKIE_MAX_AGE, SIDEBAR_COOKIE_NAME, SIDEBAR_DEFAULT_WIDTH, SIDEBAR_KEYBOARD_SHORTCUT, SIDEBAR_MAX_WIDTH, SIDEBAR_MIN_WIDTH, SIDEBAR_WIDTH_ICON } from "./utils"

const props = withDefaults(defineProps<{
  defaultOpen?: boolean
  open?: boolean
  class?: HTMLAttributes["class"]
}>(), {
  defaultOpen: !defaultDocument?.cookie.includes(`${SIDEBAR_COOKIE_NAME}=false`),
  open: undefined,
})

const emits = defineEmits<{
  "update:open": [open: boolean]
}>()

const isMobile = useMediaQuery("(max-width: 768px)")
const openMobile = ref(false)

const open = useVModel(props, "open", emits, {
  defaultValue: props.defaultOpen ?? false,
  passive: (props.open === undefined) as false,
}) as Ref<boolean>

const sidebarWidth = ref<number>(SIDEBAR_DEFAULT_WIDTH)
const isResizing = ref<boolean>(false)

if (typeof window !== "undefined") {
  const savedWidth = localStorage.getItem("sidebar_width")
  if (savedWidth) {
    const parsed = parseInt(savedWidth, 10)
    if (!isNaN(parsed) && parsed >= SIDEBAR_MIN_WIDTH && parsed <= SIDEBAR_MAX_WIDTH) {
      sidebarWidth.value = parsed
    }
  }
}

function setSidebarWidth(value: number) {
  const clamped = Math.min(Math.max(value, SIDEBAR_MIN_WIDTH), SIDEBAR_MAX_WIDTH)
  sidebarWidth.value = clamped
  if (typeof window !== "undefined") {
    localStorage.setItem("sidebar_width", clamped.toString())
    document.cookie = `sidebar_width=${clamped}; path=/; max-age=${SIDEBAR_COOKIE_MAX_AGE}`
  }
}

function setOpen(value: boolean) {
  open.value = value // emits('update:open', value)

  // This sets the cookie to keep the sidebar state.
  document.cookie = `${SIDEBAR_COOKIE_NAME}=${open.value}; path=/; max-age=${SIDEBAR_COOKIE_MAX_AGE}`
}

function setOpenMobile(value: boolean) {
  openMobile.value = value
}

// Helper to toggle the sidebar.
function toggleSidebar() {
  return isMobile.value ? setOpenMobile(!openMobile.value) : setOpen(!open.value)
}

useEventListener("keydown", (event: KeyboardEvent) => {
  if (event.key === SIDEBAR_KEYBOARD_SHORTCUT && (event.metaKey || event.ctrlKey)) {
    event.preventDefault()
    toggleSidebar()
  }
})

// We add a state so that we can do data-state="expanded" or "collapsed".
// This makes it easier to style the sidebar with Tailwind classes.
const state = computed(() => open.value ? "expanded" : "collapsed")

provideSidebarContext({
  state,
  open,
  setOpen,
  isMobile,
  openMobile,
  setOpenMobile,
  toggleSidebar,
  sidebarWidth,
  setSidebarWidth,
  isResizing,
})
</script>

<template>
  <TooltipProvider :delay-duration="0">
    <div
      data-slot="sidebar-wrapper"
      :style="{
        '--sidebar-width': `${sidebarWidth}px`,
        '--sidebar-width-icon': SIDEBAR_WIDTH_ICON,
      }"
      :class="cn(
        'group/sidebar-wrapper has-data-[variant=inset]:bg-sidebar flex min-h-svh w-full',
        isResizing && 'select-none [&_*]:cursor-col-resize! [&_[data-slot=sidebar]]:transition-none! [&_[data-slot=sidebar]_*]:transition-none!',
        props.class
      )"
      v-bind="$attrs"
    >
      <slot />
    </div>
  </TooltipProvider>
</template>
