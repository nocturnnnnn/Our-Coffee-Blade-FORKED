<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import Button from '@/components/ui/Button.vue';
import Label from '@/components/ui/Label.vue';
import Spinner from '@/components/ui/Spinner.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/password/confirm';

const form = useForm({
    password: '',
});

function submit() {
    form.post(store().url, {
        onSuccess: () => form.reset('password'),
    });
}
</script>

<template>
    <AuthLayout
        title="Confirm password"
        description="This is a secure area of the application. Please confirm your password before continuing."
    >
        <Head title="Confirm password" />

        <form @submit.prevent="submit">
            <div class="space-y-6">
                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <PasswordInput
                        id="password"
                        v-model="form.password"
                        name="password"
                        placeholder="Password"
                        autocomplete="current-password"
                        autofocus
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center">
                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="form.processing"
                        data-test="confirm-password-button"
                    >
                        <Spinner v-if="form.processing" />
                        Confirm password
                    </Button>
                </div>
            </div>
        </form>
    </AuthLayout>
</template>
