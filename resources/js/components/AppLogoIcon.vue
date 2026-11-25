<script setup lang="ts">
import { Coffee } from 'lucide-vue-next';

defineProps<{ class?: string }>();
</script>

<template>
    <Coffee :class="$props.class" />
</template>
