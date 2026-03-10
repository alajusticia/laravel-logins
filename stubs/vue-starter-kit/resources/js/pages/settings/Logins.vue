<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ConfirmPasswordDialog from '@/components/ConfirmPasswordDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { destroy, destroyAll, show } from '@/routes/logins';
import type { BreadcrumbItem } from '@/types';

type Login = {
    id: number;
    label: string;
    device_type: string | null;
    device: string | null;
    platform: string | null;
    browser: string | null;
    ip_address: string | null;
    last_active: string;
    last_activity_at: string | null;
    created_at: string | null;
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

const loginContext = (login: Login): string => {
    const details = [
        login.device_type,
        login.device,
        login.platform,
        login.browser,
    ].filter((value): value is string => Boolean(value));

    if (details.length === 0) {
        return 'Unknown device details';
    }

    return details.join(' · ');
};
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
                    description="Review and disconnect active sessions on your account"
                />

                <div
                    class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
                >
                    <div class="space-y-1 text-red-600 dark:text-red-100">
                        <p class="font-medium">Disconnect all other sessions</p>
                        <p class="text-sm">
                            This signs out every active session / device, except the current one.
                        </p>
                    </div>

                    <ConfirmPasswordDialog
                        :form="destroyAll.form()"
                        error-bag="disconnectAllLogins"
                        title="Are you sure?"
                        description="Enter your password to confirm you want to disconnect every active session from your account, except the current one."
                        trigger-label="Disconnect all other sessions"
                        submit-label="Disconnect all other sessions"
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
                    No active logins found.
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
                                    <p class="font-medium">{{ login.label }}</p>
                                    <Badge
                                        v-if="login.is_current"
                                        variant="secondary"
                                    >
                                        Current device
                                    </Badge>
                                </div>

                                <p class="text-sm text-muted-foreground">
                                    {{ loginContext(login) }}
                                </p>

                                <p class="text-xs text-muted-foreground">
                                    <span v-if="login.ip_address"
                                        >IP {{ login.ip_address }} · </span
                                    >
                                    Last active {{ login.last_active }}
                                </p>
                            </div>

                            <ConfirmPasswordDialog
                                :form="destroy.form(login.id)"
                                :error-bag="`disconnectLogin-${login.id}`"
                                title="Are you sure?"
                                description="Enter your password to confirm you want to disconnect this device from your account."
                                trigger-label="Disconnect device"
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
