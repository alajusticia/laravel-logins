<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, useId } from 'vue';
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
    ip_address: string | null;
    last_active: string;
    is_current: boolean;
    disconnect_url: string;
};

const props = defineProps<{
    logins: Login[];
    disconnectAllUrl: string;
}>();

const selectedLogin = ref<Login | null>(null);
const showDisconnectAllDialog = ref(false);
const disconnectForm = useForm({
    password: '',
});
const disconnectAllForm = useForm({
    password: '',
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
const selectedLoginUrl = computed(() => selectedLogin.value?.disconnect_url ?? '');

const closeDisconnectDialog = () => {
    selectedLogin.value = null;
    disconnectForm.reset();
    disconnectForm.clearErrors();
};

const closeDisconnectAllDialog = () => {
    showDisconnectAllDialog.value = false;
    disconnectAllForm.reset();
    disconnectAllForm.clearErrors();
};

const openDisconnectDialog = (login: Login) => {
    selectedLogin.value = login;
    disconnectForm.reset();
    disconnectForm.clearErrors();
};

const submitDisconnect = () => {
    if (!selectedLoginUrl.value) {
        return;
    }

    disconnectForm.delete(selectedLoginUrl.value, {
        preserveScroll: true,
        onSuccess: () => {
            closeDisconnectDialog();
        },
    });
};

const submitDisconnectAll = () => {
    disconnectAllForm.delete(props.disconnectAllUrl, {
        preserveScroll: true,
        onSuccess: () => {
            closeDisconnectAllDialog();
        },
    });
};
</script>

<template>
    <section class="space-y-6">
        <Heading
            variant="small"
            title="Active sessions"
            description="Manage the devices signed in to your account"
        />

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
    </section>
</template>
