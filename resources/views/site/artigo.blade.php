@extends('site.layout')

@php
    $site = config('site');
    $tituloPagina = $artigo->titulo_seo ?: $artigo->titulo . ' | Campeão Náutica';
    $descricaoPagina = $artigo->meta_descricao ?: \Illuminate\Support\Str::limit(strip_tags($artigo->resumo), 155);

    // Montado em @php: dentro de {!! !!} o Blade interpretaria '@context' como diretiva
    $artigoSchema = json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'BlogPosting',
                'headline' => $artigo->titulo,
                'description' => $descricaoPagina,
                'url' => route('site.artigo', $artigo),
                'mainEntityOfPage' => route('site.artigo', $artigo),
                'datePublished' => $artigo->publicado_em->toIso8601String(),
                'dateModified' => ($artigo->updated_at ?? $artigo->publicado_em)->toIso8601String(),
                'image' => asset('images/logo_campeao.jpg'),
                'inLanguage' => 'pt-BR',
                'author' => ['@type' => 'Organization', 'name' => $site['nome'], 'url' => $site['url']],
                'publisher' => ['@id' => $site['url']],
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início', 'item' => route('site.index')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('site.blog')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $artigo->titulo, 'item' => route('site.artigo', $artigo)],
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
@endphp

@section('title', $tituloPagina)
@section('description', $descricaoPagina)
@section('og_title', $tituloPagina)
@section('og_description', $descricaoPagina)

@push('schema')
    <script type="application/ld+json">{!! $artigoSchema !!}</script>
@endpush

@section('content')
    <header class="px-2 pt-[4.5rem] sm:px-3">
        <div class="relative overflow-hidden rounded-3xl bg-navy text-white">
            <div class="relative mx-auto max-w-4xl px-5 pb-14 pt-10 sm:px-8 md:pb-20 md:pt-14">
                <nav aria-label="Você está em" class="font-mono text-xs text-white/50">
                    <ol class="flex flex-wrap items-center gap-2">
                        <li><a href="{{ route('site.index') }}" class="hover:text-white">Início</a></li>
                        <li aria-hidden="true">/</li>
                        <li><a href="{{ route('site.blog') }}" class="hover:text-white">Blog</a></li>
                    </ol>
                </nav>
                <h1 class="mt-10 text-balance text-4xl font-semibold leading-[1.08] tracking-[-0.03em] sm:text-5xl">
                    {{ $artigo->titulo }}
                </h1>
                <p class="mt-6 text-lg leading-relaxed text-white/70">{{ $artigo->resumo }}</p>
                <p class="mt-8 font-mono text-xs text-white/50">
                    Campeão Náutica ·
                    <time datetime="{{ $artigo->publicado_em->toDateString() }}">{{ $artigo->publicado_em->translatedFormat('d \d\e F \d\e Y') }}</time>
                </p>
            </div>
        </div>
    </header>

    <section class="py-16 md:py-24">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-12 lg:gap-16 lg:px-8">
            <article class="lg:col-span-8">
                <div class="prose prose-lg max-w-none prose-headings:font-semibold prose-headings:tracking-tight prose-headings:text-navy prose-p:text-navy/75 prose-li:text-navy/75 prose-a:text-buoy prose-strong:text-navy">
                    {!! $artigo->conteudo !!}
                </div>

                <div class="mt-12 rounded-2xl bg-navy p-7 text-white sm:p-8">
                    <p class="text-xl font-semibold tracking-tight">Quer ajuda com o seu caso?</p>
                    <p class="mt-2 text-white/70">Fale com a gente pelo WhatsApp. Informamos os documentos e cuidamos do processo junto à Marinha.</p>
                    <a href="{{ $site['whatsapp'] }}?text={{ rawurlencode('Olá! Li o artigo "' . $artigo->titulo . '" e gostaria de ajuda.') }}"
                        target="_blank" rel="noopener"
                        class="mt-6 inline-flex items-center gap-2.5 rounded-xl bg-buoy px-6 py-3.5 font-medium transition hover:bg-buoy-dark">
                        @include('site.partials.icone-whatsapp')
                        Falar no WhatsApp
                    </a>
                </div>
            </article>

            <aside class="lg:col-span-4">
                <div class="space-y-6 lg:sticky lg:top-24">
                    <div class="rounded-2xl bg-mist p-2">
                        @include('site.partials.atendimento')
                    </div>
                    @if($servicos->isNotEmpty())
                        <div class="rounded-2xl ring-1 ring-navy/10 p-6">
                            <p class="font-mono text-xs uppercase tracking-wider text-navy/45">Serviços</p>
                            <ul class="mt-4 space-y-1 text-sm">
                                @foreach($servicos as $s)
                                    <li>
                                        <a href="{{ route('site.servico', $s) }}" class="-mx-2 flex items-center justify-between rounded-lg px-2 py-2 hover:bg-mist">
                                            {{ $s->nome }}
                                            <span class="text-buoy" aria-hidden="true">→</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    </section>

    @include('site.partials.faq', [
        'faq' => $artigo->faq ?? [],
        'id' => 'perguntas',
        'titulo' => 'Perguntas frequentes',
    ])

    @if($outros->isNotEmpty())
        <section class="px-2 pb-2 sm:px-3 sm:pb-3">
            <div class="rounded-3xl bg-mist">
                <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 md:py-20">
                    <h2 class="text-2xl font-semibold tracking-tight md:text-3xl">Leia também</h2>
                    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($outros as $outro)
                            <div class="rounded-2xl bg-white">
                                @include('site.partials.card-artigo', ['artigo' => $outro])
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection
