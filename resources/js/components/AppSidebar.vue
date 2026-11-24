<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BarChart3,
    Code2,
    ExternalLink,
    LayoutGrid,
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
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard, imageStudio } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import { index as reportsIndex } from '@/routes/admin/reports';

const { isCurrentUrl, currentUrl } = useCurrentUrl();

const isDashboardActive = computed(() => {
    return (
        currentUrl.value.startsWith('/dashboard') || isCurrentUrl(dashboard())
    );
});

const isStudioActive = computed(() => {
    return (
        currentUrl.value.startsWith('/image-studio') ||
        isCurrentUrl(imageStudio())
    );
});

const isSettingsActive = computed(() => {
    return currentUrl.value.startsWith('/settings');
});

const isReportsActive = computed(() => {
    return (
        currentUrl.value.startsWith('/admin/reports') ||
        isCurrentUrl(reportsIndex())
    );
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader class="border-b border-sidebar-border/70 pb-2">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
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
                            :is-active="isDashboardActive"
                            tooltip="Dashboard"
                        >
                            <Link :href="dashboard()">
                                <LayoutGrid class="h-4 w-4" />
                                <span>Dashboard</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            as-child
                            :is-active="isStudioActive"
                            tooltip="Image Studio"
                        >
                            <Link :href="imageStudio()">
                                <Wand2 class="h-4 w-4" />
                                <span>Image Studio</span>
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

            <!-- Reports Section -->
            <SidebarGroup>
                <SidebarGroupLabel>Reports</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            as-child
                            :is-active="isReportsActive"
                            tooltip="Admin Report"
                        >
                            <Link :href="reportsIndex()">
                                <BarChart3 class="h-4 w-4" />
                                <span>Admin Report</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter class="border-t border-sidebar-border/70 p-2">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child tooltip="GitHub Repository">
                        <a
                            href="https://github.com/iSemary/next-pixel"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Code2 class="h-4 w-4" />
                            <span
                                class="truncate group-data-[collapsible=icon]:hidden"
                                >GitHub Repository</span
                            >
                            <ExternalLink
                                class="ml-auto h-3 w-3 opacity-60 group-data-[collapsible=icon]:hidden"
                            />
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>

        <SidebarRail />
    </Sidebar>
    <slot />
</template>
