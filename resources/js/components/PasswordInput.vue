<script setup lang="ts">
import { Eye, EyeOff } from 'lucide-vue-next';
import { ref } from 'vue';
import Input from '@/components/ui/Input.vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    modelValue?: string;
    id?: string;
    name?: string;
    required?: boolean;
    autocomplete?: string;
    placeholder?: string;
    tabindex?: number;
    class?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const showPassword = ref(false);
</script>

<template>
    <div class="relative">
        <Input
            :id="id"
            :name="name"
            :type="showPassword ? 'text' : 'password'"
            :model-value="modelValue"
            @update:model-value="emit('update:modelValue', $event)"
            :required="required"
            :autocomplete="autocomplete"
            :placeholder="placeholder"
            :tabindex="tabindex"
            :class="cn('pr-10', props.class)"
        />
        <button
            type="button"
            @click="showPassword = !showPassword"
            class="absolute inset-y-0 right-0 flex items-center rounded-r-md px-3 text-muted-foreground hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring focus-visible:outline-none"
            :aria-label="showPassword ? 'Hide password' : 'Show password'"
            tabindex="-1"
        >
            <EyeOff v-if="showPassword" class="size-4" />
            <Eye v-else class="size-4" />
        </button>
    </div>
</template>
