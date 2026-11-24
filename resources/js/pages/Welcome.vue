<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Activity, ArrowRight, Coins, Wand2 } from '@lucide/vue';
import { onMounted, onUnmounted, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import AuthModal from '@/components/AuthModal.vue';
import DriftWall, { type DriftWallItem } from '@/components/DriftWall.vue';
import PatternWaves from '@/components/PatternWaves.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';
import { dashboard } from '@/routes';

const { resolvedAppearance } = useAppearance();

const authModalOpen = ref(false);
const authModalMode = ref<'login' | 'register'>('login');
const isScrolled = ref(false);

const handleScroll = () => {
    isScrolled.value = window.scrollY > 20;
};

const driftWallColumns = ref(8);
const updateDriftWallColumns = () => {
    if (typeof window === 'undefined') return;
    if (window.innerWidth >= 1536) {
        driftWallColumns.value = 9;
    } else if (window.innerWidth >= 1024) {
        driftWallColumns.value = 8;
    } else if (window.innerWidth >= 640) {
        driftWallColumns.value = 6;
    } else {
        driftWallColumns.value = 4;
    }
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('resize', updateDriftWallColumns, {
        passive: true,
    });
    isScrolled.value = window.scrollY > 20;
    updateDriftWallColumns();
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    window.removeEventListener('resize', updateDriftWallColumns);
});

const openLoginModal = () => {
    authModalMode.value = 'login';
    authModalOpen.value = true;
};

const openRegisterModal = () => {
    authModalMode.value = 'register';
    authModalOpen.value = true;
};

const scrollTo = (id: string) => {
    const el = document.getElementById(id);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
    }
};

const driftWallItems: DriftWallItem[] = [
    {
        image: '/images/drift-wall/art-1.jpg',
        title: 'Cyberpunk Metropolis',
        prompt: 'Volumetric neon rain over towering cybernetic skyline, reflections on wet asphalt',
        badge: '1536×1024 • High',
    },
    {
        image: '/images/drift-wall/art-2.jpg',
        title: 'Botanical Sanctuary',
        prompt: 'Macro crystalline terrarium with bioluminescent orchids, soft morning mist, 8k',
        badge: '1024×1024 • High',
    },
    {
        image: '/images/drift-wall/art-3.jpg',
        title: 'Prismatic Monolith',
        prompt: 'Floating black glass obelisk dispersing spectral caustics in endless void, octane 3D',
        badge: '1024×1536 • Medium',
    },
    {
        image: '/images/drift-wall/art-4.jpg',
        title: 'Neural Synthetics',
        prompt: 'Cosmic nebula interwoven with glowing golden synaptic fibers, interstellar particle dust',
        badge: '1536×1024 • High',
    },
    {
        image: '/images/drift-wall/art-5.jpg',
        title: 'Brutalist Pavillion',
        prompt: 'Minimalist concrete villa on Mediterranean cliffs with emerald infinity pool, architectural photo',
        badge: '1024×1024 • High',
    },
    {
        image: '/images/drift-wall/art-6.jpg',
        title: 'Robotic Cyber-Samurai',
        prompt: 'Matte carbon fiber exoskeleton armor, glowing teal optical visor, studio rim lighting portrait',
        badge: '1024×1536 • High',
    },
    {
        image: '/images/drift-wall/art-7.jpg',
        title: 'Martian Dunes Explorer',
        prompt: 'Surreal crimson dunes at twin sunset, autonomous rover surveying ancient alien monolith',
        badge: '1536×1024 • Medium',
    },
    {
        image: '/images/drift-wall/art-8.jpg',
        title: 'Emerald Vein Geode',
        prompt: 'Cross-section of volcanic geode with liquid gold filament veins, macro laboratory lens',
        badge: '1024×1024 • High',
    },
    {
        image: '/images/drift-wall/art-9.jpg',
        title: 'Fluid Chrome Kinetic',
        prompt: 'Morphing liquid mercury sculpture suspended in brutalist gallery space, raytraced reflections',
        badge: '1024×1536 • High',
    },
    {
        image: '/images/drift-wall/art-10.jpg',
        title: 'Vaporwave Sunset Highway',
        prompt: 'Retro-futuristic palm grid landscape under glowing magenta sun, lo-fi aesthetic wallpaper',
        badge: '1536×1024 • Medium',
    },
    {
        image: '/images/drift-wall/art-11.jpg',
        title: 'Submerged Sunken Temple',
        prompt: 'Ancient flooded temple sanctuary illuminated by underwater sunlight caustics, coral reef',
        badge: '1024×1024 • High',
    },
    {
        image: '/images/drift-wall/art-12.jpg',
        title: 'Isometric Lab Studio',
        prompt: 'Warm cozy developer workstation with miniature bonsai plants, soft isometric 3D lighting',
        badge: '1024×1536 • Low',
    },
    {
        image: '/images/drift-wall/art-13.jpg',
        title: 'Holographic White Tiger',
        prompt: 'Mechanized predator prowling through neon bamboo grove, dynamic motion blur, unreal engine 5',
        badge: '1536×1024 • High',
    },
    {
        image: '/images/drift-wall/art-14.jpg',
        title: 'Magma Caldera Gateway',
        prompt: 'Obsidian portal framing boiling subterranean magma lake, heat distortion embers',
        badge: '1024×1024 • High',
    },
    {
        image: '/images/drift-wall/art-15.jpg',
        title: 'Steampunk Cloud Cruiser',
        prompt: 'Victorian brass and mahogany airship sailing through towering cumulus sunset cloudscape',
        badge: '1024×1536 • Medium',
    },
];

