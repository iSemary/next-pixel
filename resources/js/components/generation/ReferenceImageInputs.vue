<script setup lang="ts">
import {
    Image as ImageIcon,
    Link2,
    Loader2,
    Plus,
    UploadCloud,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { postFormData } from '@/lib/http';
import { store as uploadReferenceImageRoute } from '@/routes/generations/reference-images';

const props = withDefaults(
    defineProps<{
        modelValue: string[];
        maxImages?: number;
    }>(),
    {
        modelValue: () => [],
        maxImages: 4,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string[]): void;
}>();

const fileInputRef = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const uploadingCount = ref(0);
const showUrlInput = ref(false);
const urlDraft = ref('');

const remainingSlots = computed(() =>
    Math.max(
        0,
        props.maxImages - props.modelValue.length - uploadingCount.value,
    ),
);

const canAddMore = computed(
    () => props.modelValue.length + uploadingCount.value < props.maxImages,
);

const isValidHttpUrl = (value: string): boolean => {
    try {
        const parsed = new URL(value.trim());
        return ['http:', 'https:'].includes(parsed.protocol);
    } catch {
        return false;
    }
};

function openFilePicker(): void {
    if (canAddMore.value) {
        fileInputRef.value?.click();
    }
}

async function uploadFiles(files: FileList | File[]): Promise<void> {
    const fileArray = Array.from(files).slice(0, remainingSlots.value);
    if (fileArray.length === 0) {
        return;
    }

    const currentUrls = [...props.modelValue];

    for (const file of fileArray) {
        if (!file.type.startsWith('image/')) {
            toast.error(`"${file.name}" is not a supported image file.`);
            continue;
        }

        uploadingCount.value++;

        try {
            const formData = new FormData();
            formData.append('image', file);

            const res = await postFormData<{ url: string }>(
                uploadReferenceImageRoute.url(),
                formData,
                { defaultErrorMessage: `Failed to upload ${file.name}` },
            );

            if (res.url && currentUrls.length < props.maxImages) {
                currentUrls.push(res.url);
                emit('update:modelValue', [...currentUrls]);
            }
        } catch (err: unknown) {
            const msg =
                err instanceof Error ? err.message : 'Image upload failed.';
            toast.error(msg);
        } finally {
            uploadingCount.value = Math.max(0, uploadingCount.value - 1);
        }
    }
}

function handleFileSelect(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
        void uploadFiles(input.files);
    }
    input.value = '';
}

function handleDrop(event: DragEvent): void {
    isDragging.value = false;
    if (event.dataTransfer?.files && event.dataTransfer.files.length > 0) {
        void uploadFiles(event.dataTransfer.files);
    }
}

function addManualUrl(): void {
    const trimmed = urlDraft.value.trim();
    if (!trimmed) {
        return;
    }

    if (!isValidHttpUrl(trimmed)) {
        toast.error('Please enter a valid HTTP or HTTPS image URL.');
        return;
    }

    if (props.modelValue.length >= props.maxImages) {
        return;
    }

    emit('update:modelValue', [...props.modelValue, trimmed]);
    urlDraft.value = '';
    showUrlInput.value = false;
}

function removeImage(index: number): void {
    emit(
        'update:modelValue',
        props.modelValue.filter((_, i) => i !== index),
    );
}
</script>

<template>
    <div class="space-y-2.5">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-1.5">
                <Label
                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Reference Images
                </Label>
                <span class="text-[11px] text-muted-foreground/80"
                    >(Optional)</span
                >
            </div>
            <span class="font-mono text-[11px] text-muted-foreground">
                {{ modelValue.length }}/{{ maxImages }}
            </span>
        </div>

        <p class="text-xs text-muted-foreground">
            Upload up to 4 images or paste URLs to guide composition (+765
            tokens each).
        </p>

        <!-- Uploaded / Linked Image Previews -->
        <div
            v-if="modelValue.length > 0 || uploadingCount > 0"
            class="grid grid-cols-4 gap-2"
        >
            <div
                v-for="(url, index) in modelValue"
                :key="`${url}-${index}`"
                class="group relative aspect-square overflow-hidden rounded-lg border border-border/80 bg-muted/40"
            >
                <img
                    :src="url"
                    alt="Reference image preview"
                    class="h-full w-full object-cover"
                />
                <button
                    type="button"
                    class="absolute top-1 right-1 flex h-5 w-5 cursor-pointer items-center justify-center rounded-full bg-background/90 text-foreground shadow-xs transition-colors hover:bg-destructive hover:text-destructive-foreground"
                    title="Remove reference image"
                    @click="removeImage(index)"
                >
                    <X class="h-3 w-3" />
                    <span class="sr-only">Remove image</span>
                </button>
            </div>

            <div
                v-for="slot in uploadingCount"
                :key="`uploading-${slot}`"
                class="flex aspect-square items-center justify-center rounded-lg border border-dashed border-primary/50 bg-primary/5 text-primary"
            >
                <Loader2 class="h-4 w-4 animate-spin" />
            </div>
        </div>

        <!-- Visual Image Upload Dropzone -->
        <input
            ref="fileInputRef"
            type="file"
            accept="image/png,image/jpeg,image/webp"
            multiple
            class="hidden"
            data-test="reference-image-file-input"
            @change="handleFileSelect"
        />

        <div
            v-if="canAddMore"
            role="button"
            tabindex="0"
            class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border border-dashed px-3 py-3 text-center transition-colors focus:ring-2 focus:ring-primary/40 focus:outline-none"
            :class="
                isDragging
                    ? 'border-primary bg-primary/10 text-foreground'
                    : 'border-border/80 bg-background/40 text-muted-foreground hover:border-primary/60 hover:bg-muted/30 hover:text-foreground dark:bg-card/40'
            "
            @click="openFilePicker"
            @keydown.enter.prevent="openFilePicker"
            @keydown.space.prevent="openFilePicker"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
        >
            <div class="flex items-center gap-2 text-xs font-medium">
                <UploadCloud class="h-4 w-4 text-primary" />
                <span>Upload reference images</span>
            </div>
            <span class="text-[11px] text-muted-foreground">
                PNG, JPG, WEBP up to 5 MB · Drag & drop or click
            </span>
        </div>

        <!-- Optional URL paste toggle -->
        <div v-if="canAddMore" class="space-y-2">
            <button
                type="button"
                class="inline-flex cursor-pointer items-center gap-1 text-[11px] font-medium text-muted-foreground transition-colors hover:text-primary"
                @click="showUrlInput = !showUrlInput"
            >
                <Link2 class="h-3 w-3" />
                <span>{{
                    showUrlInput ? 'Hide URL input' : 'Or paste an image URL'
                }}</span>
            </button>

            <div v-if="showUrlInput" class="flex items-center gap-1.5">
                <div class="relative flex-1">
                    <ImageIcon
                        class="pointer-events-none absolute top-1/2 left-2.5 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="urlDraft"
                        type="url"
                        placeholder="https://example.com/reference.png"
                        class="h-8 pl-8 text-xs"
                        @keydown.enter.prevent="addManualUrl"
                    />
                </div>
                <Button
                    type="button"
                    size="sm"
                    variant="secondary"
                    class="h-8 px-2.5 text-xs"
                    @click="addManualUrl"
                >
                    <Plus class="mr-1 h-3 w-3" />
                    Add
                </Button>
            </div>
        </div>
    </div>
</template>
