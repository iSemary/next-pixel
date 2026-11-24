<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ChevronDown, Coins, Plus } from '@lucide/vue';
import { computed, watch } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import BuyCreditsDialog from '@/components/BuyCreditsDialog.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCreditBalance } from '@/composables/useCreditBalance';
import { useInitials } from '@/composables/useInitials';
import type { BreadcrumbItem, User } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const user = computed<User>(() => page.props.auth?.user);
const { getInitials } = useInitials();
const { formattedBalance, syncBalance, openBuyCredits } = useCreditBalance();

watch(
    () => user.value?.credit_balance,
    (nextBalance) => {
        syncBalance(nextBalance);
    },
    { immediate: true },
);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="flex items-center gap-2.5 sm:gap-3">
            <!-- Credit Balance & Buy Credits Trigger -->
            <div
                v-if="user"
                class="flex items-center gap-1.5 rounded-xl border border-border/80 bg-muted/40 p-1 pl-2.5"
            >
                <div class="flex items-center gap-1.5 text-xs">
                    <Coins class="h-3.5 w-3.5 text-primary" />
                    <span class="hidden text-muted-foreground sm:inline"
                        >Credits:</span
                    >
                    <span
                        class="font-mono font-semibold text-foreground"
                        data-test="header-credit-balance"
                    >
                        {{ formattedBalance }}
                    </span>
                </div>
                <Button
                    type="button"
                    size="sm"
                    variant="secondary"
                    class="h-7 gap-1 rounded-lg px-2 text-xs font-semibold hover:bg-primary hover:text-primary-foreground"
                    data-test="buy-credits-button"
                    @click="openBuyCredits"
                >
                    <Plus class="h-3.5 w-3.5" />
                    <span>Buy credits</span>
                </Button>
            </div>

            <ThemeToggle />

            <DropdownMenu v-if="user">
                <DropdownMenuTrigger as-child>
                    <button
                        type="button"
                        class="flex cursor-pointer items-center gap-2 rounded-lg p-1.5 text-left text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        data-test="header-user-menu"
                    >
                        <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
                            <AvatarImage
                                v-if="user.avatar"
                                :src="user.avatar"
                                :alt="user.name"
                            />
                            <AvatarFallback
                                class="rounded-lg bg-neutral-200 text-xs font-semibold text-black dark:bg-neutral-700 dark:text-white"
                            >
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <span
                            class="hidden max-w-[140px] truncate text-sm font-medium text-foreground sm:inline sm:max-w-[180px]"
                        >
                            {{ user.name }}
                        </span>
                        <ChevronDown class="h-4 w-4 text-muted-foreground" />
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-56" :side-offset="4">
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>

    <BuyCreditsDialog v-if="user" />
</template>
