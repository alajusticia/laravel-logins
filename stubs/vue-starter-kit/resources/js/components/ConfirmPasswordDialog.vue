<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref, useId, useTemplateRef } from 'vue';
import type { ButtonVariants } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
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

type Props = {
    form: Record<string, unknown>;
    title: string;
    description: string;
    triggerLabel: string;
    submitLabel: string;
    errorBag?: string;
    triggerVariant?: ButtonVariants['variant'];
    triggerSize?: ButtonVariants['size'];
    submitVariant?: ButtonVariants['variant'];
    submitSize?: ButtonVariants['size'];
    triggerDataTest?: string;
    submitDataTest?: string;
};

withDefaults(defineProps<Props>(), {
    errorBag: undefined,
    triggerVariant: 'default',
    triggerSize: 'default',
    submitVariant: 'default',
    submitSize: 'default',
    triggerDataTest: undefined,
    submitDataTest: undefined,
});

const isOpen = ref(false);
const passwordInputId = `password-${useId()}`;
const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <Dialog :open="isOpen" @update:open="isOpen = $event">
        <DialogTrigger as-child>
            <Button
                :variant="triggerVariant"
                :size="triggerSize"
                :data-test="triggerDataTest"
            >
                {{ triggerLabel }}
            </Button>
        </DialogTrigger>

        <DialogContent>
            <Form
                v-bind="form"
                :error-bag="errorBag"
                reset-on-success
                @error="() => passwordInput?.$el?.focus()"
                @success="isOpen = false"
                :options="{
                    preserveScroll: true,
                }"
                class="space-y-6"
                v-slot="{ errors, processing, reset, clearErrors }"
            >
                <DialogHeader class="space-y-3">
                    <DialogTitle>{{ title }}</DialogTitle>
                    <DialogDescription>{{ description }}</DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label :for="passwordInputId" class="sr-only"
                        >Password</Label
                    >
                    <Input
                        :id="passwordInputId"
                        type="password"
                        name="password"
                        ref="passwordInput"
                        placeholder="Password"
                        autocomplete="current-password"
                        required
                    />
                    <InputError :message="errors.password" />
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button
                            type="button"
                            variant="secondary"
                            @click="
                                () => {
                                    clearErrors();
                                    reset();
                                }
                            "
                        >
                            Cancel
                        </Button>
                    </DialogClose>

                    <Button
                        type="submit"
                        :variant="submitVariant"
                        :size="submitSize"
                        :disabled="processing"
                        :data-test="submitDataTest"
                    >
                        {{ submitLabel }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
