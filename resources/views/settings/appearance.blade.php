<x-layouts.settings title="Tampilan">
    <section x-data class="rounded-2xl border border-border bg-card p-6">
        <div class="mb-5">
            <h2 class="text-base font-semibold">Preferensi Tampilan</h2>
            <p class="text-sm text-muted-foreground">Pilih tema antarmuka untuk akun dan perangkat Anda.</p>
        </div>

        <div class="inline-flex gap-1.5 rounded-xl border border-border bg-muted p-1">
            @foreach ([
                ['value' => 'light', 'label' => 'Light', 'icon' => 'sun'],
                ['value' => 'dark', 'label' => 'Dark', 'icon' => 'moon'],
                ['value' => 'system', 'label' => 'System', 'icon' => 'monitor'],
            ] as $opt)
                <button
                    type="button"
                    @click="$store.theme.set('{{ $opt['value'] }}')"
                    :class="$store.theme.mode === '{{ $opt['value'] }}'
                        ? 'bg-card text-foreground shadow-xs'
                        : 'text-muted-foreground hover:text-foreground'"
                    class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium transition-colors"
                >
                    <x-icon :name="$opt['icon']" class="size-4" />
                    <span>{{ $opt['label'] }}</span>
                </button>
            @endforeach
        </div>
    </section>
</x-layouts.settings>
