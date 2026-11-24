<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Layers, Wand2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { dashboard, library } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Image Studio',
                href: dashboard(),
            },
            {
                title: 'Library',
                href: library(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Your Library" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <div class="flex w-full flex-1 flex-col gap-6">
            <!-- Page Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="space-y-1">
                    <h1
                        class="font-bricolage text-2xl font-bold tracking-tight text-foreground"
                    >
                        Your library
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Explore and manage your image generations.
                    </p>
                </div>

                <div>
                    <Button
                        as-child
                        class="gap-2 bg-primary font-semibold text-primary-foreground shadow-xs hover:bg-primary/90"
                    >
                        <Link :href="dashboard()">
                            <Wand2 class="h-4 w-4" />
                            <span>Create an image</span>
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Responsive Gallery Container / Workspace -->
            <div
                class="flex flex-1 flex-col items-center justify-center rounded-2xl border border-dashed border-border/80 bg-card/40 p-8 text-center sm:p-16 dark:bg-card/70"
            >
                <div
                    class="relative mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-muted/60 text-muted-foreground ring-1 ring-border/80 dark:bg-card/80"
                >
                    <Layers
                        class="h-8 w-8 stroke-[1.5] text-muted-foreground"
                    />
                    <div
                        class="absolute -top-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-primary/20 text-primary"
                    >
                        <Wand2 class="h-3.5 w-3.5" />
                    </div>
                </div>

                <h3
                    class="font-bricolage text-base font-semibold text-foreground"
                >
                    No generations in your library
                </h3>

                <p
                    class="mt-1.5 max-w-sm text-xs leading-relaxed text-muted-foreground"
                >
                    Generations you create in Image Studio will appear here for
                    you to view, download, and manage.
                </p>

                <div class="mt-6">
                    <Button
                        as-child
                        variant="outline"
                        class="gap-2 border-border/80 bg-background/80 hover:bg-accent"
                    >
                        <Link :href="dashboard()">
                            <Wand2 class="h-3.5 w-3.5 text-primary" />
                            <span>Create an image</span>
                        </Link>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
