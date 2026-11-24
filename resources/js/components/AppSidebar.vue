<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    Code2,
    ExternalLink,
    Layers,
    Plus,
    Settings,
    Wand2,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useGenerationStudio } from '@/composables/useGenerationStudio';
import { dashboard, library } from '@/routes';
import { edit as editProfile } from '@/routes/profile';

const { isCurrentUrl, currentUrl } = useCurrentUrl();
const { startNewGeneration } = useGenerationStudio();

const isStudioActive = computed(() => {
    return currentUrl.value === '/dashboard' || isCurrentUrl(dashboard());
});

const isLibraryActive = computed(() => {
    return currentUrl.value.startsWith('/library') || isCurrentUrl(library());
});

const isSettingsActive = computed(() => {
    return currentUrl.value.startsWith('/settings');
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader class="border-b border-sidebar-border/70 pb-3">
            <SidebarMenu>
                <SidebarMenuItem class="flex items-center justify-between">
                    <SidebarMenuButton size="lg" as-child class="flex-1">
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                    <SidebarTrigger
                        class="ml-1 hidden shrink-0 group-data-[state=expanded]:flex"
                    />
                </SidebarMenuItem>
            </SidebarMenu>

            <!-- Prominent "New generation" Button -->
            <SidebarMenu class="mt-2">
                <SidebarMenuItem>
                    <SidebarMenuButton
                        tooltip="New generation"
                        class="h-9 w-full cursor-pointer justify-center rounded-lg bg-primary font-semibold text-primary-foreground shadow-xs transition-all group-data-[collapsible=icon]:p-0 group-data-[state=expanded]:justify-start group-data-[state=expanded]:px-3 hover:bg-primary/90 hover:text-primary-foreground"
                        @click="startNewGeneration"
                    >
                        <Plus class="h-4 w-4 shrink-0 stroke-[2.5]" />
                        <span class="truncate">New generation</span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <!-- Workspace Section -->
            <SidebarGroup>
                <SidebarGroupLabel>Workspace</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            as-child
                            :is-active="isStudioActive"
                            tooltip="Image Studio"
                        >
                            <Link :href="dashboard()">
                                <Wand2 class="h-4 w-4" />
                                <span>Image Studio</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            as-child
                            :is-active="isLibraryActive"
                            tooltip="Library"
                        >
                            <Link :href="library()">
                                <Layers class="h-4 w-4" />
                                <span>Library</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <!-- Account Section -->
            <SidebarGroup>
                <SidebarGroupLabel>Account</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            as-child
                            :is-active="isSettingsActive"
                            tooltip="Settings"
                        >
                            <Link :href="editProfile()">
                                <Settings class="h-4 w-4" />
                                <span>Settings</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter class="border-t border-sidebar-border/70 p-2">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child tooltip="API Documentation">
                        <a
                            href="https://pixelforge.promptedit.dev/docs"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <BookOpen class="h-4 w-4" />
                            <span>API Documentation</span>
                            <ExternalLink class="ml-auto h-3 w-3 opacity-60" />
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child tooltip="GitHub Repository">
                        <a
                            href="https://github.com/iSemary/next-pixel"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Code2 class="h-4 w-4" />
                            <span>GitHub Repository</span>
                            <ExternalLink class="ml-auto h-3 w-3 opacity-60" />
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>

        <SidebarRail />
    </Sidebar>
    <slot />
</template>
