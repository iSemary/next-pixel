<script setup lang="ts">
import { ChevronDown, ChevronUp, Sparkles, Terminal } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';

const props = withDefaults(
    defineProps<{
        modelValue: string;
        testingKeywords?: string[];
        maxCharacters?: number;
    }>(),
    {
        modelValue: '',
        testingKeywords: () => [],
        maxCharacters: 4000,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const isKeywordsOpen = ref(false);

const characterCount = computed(() => props.modelValue.length);
const isOverLimit = computed(() => characterCount.value > props.maxCharacters);

// Pixelforge documentation: ~4 characters per text token
const liveTokenCount = computed(() => {
    const trimmed = props.modelValue.trim();
    if (!trimmed) {
        return 0;
    }
    return Math.ceil(trimmed.length / 4);
});

const onInput = (event: Event) => {
    const target = event.target as HTMLTextAreaElement;
    emit('update:modelValue', target.value);
};

const insertKeyword = (keyword: string) => {
    if (props.modelValue.includes(keyword)) {
        return;
    }
    const separator = props.modelValue.trim() === '' ? '' : ' ';
    emit(
        'update:modelValue',
        `${props.modelValue.trimEnd()}${separator}${keyword}`,
    );
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <Label
                for="prompt-input"
                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Prompt
            </Label>
            <div class="flex items-center gap-2">
                <Badge
                    variant="outline"
                    class="h-5 border-border/80 bg-background/50 px-1.5 text-[11px] font-medium text-muted-foreground"
                    title="Estimated live text tokens (~4 characters per token)"
                >
                    <Sparkles class="mr-1 h-3 w-3 text-primary" />
                    ~{{ liveTokenCount }} tokens
                </Badge>
                <span
                    class="font-mono text-[11px] tabular-nums"
                    :class="
                        isOverLimit
                            ? 'font-semibold text-destructive'
                            : 'text-muted-foreground'
                    "
                >
                    {{ characterCount }}/{{ maxCharacters }}
                </span>
            </div>
        </div>

        <div class="relative">
            <textarea
                id="prompt-input"
                :value="modelValue"
                rows="4"
                maxlength="4000"
                class="min-h-[110px] w-full resize-y rounded-xl border border-input/80 bg-background/60 px-4 py-3 text-sm leading-relaxed text-foreground shadow-2xs transition-all placeholder:text-muted-foreground/60 focus:border-primary focus:ring-2 focus:ring-primary/25 focus:outline-none dark:bg-card/70"
                placeholder="A vibrant cinematic shot of a futuristic metropolis with flying vehicles at dusk, hyperrealistic, 8k resolution..."
                @input="onInput"
            />
        </div>

        <!-- Development-only testing keywords (Collapsible) -->
        <div
            v-if="testingKeywords && testingKeywords.length > 0"
            class="overflow-hidden rounded-lg border border-border/70 bg-muted/20 text-xs transition-colors"
        >
            <button
                type="button"
                class="flex w-full cursor-pointer items-center justify-between px-3 py-1.5 text-left font-medium text-muted-foreground transition-colors select-none hover:text-foreground"
                @click="isKeywordsOpen = !isKeywordsOpen"
            >
                <div class="flex items-center gap-1.5 text-[11px]">
                    <Terminal class="h-3.5 w-3.5 text-primary" />
                    <span>Dev test keywords</span>
                    <span
                        class="rounded bg-muted/80 px-1.5 py-0.5 font-mono text-[10px] text-muted-foreground"
                    >
                        {{ testingKeywords.length }}
                    </span>
                </div>
                <component
                    :is="isKeywordsOpen ? ChevronUp : ChevronDown"
                    class="h-3.5 w-3.5 text-muted-foreground transition-transform"
                />
            </button>

            <div
                v-if="isKeywordsOpen"
                class="border-t border-border/50 bg-background/40 p-2.5"
            >
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="kw in testingKeywords"
                        :key="kw"
                        type="button"
                        class="inline-flex cursor-pointer items-center rounded-md border border-border/60 bg-secondary/70 px-2 py-0.5 font-mono text-[10px] text-secondary-foreground transition-all hover:border-primary/50 hover:bg-primary hover:text-primary-foreground focus:ring-1 focus:ring-primary focus:outline-none"
                        :class="{
                            'border-primary/60 bg-primary/15 font-semibold text-primary ring-1 ring-primary/40':
                                modelValue.includes(kw),
                        }"
                        :title="`Click to append ${kw} to prompt`"
                        @click="insertKeyword(kw)"
                    >
                        {{ kw }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
