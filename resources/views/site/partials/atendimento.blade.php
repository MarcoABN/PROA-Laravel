@php
    $site = config('site');
    $mapa = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($site['endereco']['mapa_busca']);
@endphp
<div class="rounded-xl bg-white p-6 text-navy">
    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
        <h2 class="font-semibold">Atendimento</h2>
        <span class="inline-flex items-center gap-1.5 font-mono text-xs text-navy/60">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
            Seg–Sex · 8h–18h
        </span>
    </div>
    <ul class="mt-5 space-y-1 text-sm">
        <li>
            <a href="{{ $site['whatsapp'] }}" target="_blank" rel="noopener" class="-mx-2 flex flex-wrap items-center justify-between gap-x-4 rounded-lg px-2 py-2.5 hover:bg-mist">
                <span class="text-navy/60">WhatsApp</span>
                <span class="font-mono font-medium">{{ $site['whatsapp_exibicao'] }}</span>
            </a>
        </li>
        <li>
            <a href="tel:{{ $site['telefone2'] }}" class="-mx-2 flex flex-wrap items-center justify-between gap-x-4 rounded-lg px-2 py-2.5 hover:bg-mist">
                <span class="text-navy/60">Telefone</span>
                <span class="font-mono font-medium">{{ $site['telefone2_exibicao'] }}</span>
            </a>
        </li>
        <li>
            <a href="mailto:{{ $site['email'] }}" class="-mx-2 flex flex-wrap items-center justify-between gap-x-4 rounded-lg px-2 py-2.5 hover:bg-mist">
                <span class="text-navy/60">E-mail</span>
                <span class="break-all font-medium">{{ $site['email'] }}</span>
            </a>
        </li>
    </ul>
    <a href="{{ $mapa }}" target="_blank" rel="noopener"
        class="mt-4 flex items-start gap-3 rounded-xl bg-mist p-4 text-sm transition hover:bg-navy/[0.07]">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-buoy" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a7 7 0 00-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 00-7-7zm0 9.5A2.5 2.5 0 1112 6.5a2.5 2.5 0 010 5z"/></svg>
        <span>
            {{ str_replace('Avenida', 'Av.', $site['endereco']['rua']) }} — Aeroviário<br>
            <span class="text-navy/60">Goiânia, GO · Ver no mapa</span>
        </span>
    </a>
</div>