const features = [
    {
        icon: Wand2,
        title: 'AI-Powered Image Generation',
    },
    {
        icon: Coins,
        title: 'Transparent Usage-Based Pricing',
    },
    {
        icon: Activity,
        title: 'Real-Time Generation Tracking',
    },
];

const steps = [
    {
        step: '01',
        title: 'Describe your idea',
        description:
            'Enter your visual concept in plain language. Describe styles, lighting, perspectives, and key aesthetic details.',
        samplePrompt:
            '“Bioluminescent botanicals glowing inside an emerald glass conservatory, cinematic volumetric rays, 8k”',
    },
    {
        step: '02',
        title: 'Choose settings & review price',
        description:
            'Select your desired resolution, aspect ratio, and sampling quality. See the exact price before you generate.',
        sampleMeta: [
            { label: 'Aspect Ratio', val: '1:1 Square' },
            { label: 'Fidelity', val: '50 Steps' },
            { label: 'Est. Cost', val: '$0.02' },
        ],
    },
    {
        step: '03',
        title: 'Generate and track your results',
        description:
            'Submit your job and watch real-time progress indicators as your high-fidelity image renders into your library.',
        sampleStatus: {
            progress: 100,
            time: '1.2s',
            status: 'Completed',
        },
    },
];
</script>

