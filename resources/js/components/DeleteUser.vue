<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import Button from '@/components/ui/Button.vue';
import Label from '@/components/ui/Label.vue';

const open = ref(false);
const form = useForm({
    password: '',
});

function closeModal() {
    open.value = false;
    form.reset();
    form.clearErrors();
}

function submit() {
    form.delete(ProfileController.destroy.url(), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
}
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            title="Delete account"
            description="Delete your account and all of its resources"
        />
        <div class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">Warning</p>
                <p class="text-sm">
                    Please proceed with caution, this cannot be undone.
                </p>
            </div>

            <Button
                variant="destructive"
                data-test="delete-user-button"
                @click="open = true"
            >
                Delete account
            </Button>

            <Teleport to="body">
                <div
                    v-if="open"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                >
                    <div class="w-full max-w-md rounded-xl border border-border bg-background p-6 shadow-xl">
                        <h3 class="text-lg font-semibold text-foreground">
                            Are you sure you want to delete your account?
                        </h3>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.
                        </p>

                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            <div class="grid gap-2">
                                <Label for="delete_password" class="sr-only">Password</Label>
                                <PasswordInput
                                    id="delete_password"
                                    v-model="form.password"
                                    name="password"
                                    placeholder="Password"
                                    autocomplete="current-password"
                                />
                                <InputError :message="form.errors.password" />
                            </div>

                            <div class="flex justify-end gap-2">
                                <Button variant="secondary" @click="closeModal">
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    :disabled="form.processing"
                                    data-test="confirm-delete-user-button"
                                >
                                    Delete account
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>
        </div>
    </div>
</template>
