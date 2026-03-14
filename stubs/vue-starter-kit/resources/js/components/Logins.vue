<script setup lang="ts">
import { onMounted, reactive, ref, useId } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Laptop, Smartphone, Tablet } from 'lucide-vue-next';

type Login = {
    id: number;
    label: string | null;
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

type FormErrors = {
    password?: string;
    general?: string;
};

type PasswordForm = {
    password: string;
    processing: boolean;
    errors: FormErrors;
};

const LOGINS_API_PATH = '/api/logins';
const csrfToken = document
    .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
    ?.getAttribute('content');

const logins = ref<Login[]>([]);
const isLoading = ref(true);
const loadError = ref<string | null>(null);
const selectedLogin = ref<Login | null>(null);
const showDisconnectAllDialog = ref(false);
const disconnectForm = reactive<PasswordForm>({
    password: '',
    processing: false,
    errors: {},
});
const disconnectAllForm = reactive<PasswordForm>({
    password: '',
    processing: false,
    errors: {},
});
const disconnectPasswordInputId = `disconnect-password-${useId()}`;
const disconnectAllPasswordInputId = `disconnect-all-password-${useId()}`;
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
const loginLabel = (login: Login) => login.label?.trim() || 'Unknown device';

const apiHeaders = (includeJsonBody = false): Record<string, string> => {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (includeJsonBody) {
        headers['Content-Type'] = 'application/json';
    }

    if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }

    return headers;
};

const normalizeErrors = (errors?: Record<string, string[]>): FormErrors => ({
    password: errors?.password?.[0],
});

const resetForm = (form: PasswordForm) => {
    form.password = '';
    form.errors = {};
};

const closeDisconnectDialog = () => {
    selectedLogin.value = null;
    resetForm(disconnectForm);
};

const closeDisconnectAllDialog = () => {
    showDisconnectAllDialog.value = false;
    resetForm(disconnectAllForm);
};

const openDisconnectDialog = (login: Login) => {
    selectedLogin.value = login;
    resetForm(disconnectForm);
};

const fetchLogins = async () => {
    isLoading.value = true;
    loadError.value = null;

    try {
        const response = await fetch(LOGINS_API_PATH, {
            method: 'GET',
            headers: apiHeaders(),
            credentials: 'same-origin',
        });

        if (! response.ok) {
            throw new Error('Unable to load active sessions.');
        }

        const payload = (await response.json()) as { data?: Login[] };

        logins.value = Array.isArray(payload.data) ? payload.data : [];
    } catch {
        loadError.value = 'Unable to load active sessions.';
    } finally {
        isLoading.value = false;
    }
};

const submitPasswordAction = async (
    path: string,
    form: PasswordForm,
    onSuccess: () => Promise<void> | void,
) => {
    if (form.processing) {
        return;
    }

    form.processing = true;
    form.errors = {};

    try {
        const response = await fetch(path, {
            method: 'DELETE',
            headers: apiHeaders(true),
            credentials: 'same-origin',
            body: JSON.stringify({
                password: form.password,
            }),
        });

        if (response.status === 204) {
            await onSuccess();

            return;
        }

        if (response.status === 422) {
            const payload = (await response.json()) as {
                errors?: Record<string, string[]>;
            };

            form.errors = normalizeErrors(payload.errors);

            return;
        }

        form.errors = {
            general: response.status === 401
                ? 'Your session has expired. Refresh the page and sign in again.'
                : 'Unable to update active sessions.',
        };
    } catch {
        form.errors = {
            general: 'Unable to update active sessions.',
        };
    } finally {
        form.processing = false;
    }
};

const submitDisconnect = async () => {
    if (! selectedLogin.value) {
        return;
    }

    const login = selectedLogin.value;

    await submitPasswordAction(
        `${LOGINS_API_PATH}/${login.id}`,
        disconnectForm,
        async () => {
            closeDisconnectDialog();

            if (login.is_current) {
                window.location.reload();

                return;
            }

            await fetchLogins();
        },
    );
};

const submitDisconnectAll = async () => {
    await submitPasswordAction(LOGINS_API_PATH, disconnectAllForm, async () => {
        closeDisconnectAllDialog();
        await fetchLogins();
    });
};

