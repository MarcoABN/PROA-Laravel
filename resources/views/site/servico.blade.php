@extends('site.layout')

@php
    $site = config('site');
    $tituloPagina = $servico->titulo_seo ?: $servico->nome . ' em Goiânia | Campeão Náutica';
    $descricaoPagina = $servico->meta_descricao ?: \Illuminate\Support\Str::limit(strip_tags($servico->descricao), 155);
    $whatsappServico = $site['whatsapp'] . '?text=' . rawurlencode('Olá! Gostaria de um orçamento para: ' . $servico->nome);
    $outros = $servicos->where('id', '!=', $servico->id);
@endphp

@section('title', $tituloPagina)
@section('description', $descricaoPagina)
@section('og_title', $tituloPagina)
@section('og_description', $descricaoPagina)

@php
    // Montado em @php: dentro de {!! !!} o Blade interpretaria '@context' como diretiva
    $servicoSchema = json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Service',
                'name' => $servico->nome,
                'description' => $descricaoPagina,
                'url' => route('site.servico', $servico),
                'provider' => ['@id' => $site['url']],
                'areaServed' => [
                    ['@type' => 'State', 'name' => 'Goiás'],
                    ['@type' => 'Country', 'name' => 'Brasil'],
                ],
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início', 'item' => route('site.index')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Serviços', 'item' => route('site.index') . '#servicos'],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $servico->nome, 'item' => route('site.servico', $servico)],
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
@endphp

@push('schema')
    <script type="application/ld+json">{!! $servicoSchema !!}</script>
@endpush

@section('content')
    {{-- Cabeçalho do serviço --}}
    <header class="px-2 pt-[4.5rem] sm:px-3">
        <div class="relative overflow-hidden rounded-3xl bg-navy text-white">
            <svg class="pointer-events-none absolute inset-0 h-full w-full opacity-[0.09]" preserveAspectRatio="none"
                viewBox="0 0 1200 500" fill="none" stroke="white" stroke-width="1" aria-hidden="true">
                <path d="M700 -20 C 760 80, 920 110, 980 210 S 1120 330, 1220 300"/>
                <path d="M760 -20 C 820 60, 960 90, 1020 180 S 1140 280, 1220 250"/>
                <path d="M820 -20 C 880 40, 1000 70, 1060 150 S 1160 230, 1220 200"/>
                <path d="M880 -20 C 940 20, 1040 50, 1100 120 S 1180 180, 1220 150"/>
                <path d="M-20 420 C 200 380, 330 460, 540 410 S 880 330, 1220 390"/>
                <path d="M-20 460 C 220 420, 340 500, 560 450 S 900 370, 1220 430"/>
            </svg>

            <div class="relative mx-auto max-w-7xl px-5 pb-14 pt-10 sm:px-8 md:pb-20 md:pt-14">
                <nav aria-label="Você está em" class="font-mono text-xs text-white/50">
                    <ol class="flex flex-wrap items-center gap-2">
                        <li><a href="{{ route('site.index') }}" class="hover:text-white">Início</a></li>
                        <li aria-hidden="true">/</li>
                        <li><a href="{{ route('site.index') }}#servicos" class="hover:text-white">Serviços</a></li>
                        <li aria-hidden="true">/</li>
                        <li class="text-white/80" aria-current="page">{{ $servico->nome }}</li>
                    </ol>
                </nav>

                <h1 class="mt-10 max-w-4xl text-balance text-[2.5rem] font-semibold leading-[1.05] tracking-[-0.035em] sm:text-6xl">
                    {{ $servico->nome }}
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-white/70">{{ $servico->descricao }}</p>

                <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ $whatsappServico }}" target="_blank" rel="noopener"
                        class="inline-flex items-center justify-center gap-2.5 rounded-xl bg-buoy px-6 py-3.5 font-medium text-white transition hover:bg-buoy-dark">
                        @include('site.partials.icone-whatsapp')
                        Pedir orçamento pelo WhatsApp
                    </a>
                    <a href="tel:{{ $site['telefone2'] }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl px-6 py-3.5 font-medium text-white ring-1 ring-inset ring-white/20 transition hover:bg-white/10">
                        Ligar {{ $site['telefone2_exibicao'] }}
                    </a>
                </div>
            </div>
        </div>
    </header>

    {{-- Conteúdo --}}
    <section class="py-16 md:py-24">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-12 lg:gap-16 lg:px-8">
            <article class="lg:col-span-8">
                @if(filled($servico->conteudo))
                    <div class="prose prose-lg max-w-none prose-headings:font-semibold prose-headings:tracking-tight prose-headings:text-navy prose-p:text-navy/75 prose-li:text-navy/75 prose-a:text-buoy prose-strong:text-navy">
                        {!! $servico->conteudo !!}
                    </div>
                @else
                    <h2 class="text-3xl font-semibold tracking-tight">Como podemos ajudar</h2>
                    <p class="mt-5 text-lg leading-relaxed text-navy/70">
                        Cuidamos de todo o processo, do começo ao fim: conferimos os documentos, damos entrada junto à Marinha do Brasil e acompanhamos o processo até a liberação.
                        Atendemos em Goiânia, com alunos de todo o estado de Goiás e regularização de embarcações em
                        qualquer estado do país.
                    </p>
                    <p class="mt-4 text-lg leading-relaxed text-navy/70">
                        Mande uma mensagem contando o seu caso e informamos a lista de documentos e o prazo estimado.
                    </p>
                @endif
            </article>

            <aside class="lg:col-span-4">
                <div class="lg:sticky lg:top-24">
                    <div class="rounded-2xl bg-mist p-2">
                        @include('site.partials.atendimento')
                    </div>
                </div>
            </aside>
        </div>
    </section>

    @include('site.partials.como-funciona')

    @include('site.partials.faq', [
        'faq' => $servico->faq ?? [],
        'id' => 'perguntas',
        'titulo' => 'Dúvidas sobre ' . $servico->nome,
    ])

    {{-- Outros serviços --}}
    @if($outros->isNotEmpty())
        <section class="px-2 pb-2 sm:px-3 sm:pb-3">
            <div class="rounded-3xl bg-mist">
                <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 md:py-20">
                    <h2 class="text-2xl font-semibold tracking-tight md:text-3xl">Outros serviços</h2>
                    <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($outros as $outro)
                            <a href="{{ route('site.servico', $outro) }}"
                                class="group flex items-center justify-between gap-4 rounded-xl bg-white px-5 py-4 font-medium ring-1 ring-navy/10 transition hover:ring-navy">
                                {{ $outro->nome }}
                                <span class="text-buoy transition group-hover:translate-x-1" aria-hidden="true">→</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection
