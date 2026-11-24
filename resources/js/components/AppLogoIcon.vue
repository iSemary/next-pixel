<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    class?: HTMLAttributes['class'];
    className?: HTMLAttributes['class'];
    alt?: string;
};

const props = withDefaults(defineProps<Props>(), {
    alt: 'NextPixel Logo',
});
</script>

<template>
    <img
        src="/logo.png"
        srcset="/logo.png 1x, /logo-128.png 2x, /logo-256.png 4x"
        :alt="props.alt"
        :class="[
            'inline-block aspect-square object-contain select-none [image-rendering:crisp-edges] [image-rendering:pixelated]',
            props.class,
            props.className,
        ]"
        v-bind="$attrs"
    />
</template>
