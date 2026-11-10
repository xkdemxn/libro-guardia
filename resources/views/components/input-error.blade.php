@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'mt-1.5 space-y-1 text-[13px] text-rose-400']) }}>
        @foreach ((array) $messages as $message)
            <li class="flex items-start gap-1.5">
                <span class="punto mt-1.5 bg-rose-400"></span>
                <span>{{ $message }}</span>
            </li>
        @endforeach
    </ul>
@endif
