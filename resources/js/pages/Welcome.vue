<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Activity,
    ArrowRight,
    Check,
    Coins,
    Sparkles,
    Wand2,
} from '@lucide/vue';
import { onMounted, onUnmounted, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import AuthModal from '@/components/AuthModal.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

const authModalOpen = ref(false);
const authModalMode = ref<'login' | 'register'>('login');
const isScrolled = ref(false);

const handleScroll = () => {
    isScrolled.value = window.scrollY > 20;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
    isScrolled.value = window.scrollY > 20;
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
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

const features = [
    {
        icon: Wand2,
        title: 'AI-Powered Image Generation',
        description:
            'Transform natural language prompts into stunning visual concepts with fine-grained control over aspect ratios and creative direction.',
        badge: 'Creative AI Engine',
    },
    {
        icon: Coins,
        title: 'Transparent Usage-Based Pricing',
        description:
            'Know the exact cost before you generate. Clear per-image token pricing with no recurring subscriptions or hidden lock-ins.',
        badge: 'Upfront Estimation',
    },
    {
        icon: Activity,
        title: 'Real-Time Generation Tracking',
        description:
            'Monitor every step from pipeline submission to high-resolution render with live progress metrics and latency status.',
        badge: 'Live Progress',
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

        <!-- Hero Section with Immersive Rotated Mosaic Background -->
        <section class="relative overflow-hidden pt-12 pb-24 lg:pt-20 lg:pb-36">
            <!-- Immersive Background: Slightly rotated mosaic of creative artwork -->
            <div
                class="pointer-events-none absolute inset-0 -top-8 flex items-center justify-center overflow-hidden opacity-40 select-none dark:opacity-30"
                aria-hidden="true"
            >
                <div
                    class="grid max-w-7xl -rotate-2 grid-cols-2 gap-4 px-4 sm:gap-6 md:-rotate-3 md:grid-cols-3"
                >
                    <!-- Artwork 1: Neon Botanical Terrarium -->
                    <div
                        class="group relative aspect-[4/3] overflow-hidden rounded-2xl border border-border/70 bg-card/95 shadow-sm dark:border-[#404040]/70 dark:bg-[#242424]/90 dark:shadow-2xl"
                    >
                        <svg
                            viewBox="0 0 400 300"
                            class="h-full w-full object-cover"
                        >
                            <defs>
                                <linearGradient
                                    id="g1"
                                    x1="0%"
                                    y1="0%"
                                    x2="100%"
                                    y2="100%"
                                >
                                    <stop offset="0%" stop-color="#052e16" />
                                    <stop offset="50%" stop-color="#10B981" />
                                    <stop offset="100%" stop-color="#A3E600" />
                                </linearGradient>
                                <radialGradient
                                    id="glow1"
                                    cx="60%"
                                    cy="40%"
                                    r="50%"
                                >
                                    <stop
                                        offset="0%"
                                        stop-color="#A3E600"
                                        stop-opacity="0.6"
                                    />
                                    <stop
                                        offset="100%"
                                        stop-color="#052e16"
                                        stop-opacity="0"
                                    />
                                </radialGradient>
                            </defs>
                            <rect width="400" height="300" fill="#141E16" />
                            <circle
                                cx="240"
                                cy="120"
                                r="120"
                                fill="url(#glow1)"
                            />
                            <path
                                d="M50 250 Q 150 100 200 200 T 350 150 L 400 300 L 0 300 Z"
                                fill="url(#g1)"
                                opacity="0.85"
                            />
                            <circle
                                cx="120"
                                cy="80"
                                r="3"
                                fill="#A3E600"
                                opacity="0.8"
                            />
                            <circle
                                cx="280"
                                cy="160"
                                r="4"
                                fill="#4ADE80"
                                opacity="0.9"
                            />
                            <circle
                                cx="310"
                                cy="70"
                                r="2.5"
                                fill="#10B981"
                                opacity="0.7"
                            />
                        </svg>
                        <div
                            class="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-card/95 via-card/40 to-transparent p-3 dark:from-[#1A1A1A]/90 dark:via-transparent"
                        >
                            <span
                                class="text-[10px] font-medium text-foreground dark:text-[#FAFAFA]"
                                >Simulated Artwork • Generative v2</span
                            >
                        </div>
                    </div>

                    <!-- Artwork 2: Prismatic Monolith -->
                    <div
                        class="relative aspect-[4/3] overflow-hidden rounded-2xl border border-border/70 bg-card/95 shadow-sm dark:border-[#404040]/70 dark:bg-[#242424]/90 dark:shadow-2xl"
                    >
                        <svg
                            viewBox="0 0 400 300"
                            class="h-full w-full object-cover"
                        >
                            <defs>
                                <linearGradient
                                    id="g2"
                                    x1="0%"
                                    y1="100%"
                                    x2="100%"
                                    y2="0%"
                                >
                                    <stop offset="0%" stop-color="#1A1A1A" />
                                    <stop offset="60%" stop-color="#047857" />
                                    <stop offset="100%" stop-color="#A3E600" />
                                </linearGradient>
                            </defs>
                            <rect width="400" height="300" fill="#18181B" />
                            <polygon
                                points="200,40 320,240 80,240"
                                fill="url(#g2)"
                                opacity="0.9"
                            />
                            <line
                                x1="200"
                                y1="40"
                                x2="200"
                                y2="240"
                                stroke="#A3E600"
                                stroke-width="1.5"
                                opacity="0.6"
                            />
                        </svg>
                        <div
                            class="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-card/95 via-card/40 to-transparent p-3 dark:from-[#1A1A1A]/90 dark:via-transparent"
                        >
                            <span
                                class="text-[10px] font-medium text-foreground dark:text-[#FAFAFA]"
                                >Simulated Artwork • 1024×1024</span
                            >
                        </div>
                    </div>

                    <!-- Artwork 3: Cyber Dunes Horizon -->
                    <div
                        class="relative hidden aspect-[4/3] overflow-hidden rounded-2xl border border-border/70 bg-card/95 shadow-sm md:block dark:border-[#404040]/70 dark:bg-[#242424]/90 dark:shadow-2xl"
                    >
                        <svg
                            viewBox="0 0 400 300"
                            class="h-full w-full object-cover"
                        >
                            <defs>
                                <linearGradient
                                    id="g3"
                                    x1="50%"
                                    y1="0%"
                                    x2="50%"
                                    y2="100%"
                                >
                                    <stop offset="0%" stop-color="#064E3B" />
                                    <stop offset="70%" stop-color="#1A1A1A" />
                                </linearGradient>
                            </defs>
                            <rect width="400" height="300" fill="url(#g3)" />
                            <circle
                                cx="200"
                                cy="110"
                                r="55"
                                fill="#A3E600"
                                opacity="0.75"
                            />
                            <path
                                d="M0 190 Q 100 150 200 180 T 400 170 L 400 300 L 0 300 Z"
                                fill="#0f172a"
                            />
                            <path
                                d="M0 220 Q 160 180 260 210 T 400 200 L 400 300 L 0 300 Z"
                                fill="#042f2e"
                                opacity="0.9"
                            />
                        </svg>
                        <div
                            class="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-card/95 via-card/40 to-transparent p-3 dark:from-[#1A1A1A]/90 dark:via-transparent"
                        >
                            <span
                                class="text-[10px] font-medium text-foreground dark:text-[#FAFAFA]"
                                >Simulated Artwork • Creative Seed</span
                            >
                        </div>
                    </div>

                    <!-- Artwork 4: Quantum Neural Web -->
                    <div
                        class="relative hidden aspect-[4/3] overflow-hidden rounded-2xl border border-border/70 bg-card/95 shadow-sm md:block dark:border-[#404040]/70 dark:bg-[#242424]/90 dark:shadow-2xl"
                    >
                        <svg
                            viewBox="0 0 400 300"
                            class="h-full w-full object-cover"
                        >
                            <rect width="400" height="300" fill="#111827" />
                            <path
                                d="M30 50 L 140 180 L 260 90 L 370 230"
                                stroke="#A3E600"
                                stroke-width="2"
                                fill="none"
                                opacity="0.6"
                            />
                            <path
                                d="M80 260 L 210 140 L 330 200"
                                stroke="#10B981"
                                stroke-width="1.5"
                                fill="none"
                                opacity="0.5"
                            />
                            <circle
                                cx="140"
                                cy="180"
                                r="7"
                                fill="#A3E600"
                                opacity="0.9"
                            />
                            <circle
                                cx="260"
                                cy="90"
                                r="6"
                                fill="#4ADE80"
                                opacity="0.8"
                            />
                            <circle
                                cx="210"
                                cy="140"
                                r="5"
                                fill="#10B981"
                                opacity="0.85"
                            />
                        </svg>
                        <div
                            class="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-card/95 via-card/40 to-transparent p-3 dark:from-[#1A1A1A]/90 dark:via-transparent"
                        >
                            <span
                                class="text-[10px] font-medium text-foreground dark:text-[#FAFAFA]"
                                >Simulated Artwork • Vector AI</span
                            >
                        </div>
                    </div>

                    <!-- Artwork 5: Prismatic Aurora Mesh -->
                    <div
                        class="relative aspect-[4/3] overflow-hidden rounded-2xl border border-border/70 bg-card/95 shadow-sm dark:border-[#404040]/70 dark:bg-[#242424]/90 dark:shadow-2xl"
                    >
                        <svg
                            viewBox="0 0 400 300"
                            class="h-full w-full object-cover"
                        >
                            <defs>
                                <radialGradient
                                    id="g5"
                                    cx="50%"
                                    cy="50%"
                                    r="60%"
                                >
                                    <stop offset="0%" stop-color="#A3E600" />
                                    <stop offset="50%" stop-color="#059669" />
                                    <stop offset="100%" stop-color="#1A1A1A" />
                                </radialGradient>
                            </defs>
                            <rect width="400" height="300" fill="#18181B" />
                            <ellipse
                                cx="200"
                                cy="150"
                                rx="160"
                                ry="90"
                                fill="url(#g5)"
                                opacity="0.7"
                            />
                        </svg>
                        <div
                            class="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-card/95 via-card/40 to-transparent p-3 dark:from-[#1A1A1A]/90 dark:via-transparent"
                        >
                            <span
                                class="text-[10px] font-medium text-foreground dark:text-[#FAFAFA]"
                                >Simulated Artwork • Ambient Glow</span
                            >
                        </div>
                    </div>

                    <!-- Artwork 6: Architectural Pavillion -->
                    <div
                        class="relative aspect-[4/3] overflow-hidden rounded-2xl border border-border/70 bg-card/95 shadow-sm dark:border-[#404040]/70 dark:bg-[#242424]/90 dark:shadow-2xl"
                    >
                        <svg
                            viewBox="0 0 400 300"
                            class="h-full w-full object-cover"
                        >
                            <rect width="400" height="300" fill="#1A1A1A" />
                            <polygon
                                points="60,260 160,80 340,80 240,260"
                                fill="#2B2B2B"
                                stroke="#404040"
                            />
                            <polygon
                                points="160,80 240,260 210,260 130,80"
                                fill="#A3E600"
                                opacity="0.3"
                            />
                            <line
                                x1="340"
                                y1="80"
                                x2="240"
                                y2="260"
                                stroke="#10B981"
                                stroke-width="2"
                            />
                        </svg>
                        <div
                            class="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-card/95 via-card/40 to-transparent p-3 dark:from-[#1A1A1A]/90 dark:via-transparent"
                        >
                            <span
                                class="text-[10px] font-medium text-foreground dark:text-[#FAFAFA]"
                                >Simulated Artwork • 3D Render</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Radial Vignette Overlay keeping headline & text razor-sharp -->
            <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-b from-background/30 via-background/80 to-background dark:from-background/40 dark:via-background/85 dark:to-background"
                aria-hidden="true"
            ></div>
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_20%,var(--color-background)_75%)]"
                aria-hidden="true"
            ></div>

            <!-- Hero Main Foreground Content -->
            <div class="relative z-10 container mx-auto max-w-5xl px-4 sm:px-6">
                <div class="flex flex-col items-center text-center">
                    <!-- Headline: Oversized, bold, tight letter spacing -->
                    <h1
                        class="font-bricolage text-5xl leading-[1.05] font-extrabold tracking-tight text-balance sm:text-6xl md:text-7xl lg:text-8xl"
                    >
                        <span class="block text-foreground">Imagine it.</span>
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

                    <!-- Trust / Transparency Micro-Badges -->
                    <div
                        class="mt-12 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-xs text-secondary-foreground dark:text-muted-foreground"
                    >
                        <div class="flex items-center gap-1.5">
                            <Check
                                class="h-3.5 w-3.5 text-[#3F6212] dark:text-[#A3E600]"
                            />
                            <span>Transparent Pay-As-You-Go</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <Check
                                class="h-3.5 w-3.5 text-[#3F6212] dark:text-[#A3E600]"
                            />
                            <span>Instant Generation Tracking</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <Check
                                class="h-3.5 w-3.5 text-[#3F6212] dark:text-[#A3E600]"
                            />
                            <span>No Hidden Subscriptions</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Feature Section: 3 Minimal Cards on subtle glass surfaces -->
        <section
            id="features"
            class="scroll-mt-20 border-t border-border bg-secondary/50 py-20 lg:py-28 dark:border-[#404040]/40 dark:bg-surface/30"
        >
            <div class="container mx-auto max-w-6xl px-4 sm:px-6">
                <!-- Section Header -->
                <div class="mx-auto mb-16 max-w-2xl text-center">
                    <span
                        class="text-xs font-semibold tracking-wider text-[#3F6212] uppercase dark:text-[#A3E600]"
                    >
                        Core Features
                    </span>
                    <h2
                        class="mt-2 font-bricolage text-3xl font-bold tracking-tight text-foreground sm:text-4xl"
                    >
                        Designed for creators. Built for clarity.
                    </h2>
                    <p
                        class="mt-3 text-sm text-secondary-foreground sm:text-base dark:text-muted-foreground"
                    >
                        NextPixel strips away friction so you can focus purely
                        on ideation, precision, and predictable costs.
                    </p>
                </div>

                <!-- 3 Feature Cards Grid -->
                <div class="grid gap-6 md:grid-cols-3">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-border bg-card p-7 shadow-xs backdrop-blur-md transition-all duration-300 hover:border-[#3F6212]/30 hover:shadow-md dark:border-[#404040]/60 dark:bg-[#242424]/70 dark:shadow-sm dark:hover:border-[#A3E600]/50 dark:hover:shadow-[0_0_24px_rgba(163,230,0,0.12)]"
                    >
                        <div>
                            <!-- Lime-colored Lucide Icon Container with soft background in light mode -->
                            <div
                                class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl border border-[#A3E600]/40 bg-[#A3E600]/15 text-[#3F6212] shadow-xs transition-colors duration-200 group-hover:border-[#3F6212]/30 dark:border-[#404040]/70 dark:bg-[#2B2B2B] dark:text-[#A3E600] dark:group-hover:border-[#A3E600]/40"
                            >
                                <component :is="feature.icon" class="h-6 w-6" />
                            </div>

                            <h3
                                class="font-bricolage text-lg font-bold text-foreground"
                            >
                                {{ feature.title }}
                            </h3>
                            <p
                                class="mt-2.5 text-sm leading-relaxed text-secondary-foreground dark:text-muted-foreground"
                            >
                                {{ feature.description }}
                            </p>
                        </div>

                        <!-- Card Footer Tag -->
                        <div
                            class="mt-6 border-t border-border pt-4 dark:border-[#404040]/40"
                        >
                            <span
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#3F6212] dark:text-[#A3E600]"
                            >
                                <Sparkles
                                    class="h-3 w-3 text-[#3F6212] dark:text-[#A3E600]"
                                />
                                {{ feature.badge }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section: 3 Simple Steps -->
        <section
            id="how-it-works"
            class="scroll-mt-20 border-t border-border bg-background py-20 lg:py-28 dark:border-[#404040]/40"
        >
            <div class="container mx-auto max-w-6xl px-4 sm:px-6">
                <!-- Section Header -->
                <div class="mx-auto mb-16 max-w-2xl text-center">
                    <span
                        class="text-xs font-semibold tracking-wider text-[#3F6212] uppercase dark:text-[#A3E600]"
                    >
                        Workflow
                    </span>
                    <h2
                        class="mt-2 font-bricolage text-3xl font-bold tracking-tight text-foreground sm:text-4xl"
                    >
                        How it works
                    </h2>
                    <p
                        class="mt-3 text-sm text-secondary-foreground sm:text-base dark:text-muted-foreground"
                    >
                        From a spark of imagination to production-ready artwork
                        in three seamless steps.
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
                                <div class="flex items-center justify-between">
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

        <!-- Final CTA Section: Soft Light Gradient / Dark Aurora Background -->
        <section class="container mx-auto max-w-6xl px-4 py-20 sm:px-6">
            <div
                class="relative overflow-hidden rounded-3xl border border-[#A3E600]/40 bg-[linear-gradient(120deg,#F7FEE7_0%,#ECFDF5_50%,#F0FDF4_100%)] px-6 py-16 text-center shadow-md sm:px-12 sm:py-20 dark:border-[#404040]/70 dark:bg-[#242424]/90 dark:bg-none dark:shadow-2xl"
            >
                <!-- Aurora Animated Glow -->
                <div
                    class="aurora-glow pointer-events-none absolute -inset-10 opacity-20 blur-3xl dark:opacity-45"
                    aria-hidden="true"
                >
                    <div
                        class="absolute inset-0 bg-gradient-to-r from-[#A3E600]/25 via-[#4ADE80]/20 to-[#059669]/25"
                    ></div>
                </div>

                <div class="relative z-10 mx-auto max-w-2xl">
                    <h2
                        class="font-bricolage text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl md:text-5xl"
                    >
                        Your next idea starts here.
                    </h2>
                    <p
                        class="mt-4 text-base text-balance text-secondary-foreground sm:text-lg dark:text-muted-foreground"
                    >
                        Create images, explore ideas, and stay in control of
                        every generation.
                    </p>

                    <div class="mt-8 flex justify-center">
                        <template v-if="$page.props.auth?.user">
                            <Button
                                size="lg"
                                as-child
                                class="h-12 cursor-pointer bg-[#A3E600] px-9 text-base font-semibold text-[#171717] shadow-xs transition-all duration-200 hover:bg-[#b2f500] hover:shadow-md dark:shadow-[0_0_24px_rgba(163,230,0,0.4)] dark:hover:shadow-[0_0_36px_rgba(163,230,0,0.6)]"
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
                                class="h-12 cursor-pointer bg-[#A3E600] px-9 text-base font-semibold text-[#171717] shadow-xs transition-all duration-200 hover:bg-[#b2f500] hover:shadow-md dark:shadow-[0_0_24px_rgba(163,230,0,0.4)] dark:hover:shadow-[0_0_36px_rgba(163,230,0,0.6)]"
                                @click="openRegisterModal"
                            >
                                Start creating
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Button>
                        </template>
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
