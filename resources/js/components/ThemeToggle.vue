<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';

const { resolvedAppearance, updateAppearance } = useAppearance();

const toggleTheme = () => {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
};
</script>

<template>
    <Button
        variant="ghost"
        size="icon"
        class="h-9 w-9 cursor-pointer rounded-md border border-border/70 bg-secondary/60 text-foreground transition-colors hover:bg-secondary hover:text-foreground dark:border-[#404040]/60 dark:bg-[#242424]/60 dark:hover:bg-[#2B2B2B]"
        :title="
            resolvedAppearance === 'dark'
                ? 'Switch to light mode'
                : 'Switch to dark mode'
        "
        :aria-label="
            resolvedAppearance === 'dark'
                ? 'Switch to light mode'
                : 'Switch to dark mode'
        "
        @click="toggleTheme"
    >
        <Sun
            v-if="resolvedAppearance === 'dark'"
            class="h-4 w-4 text-[#A3E600] transition-all"
        />
        <Moon v-else class="h-4 w-4 text-[#3F6212] transition-all" />
        <span class="sr-only">Toggle theme</span>
    </Button>
</template>
