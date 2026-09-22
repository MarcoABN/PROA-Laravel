<a href="{{ route('site.artigo', $artigo) }}"
    class="group flex flex-col rounded-2xl border border-navy/10 p-7 transition duration-300 hover:border-navy">
    <time datetime="{{ $artigo->publicado_em->toDateString() }}" class="font-mono text-xs text-navy/45">
        {{ $artigo->publicado_em->translatedFormat('d \d\e F \d\e Y') }}
    </time>
    <h3 class="mt-6 text-xl font-semibold leading-snug tracking-tight group-hover:text-navy-700">{{ $artigo->titulo }}</h3>
    <p class="mt-3 flex-1 leading-relaxed text-navy/60">{{ $artigo->resumo }}</p>
    <span class="mt-8 inline-flex items-center gap-2 text-sm font-medium text-buoy">
        Ler artigo
        <span class="transition group-hover:translate-x-1" aria-hidden="true">→</span>
    </span>
</a>