onMounted(() => {
    void fetchLogins();
});
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            title="Active sessions"
            description="Manage the devices signed in to your account"
        />

        <div
            v-if="isLoading"
            class="rounded-lg border border-border p-4 text-sm text-muted-foreground"
        >
            Loading active sessions...
        </div>

        <div
            v-else-if="loadError"
            class="rounded-lg border border-destructive/30 p-4 text-sm text-destructive"
        >
            {{ loadError }}
        </div>

        <div
            v-else-if="logins.length === 0"
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
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <component
                                :is="loginIcon(login)"
                                aria-hidden="true"
                                class="size-4 text-muted-foreground"
                            />
                            <p class="text-sm font-medium">{{ loginLabel(login) }}</p>
                            <Badge v-if="login.is_current" variant="secondary">
                                Current device
                            </Badge>
                        </div>

                        <p class="text-sm text-muted-foreground">
                            {{ login.ip_address || 'Unknown IP address' }}
                            <span v-if="!login.is_current"> - Last active {{ login.last_active }}</span>
                        </p>
                    </div>

                    <Dialog
                        :open="selectedLogin?.id === login.id"
                        @update:open="(open) => !open && closeDisconnectDialog()"
                    >
                        <DialogTrigger as-child>
                            <Button
                                variant="outline"
                                size="sm"
                                @click="openDisconnectDialog(login)"
                            >
                                Disconnect
                            </Button>
                        </DialogTrigger>

                        <DialogContent>
                            <form class="space-y-6" @submit.prevent="submitDisconnect">
                                <DialogHeader class="space-y-3">
                                    <DialogTitle>Disconnect this device?</DialogTitle>
                                    <DialogDescription>
                                        Enter your password to confirm you want to disconnect this device from your account.
                                    </DialogDescription>
                                </DialogHeader>

                                <div class="grid gap-2">
                                    <Label :for="`${disconnectPasswordInputId}-${login.id}`" class="sr-only">Password</Label>
                                    <Input
                                        :id="`${disconnectPasswordInputId}-${login.id}`"
                                        v-model="disconnectForm.password"
                                        type="password"
                                        name="password"
                                        placeholder="Password"
                                        autocomplete="current-password"
                                        required
                                    />
                                    <InputError :message="disconnectForm.errors.password" />
                                    <InputError :message="disconnectForm.errors.general" />
                                </div>

                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            @click="
                                                () => {
                                                    closeDisconnectDialog();
                                                }
                                            "
                                        >
                                            Cancel
                                        </Button>
                                    </DialogClose>

                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        :disabled="disconnectForm.processing"
                                    >
                                        Disconnect device
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            </li>
        </ul>

        <Dialog
            :open="showDisconnectAllDialog"
            @update:open="(open) => !open && closeDisconnectAllDialog()"
        >
            <DialogTrigger as-child>
                <Button variant="destructive" @click="showDisconnectAllDialog = true">
                    Disconnect all other devices
                </Button>
            </DialogTrigger>

            <DialogContent>
                <form class="space-y-6" @submit.prevent="submitDisconnectAll">
                    <DialogHeader class="space-y-3">
                        <DialogTitle>Disconnect all other devices?</DialogTitle>
                        <DialogDescription>
                            Enter your password to confirm you want to disconnect every active session from your account, except the current one.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label :for="disconnectAllPasswordInputId" class="sr-only">Password</Label>
                        <Input
                            :id="disconnectAllPasswordInputId"
                            v-model="disconnectAllForm.password"
                            type="password"
                            name="password"
                            placeholder="Password"
                            autocomplete="current-password"
                            required
                        />
                        <InputError :message="disconnectAllForm.errors.password" />
                        <InputError :message="disconnectAllForm.errors.general" />
                    </div>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button
                                type="button"
                                variant="secondary"
                                @click="
                                    () => {
                                        closeDisconnectAllDialog();
                                    }
                                "
                            >
                                Cancel
                            </Button>
                        </DialogClose>

                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="disconnectAllForm.processing"
                        >
                            Disconnect all other devices
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
