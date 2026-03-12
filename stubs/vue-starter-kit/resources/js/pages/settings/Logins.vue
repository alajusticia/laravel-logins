<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ConfirmPasswordDialog from '@/components/ConfirmPasswordDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { destroy, destroyAll, show } from '@/routes/logins';
import type { BreadcrumbItem } from '@/types';
import { Laptop, Smartphone, Tablet } from 'lucide-vue-next';

type Login = {
    id: number;
    label: string;
    device_type: string;
    device: string;
    platform: string;
    browser: string;
    ip_address: string;
    last_active: string;
    last_activity_at: string;
    created_at: string;
    is_current: boolean;
};

defineProps<{
    logins: Login[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Active sessions',
        href: show(),
    },
];

const loginIcon = (login: Login) => {
  const deviceType = login.device_type?.toLowerCase();

  if (deviceType === 'desktop') {
    return Laptop;
  }

  if (deviceType === 'tablet') {
    return Tablet;
  }

  return Smartphone;
};

const loginLabel = (login: Login) => login.label.trim() || 'Unknown device';
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Active sessions" />

        <h1 class="sr-only">Active sessions</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Active sessions"
                    description="Manage the devices signed in to your account"
                />

                <div
                    class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
                >
                    <div class="space-y-1 text-red-600 dark:text-red-100">
                        <p class="font-medium">Disconnect all other devices</p>
                        <p class="text-sm">
                          This will sign you out of your account on all other devices and browsers. Your current session will remain active.
                        </p>
                    </div>

                    <ConfirmPasswordDialog
                        :form="destroyAll.form()"
                        error-bag="disconnectAllLogins"
                        title="Are you sure?"
                        description="Enter your password to confirm you want to disconnect every active session from your account, except the current one."
                        trigger-label="Disconnect all other devices"
                        submit-label="Disconnect all other devices"
                        trigger-variant="destructive"
                        submit-variant="destructive"
                        trigger-data-test="disconnect-all-other-sessions-button"
                        submit-data-test="confirm-disconnect-all-other-sessions-button"
                    />
                </div>

                <div
                    v-if="logins.length === 0"
                    class="rounded-lg border border-border p-4 text-sm text-muted-foreground"
                >
                    No active sessions found.
                </div>

                <ul v-else class="space-y-3">
                    <li
                        v-for="login in logins"
                        :key="login.id"
                        class="space-y-4 rounded-lg border border-border p-4"
                    >
                        <div
                            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <component
                                        :is="loginIcon(login)"
                                        aria-hidden="true"
                                        class="size-4 text-muted-foreground"
                                    />
                                    <p class="text-sm font-medium">{{ loginLabel(login) }}</p>
                                    <Badge
                                        v-if="login.is_current"
                                        variant="secondary"
                                    >
                                        Current device
                                    </Badge>
                                </div>

                                <p class="text-sm text-muted-foreground">
                                    IP {{ login.ip_address }}<span v-if="!login.is_current"> - Last active {{ login.last_active }}</span>
                                </p>
                            </div>

                            <ConfirmPasswordDialog
                                :form="destroy.form(login.id)"
                                :error-bag="`disconnectLogin-${login.id}`"
                                title="Are you sure?"
                                description="Enter your password to confirm you want to disconnect this device from your account."
                                trigger-label="Log out"
                                submit-label="Disconnect device"
                                trigger-variant="outline"
                                trigger-size="sm"
                                submit-variant="destructive"
                                :trigger-data-test="`disconnect-device-button-${login.id}`"
                                :submit-data-test="`confirm-disconnect-device-button-${login.id}`"
                            />
                        </div>
                    </li>
                </ul>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