<template>
    <Head title="NextPixel - From imagination to image" />

    <div
        class="min-h-screen bg-background font-bricolage text-foreground selection:bg-[#A3E600] selection:text-[#171717]"
    >
        <!-- Header: Clean light / solid charcoal on scroll -->
        <header
            :class="[
                isScrolled
                    ? 'border-b border-border/80 bg-background/90 shadow-xs backdrop-blur-md dark:border-[#404040]/50 dark:bg-[#1A1A1A]/95'
                    : 'border-b border-transparent bg-transparent',
                'sticky top-0 z-50 w-full transition-all duration-300',
            ]"
        >
            <div
                class="container mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6"
            >
                <!-- Logo & Brand -->
                <Link
                    :href="'/'"
                    class="flex items-center gap-2.5 text-lg font-semibold tracking-tight"
                >
                    <AppLogoIcon class="size-8" />
                    <span class="font-bold tracking-tight text-foreground"
                        >NextPixel</span
                    >
                </Link>

                <!-- Navigation Links -->
                <nav class="hidden items-center gap-7 md:flex">
                    <button
                        type="button"
                        class="cursor-pointer text-sm font-medium text-secondary-foreground transition-colors hover:text-foreground dark:text-muted-foreground"
                        @click="scrollTo('features')"
                    >
                        Features
                    </button>
                    <button
                        type="button"
                        class="cursor-pointer text-sm font-medium text-secondary-foreground transition-colors hover:text-foreground dark:text-muted-foreground"
                        @click="scrollTo('how-it-works')"
                    >
                        How it works
                    </button>
                </nav>

                <!-- Header Actions -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <Button
                        variant="ghost"
                        size="icon"
                        as-child
                        class="h-9 w-9 cursor-pointer rounded-md border border-border/70 bg-secondary/60 text-foreground transition-colors hover:bg-secondary hover:text-foreground dark:border-[#404040]/60 dark:bg-[#242424]/60 dark:hover:bg-[#2B2B2B]"
                    >
                        <a
                            href="https://github.com/isemary/next-pixel"
                            target="_blank"
                            rel="noreferrer"
                            title="GitHub Repository"
                            aria-label="GitHub Repository"
                        >
                            <svg
                                class="h-4 w-4 fill-current"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"
                                />
                            </svg>
                            <span class="sr-only">GitHub</span>
                        </a>
                    </Button>

                    <ThemeToggle />

                    <template v-if="$page.props.auth?.user">
                        <Button
                            as-child
                            class="cursor-pointer bg-[#A3E600] font-semibold text-[#171717] shadow-xs transition-all duration-200 hover:bg-[#b2f500] hover:shadow-sm dark:shadow-[0_0_16px_rgba(163,230,0,0.3)]"
                        >
                            <Link :href="dashboard()">
                                Dashboard
                                <ArrowRight class="ml-1.5 h-4 w-4" />
                            </Link>
                        </Button>
                    </template>
                    <template v-else>
                        <Button
                            variant="ghost"
                            class="cursor-pointer text-secondary-foreground transition-colors hover:text-foreground dark:text-muted-foreground"
                            @click="openLoginModal"
                        >
                            Log in
                        </Button>
                        <Button
                            class="cursor-pointer bg-[#A3E600] font-semibold text-[#171717] shadow-xs transition-all duration-200 hover:bg-[#b2f500] hover:shadow-sm dark:shadow-[0_0_16px_rgba(163,230,0,0.3)]"
                            @click="openRegisterModal"
                        >
                            Sign up
                        </Button>
                    </template>
                </div>
            </div>
        </header>

        <!-- Seamless Hero + Features + How It Works Canvas -->
        <div class="relative overflow-hidden bg-background">
            <!-- Pattern Waves Animated Background extending softly into Features -->
            <div
                class="pointer-events-none absolute inset-x-0 top-0 z-0 h-[560px] overflow-hidden sm:h-[620px] lg:h-[680px]"
                aria-hidden="true"
            >
                <PatternWaves
                    preset="silk"
                    :color="
                        resolvedAppearance === 'dark' ? '#A3E600' : '#4D7C0F'
                    "
                    :background-color="
                        resolvedAppearance === 'dark' ? '#1A1A1A' : '#F8F9FA'
                    "
                    :opacity="resolvedAppearance === 'dark' ? 0.45 : 0.28"
                    fade="bottom"
                    :fade-size="0.65"
                    :speed="0.3"
                    :scale="1.0"
                    :interactive="true"
                    class="h-full w-full"
                />

                <!-- Soft Radial & Bottom Gradient Overlays fading seamlessly into background -->
                <div
                    class="absolute inset-0 bg-gradient-to-b from-background/30 via-background/70 via-70% to-background dark:from-background/40 dark:via-background/75 dark:via-70% dark:to-background"
                ></div>
                <div
                    class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_25%,var(--color-background)_80%)]"
                ></div>
            </div>

            <!-- Hero Section foreground -->
            <section
                class="relative z-10 pt-12 pb-10 sm:pb-12 lg:pt-20 lg:pb-14"
            >
                <div class="container mx-auto max-w-5xl px-4 sm:px-6">
                    <div class="flex flex-col items-center text-center">
                        <!-- Headline: Oversized, bold, tight letter spacing -->
                        <h1
                            class="font-bricolage text-5xl leading-[1.05] font-extrabold tracking-tight text-balance sm:text-6xl md:text-7xl lg:text-8xl"
                        >
                            <span class="block text-foreground"
                                >Imagine it.</span
                            >
                            <span
                                class="mt-1 block bg-gradient-to-r from-[#3F6212] via-[#16A34A] to-[#059669] bg-clip-text text-transparent [text-shadow:none] dark:from-[#A3E600] dark:via-[#4ADE80] dark:to-[#059669] dark:[text-shadow:0_0_35px_rgba(163,230,0,0.35)]"
                            >
                                Generate it.
                            </span>
                        </h1>

                        <!-- Description -->
                        <p
                            class="mt-6 max-w-2xl text-base text-balance text-secondary-foreground sm:text-lg md:text-xl dark:text-muted-foreground"
                        >
                            Turn your ideas into images with AI. Simple tools.
                            Transparent pricing. No surprises.
                        </p>

                        <!-- CTA Buttons -->
                        <div
                            class="mt-10 flex flex-wrap items-center justify-center gap-4"
                        >
                            <template v-if="$page.props.auth?.user">
                                <Button
                                    size="lg"
                                    as-child
                                    class="h-12 cursor-pointer bg-[#A3E600] px-8 text-base font-semibold text-[#171717] shadow-xs transition-all duration-200 hover:bg-[#b2f500] hover:shadow-md dark:shadow-[0_0_24px_rgba(163,230,0,0.38)] dark:hover:shadow-[0_0_36px_rgba(163,230,0,0.55)]"
                                >
                                    <Link :href="dashboard()">
                                        Start creating
                                        <ArrowRight class="ml-2 h-4 w-4" />
                                    </Link>
                                </Button>
                            </template>
                            <template v-else>
                                <Button
                                    size="lg"
                                    class="h-12 cursor-pointer bg-[#A3E600] px-8 text-base font-semibold text-[#171717] shadow-xs transition-all duration-200 hover:bg-[#b2f500] hover:shadow-md dark:shadow-[0_0_24px_rgba(163,230,0,0.38)] dark:hover:shadow-[0_0_36px_rgba(163,230,0,0.55)]"
                                    @click="openRegisterModal"
                                >
                                    Start creating
                                    <ArrowRight class="ml-2 h-4 w-4" />
                                </Button>
                                <Button
                                    size="lg"
                                    variant="outline"
                                    class="h-12 cursor-pointer border-border/80 bg-card/80 px-8 text-base font-medium text-foreground transition-all duration-200 hover:border-foreground/20 hover:bg-secondary dark:border-[#404040]/80 dark:bg-background/60 dark:hover:border-[#A3E600]/60 dark:hover:bg-muted/50"
                                    @click="scrollTo('how-it-works')"
                                >
                                    How it works
                                </Button>
                            </template>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Feature Section: Compact Pill Badges & Full-Width Drift Wall -->
            <section
                id="features"
                class="relative z-10 scroll-mt-20 pt-4 pb-4 sm:pb-6 lg:pt-6 lg:pb-8"
            >
                <div class="container mx-auto max-w-6xl px-4 sm:px-6">
                    <!-- Section Header -->
                    <div class="mx-auto max-w-2xl text-center">
                        <h2
                            class="font-bricolage text-3xl font-bold tracking-tight text-foreground sm:text-4xl"
                        >
                            Designed for creators. Built for clarity.
                        </h2>
                        <p
                            class="mt-3 text-sm text-secondary-foreground sm:text-base dark:text-muted-foreground"
                        >
                            NextPixel strips away friction so you can focus
                            purely on ideation, precision, and predictable
                            costs.
                        </p>
                    </div>

                    <!-- Compact Feature Badges -->
                    <div
                        class="mt-7 mb-10 flex flex-wrap items-center justify-center gap-2.5 sm:mb-12 sm:gap-3"
                    >
                        <div
                            v-for="feature in features"
                            :key="feature.title"
                            class="inline-flex items-center gap-2 rounded-full border border-border/80 bg-surface-elevated/90 px-4 py-2 text-xs font-medium text-foreground shadow-xs transition-colors duration-200 hover:border-[#3F6212]/30 hover:bg-card sm:text-sm dark:border-[#404040]/70 dark:bg-[#242424]/80 dark:hover:border-[#A3E600]/40 dark:hover:bg-[#2B2B2B]"
                        >
                            <component
                                :is="feature.icon"
                                class="h-4 w-4 shrink-0 text-[#3F6212] dark:text-[#A3E600]"
                            />
                            <span>{{ feature.title }}</span>
                        </div>
                    </div>
                </div>

                <!-- Full-Width 3D Drift Wall Showcase -->
                <div class="relative w-full overflow-hidden">
                    <div class="h-[400px] w-full sm:h-[460px] md:h-[520px]">
                        <DriftWall
                            :items="driftWallItems"
                            :columns="driftWallColumns"
                            :tile-width="240"
                            :tile-height="160"
                            :gap="18"
                            :radius="14"
                            :tilt="12"
                            :turn="-10"
                            :speed="36"
                            :depth="140"
                            :pause-on-hover="true"
                            :full-width="true"
                            :overlay-color="
                                resolvedAppearance === 'dark'
                                    ? '#000000'
                                    : '#ffffff'
                            "
                            class="h-full w-full"
                        />
                    </div>
                    <!-- Soft bottom gradient fading DriftWall naturally into How It Works -->
                    <div
                        class="pointer-events-none absolute inset-x-0 bottom-0 z-20 h-20 bg-gradient-to-t from-background via-background/70 to-transparent sm:h-28"
                        aria-hidden="true"
                    ></div>
                </div>
            </section>

            <!-- How It Works Section: 3 Simple Steps -->
            <section
                id="how-it-works"
                class="relative z-10 scroll-mt-20 pt-6 pb-20 sm:pt-8 lg:pt-10 lg:pb-24"
            >
                <div class="container mx-auto max-w-6xl px-4 sm:px-6">
                    <!-- Section Header -->
                    <div class="mx-auto mb-16 max-w-2xl text-center">
                        <h2
                            class="font-bricolage text-3xl font-bold tracking-tight text-foreground sm:text-4xl"
                        >
                            How it works
                        </h2>
                        <p
                            class="mt-3 text-sm text-secondary-foreground sm:text-base dark:text-muted-foreground"
                        >
                            From a spark of imagination to production-ready
                            artwork in three seamless steps.
                        </p>
                    </div>

                    <!-- 3 Steps Grid -->
                    <div class="grid gap-6 md:grid-cols-3">
                        <div
                            v-for="item in steps"
                            :key="item.step"
                            class="relative flex flex-col justify-between rounded-2xl border border-border bg-card p-7 shadow-xs backdrop-blur-md transition-all duration-300 hover:border-foreground/20 hover:shadow-md dark:border-[#404040]/60 dark:bg-[#242424]/60 dark:shadow-none dark:hover:border-[#404040]"
                        >
                            <div>
                                <!-- Step Number with high contrast in light mode and subtle glow in dark mode -->
                                <div class="mb-4">
                                    <span
                                        class="font-bricolage text-3xl font-extrabold text-[#3F6212] [text-shadow:none] dark:text-[#A3E600] dark:[text-shadow:0_0_15px_rgba(163,230,0,0.3)]"
                                    >
                                        {{ item.step }}
                                    </span>
                                </div>

                                <h3
                                    class="font-bricolage text-lg font-bold text-foreground"
                                >
                                    {{ item.title }}
                                </h3>
                                <p
                                    class="mt-2 text-sm leading-relaxed text-secondary-foreground dark:text-muted-foreground"
                                >
                                    {{ item.description }}
                                </p>
                            </div>

                            <!-- Micro UI Preview depending on step -->
                            <div
                                class="mt-6 border-t border-border pt-4 dark:border-[#404040]/40"
                            >
                                <!-- Step 1 snippet: Prompt bar -->
                                <div
                                    v-if="item.samplePrompt"
                                    class="rounded-lg border border-border bg-secondary p-3 text-xs font-medium text-secondary-foreground italic dark:border-[#404040]/60 dark:bg-[#1A1A1A] dark:text-muted-foreground"
                                >
                                    {{ item.samplePrompt }}
                                </div>

                                <!-- Step 2 snippet: Price breakdown -->
                                <div
                                    v-else-if="item.sampleMeta"
                                    class="grid grid-cols-3 gap-2"
                                >
                                    <div
                                        v-for="meta in item.sampleMeta"
                                        :key="meta.label"
                                        class="rounded-lg border border-border bg-secondary p-2 text-center dark:border-[#404040]/60 dark:bg-[#1A1A1A]"
                                    >
                                        <span
                                            class="block text-[10px] text-muted-foreground"
                                            >{{ meta.label }}</span
                                        >
                                        <span
                                            class="block text-xs font-semibold text-foreground"
                                            >{{ meta.val }}</span
                                        >
                                    </div>
                                </div>

                                <!-- Step 3 snippet: Real-time progress -->
                                <div
                                    v-else-if="item.sampleStatus"
                                    class="rounded-lg border border-border bg-secondary p-3 text-xs dark:border-[#404040]/60 dark:bg-[#1A1A1A]"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="font-semibold text-[#3F6212] dark:text-[#A3E600]"
                                            >100% Rendered</span
                                        >
                                        <span
                                            class="text-secondary-foreground dark:text-muted-foreground"
                                            >Time:
                                            {{ item.sampleStatus.time }}</span
                                        >
                                    </div>
                                    <div
                                        class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-border dark:bg-[#2B2B2B]"
                                    >
                                        <div
                                            class="h-full w-full rounded-full bg-gradient-to-r from-[#A3E600] to-[#10B981]"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Final CTA Section: Soft Lime & Emerald Waves (Adaptive Light & Dark Modes) -->
        <section
            class="container mx-auto max-w-5xl px-4 py-12 sm:px-6 sm:py-16 md:py-20"
        >
            <div
                class="relative overflow-hidden rounded-3xl bg-white px-6 py-12 text-center shadow-xl shadow-zinc-200/50 sm:px-12 sm:py-16 md:py-20 dark:bg-[#121418] dark:shadow-none"
            >
                <!-- Subtle Grain Texture Overlay for Visual Depth -->
                <div
                    class="pointer-events-none absolute inset-0 opacity-[0.035] mix-blend-overlay"
                    :style="{
                        backgroundImage: `url(&quot;data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E&quot;)`,
                        backgroundRepeat: 'repeat',
                    }"
                    aria-hidden="true"
                ></div>

                <!-- Soft Animated Ambient Lighting & Blurred Gradient Waves -->
                <div
                    class="cta-ambient-glow pointer-events-none absolute -inset-10 opacity-30 blur-3xl dark:opacity-45"
                    aria-hidden="true"
                >
                    <!-- Soft Lime Wave (Top-Left) -->
                    <div
                        class="absolute -top-16 -left-16 h-80 w-80 rounded-full bg-gradient-to-br from-[#A3E600]/30 via-[#4ADE80]/20 to-transparent"
                    ></div>
                    <!-- Soft Emerald Wave (Bottom-Right) -->
                    <div
                        class="absolute -right-16 -bottom-20 h-96 w-96 rounded-full bg-gradient-to-tl from-[#059669]/30 via-[#10B981]/20 to-transparent dark:from-[#059669]/35"
                    ></div>
                    <!-- Soft Ambient Emerald Accent (Top-Right) -->
                    <div
                        class="absolute top-0 right-1/4 h-64 w-64 rounded-full bg-gradient-to-b from-[#10B981]/15 to-transparent"
                    ></div>
                </div>

                <!-- Clean Center Vignette to Preserve Razor-Sharp Typography Contrast -->
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(255,255,255,0.75)_0%,transparent_80%)] dark:bg-[radial-gradient(ellipse_at_center,rgba(18,20,24,0.75)_0%,transparent_80%)]"
                    aria-hidden="true"
                ></div>

                <div class="relative z-10 mx-auto max-w-2xl">
                    <!-- Headline with subtle lime-to-emerald gradient on "starts here." -->
                    <h2
                        class="font-bricolage text-3xl font-extrabold tracking-tight text-balance text-zinc-900 sm:text-4xl md:text-5xl dark:text-white"
                    >
                        Your next idea
                        <span
                            class="bg-gradient-to-r from-[#4D7C0F] via-[#15803D] to-[#047857] bg-clip-text text-transparent dark:from-[#A3E600] dark:via-[#4ADE80] dark:to-[#10B981]"
                        >
                            starts here.
                        </span>
                    </h2>

                    <!-- Description -->
                    <p
                        class="mt-4 text-base leading-relaxed text-balance text-zinc-600 sm:text-lg dark:text-zinc-300"
                    >
                        Turn your imagination into images. Stay in control of
                        every generation and every dollar.
                    </p>

                    <!-- Primary Action & Subtle Button Glow -->
                    <div class="mt-8 flex flex-col items-center gap-4">
                        <template v-if="$page.props.auth?.user">
                            <Button
                                size="lg"
                                as-child
                                class="group relative h-12 cursor-pointer overflow-hidden rounded-xl bg-[#A3E600] px-8 text-base font-semibold text-[#171717] shadow-[0_0_20px_rgba(163,230,0,0.3)] transition-all duration-300 hover:scale-[1.02] hover:bg-[#b5f802] hover:shadow-[0_0_32px_rgba(163,230,0,0.5)] active:scale-[0.98] motion-reduce:transform-none"
                            >
                                <Link :href="dashboard()">
                                    <span>Start creating</span>
                                    <ArrowRight
                                        class="ml-2 h-4 w-4 transition-transform duration-200 group-hover:translate-x-1 motion-reduce:transform-none"
                                    />
                                </Link>
                            </Button>
                        </template>
                        <template v-else>
                            <Button
                                size="lg"
                                class="group relative h-12 cursor-pointer overflow-hidden rounded-xl bg-[#A3E600] px-8 text-base font-semibold text-[#171717] shadow-[0_0_20px_rgba(163,230,0,0.3)] transition-all duration-300 hover:scale-[1.02] hover:bg-[#b5f802] hover:shadow-[0_0_32px_rgba(163,230,0,0.5)] active:scale-[0.98] motion-reduce:transform-none"
                                @click="openRegisterModal"
                            >
                                <span>Start creating</span>
                                <ArrowRight
                                    class="ml-2 h-4 w-4 transition-transform duration-200 group-hover:translate-x-1 motion-reduce:transform-none"
                                />
                            </Button>
                        </template>

                        <!-- Supporting Micro Copy -->
                        <p
                            class="text-xs font-medium text-zinc-500 sm:text-sm dark:text-zinc-400"
                        >
                            Transparent pricing · No subscriptions · Failed
                            generations are free
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer: Clean Light Neutral / Solid Dark Surface -->
        <footer
            class="border-t border-border bg-[#F0F2F4] py-10 dark:border-[#404040]/40 dark:bg-[#1A1A1A]/90"
        >
            <div
                class="container mx-auto flex max-w-6xl flex-col items-center justify-between gap-6 px-4 text-xs text-secondary-foreground sm:flex-row sm:px-6 dark:text-muted-foreground"
            >
                <!-- Brand -->
                <div class="flex items-center gap-2">
                    <AppLogoIcon class="size-6" />
                    <span class="font-bold text-foreground">NextPixel</span>
                    <span class="text-border dark:text-[#404040]">•</span>
                    <span
                        class="text-xs text-secondary-foreground dark:text-muted-foreground"
                        >From imagination to image</span
                    >
                </div>

                <!-- Footer Navigation & Links -->
                <div class="flex flex-wrap items-center justify-center gap-6">
                    <button
                        type="button"
                        class="cursor-pointer transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-hidden"
                        @click="scrollTo('features')"
                    >
                        Features
                    </button>
                    <button
                        type="button"
                        class="cursor-pointer transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-hidden"
                        @click="scrollTo('how-it-works')"
                    >
                        How it works
                    </button>
                    <a
                        href="https://github.com/isemary/next-pixel"
                        target="_blank"
                        rel="noreferrer"
                        class="flex items-center gap-1.5 transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-hidden"
                    >
                        <svg
                            class="h-3.5 w-3.5 fill-current"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"
                            />
                        </svg>
                        <span>GitHub</span>
                    </a>
                </div>

                <!-- Copyright -->
                <div class="text-center sm:text-right">
                    <span>© 2026 NextPixel. All rights reserved.</span>
                </div>
            </div>
        </footer>

        <!-- Authentication Popup Modal (Login / Register in Inter Font) -->
        <AuthModal v-model:open="authModalOpen" :initial-mode="authModalMode" />
    </div>
</template>
