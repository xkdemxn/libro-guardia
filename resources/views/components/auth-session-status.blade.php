@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'flex items-center gap-2 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm font-medium text-emerald-300']) }}>
        <span class="punto bg-emerald-400"></span>{{ $status }}
    </div>
@endif
