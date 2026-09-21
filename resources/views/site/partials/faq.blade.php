{{-- Perguntas frequentes + schema FAQPage. Espera $faq = [['pergunta' => ..., 'resposta' => ...], ...] --}}
@php
    $faq = collect($faq ?? [])->filter(fn($item) => filled($item['pergunta'] ?? null) && filled($item['resposta'] ?? null))->values();
@endphp

@if($faq->isNotEmpty())
    <section id="{{ $id ?? 'perguntas' }}" class="py-20 md:py-28">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-12 lg:gap-16 lg:px-8">
            <div class="lg:col-span-4">
                <p class="font-mono text-xs uppercase tracking-[0.18em] text-buoy">Dúvidas frequentes</p>
                <h2 class="mt-4 text-balance text-4xl font-semibold tracking-[-0.03em] md:text-5xl">
                    {{ $titulo ?? 'Perguntas que sempre ouvimos.' }}
                </h2>
            </div>

            <div class="divide-y divide-navy/10 border-y border-navy/10 lg:col-span-8">
                @foreach($faq as $item)
                    <details class="group py-6" @if($loop->first) open @endif>
                        <summary class="flex cursor-pointer items-start justify-between gap-6 text-lg font-medium">
                            <h3>{{ $item['pergunta'] }}</h3>
                            <span class="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full ring-1 ring-navy/15 transition group-open:rotate-45" aria-hidden="true">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 12 12"><path d="M6 1v10M1 6h10"/></svg>
                            </span>
                        </summary>
                        <p class="mt-3 max-w-2xl leading-relaxed text-navy/65">{{ $item['resposta'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    @php
        // Montado em @php: dentro de {!! !!} o Blade interpretaria '@context' como diretiva
        $faqSchema = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faq->map(fn($item) => [
                '@type' => 'Question',
                'name' => $item['pergunta'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['resposta']],
            ])->all(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    @endphp
    @push('schema')
        <script type="application/ld+json">{!! $faqSchema !!}</script>
    @endpush
@endif
