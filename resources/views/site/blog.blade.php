@extends('site.layout')

@php
    $tituloPagina = 'Blog sobre habilitação e documentação náutica | Campeão Náutica';
    $descricaoPagina = 'Dicas e guias sobre Arrais Amador, Motonauta e documentação de embarcações junto à Marinha, escritos por quem atua há mais de 20 anos em Goiânia.';
@endphp

@section('title', $artigos->currentPage() > 1 ? $tituloPagina . ' | Página ' . $artigos->currentPage() : $tituloPagina)
@section('description', $descricaoPagina)
@section('og_title', $tituloPagina)
@section('og_description', $descricaoPagina)

@section('content')
    <header class="px-2 pt-[4.5rem] sm:px-3">
        <div class="relative overflow-hidden rounded-3xl bg-navy text-white">
            <div class="relative mx-auto max-w-7xl px-5 pb-14 pt-10 sm:px-8 md:pb-20 md:pt-14">
                <nav aria-label="Você está em" class="font-mono text-xs text-white/50">
                    <ol class="flex flex-wrap items-center gap-2">
                        <li><a href="{{ route('site.index') }}" class="hover:text-white">Início</a></li>
                        <li aria-hidden="true">/</li>
                        <li class="text-white/80" aria-current="page">Blog</li>
                    </ol>
                </nav>
                <h1 class="mt-10 max-w-3xl text-balance text-[2.5rem] font-semibold leading-[1.05] tracking-[-0.035em] sm:text-6xl">
                    Blog da Campeão Náutica
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-white/70">
                    Guias sobre habilitação náutica e documentação de embarcações, com o que aprendemos em mais de
                    20 anos atendendo junto à Marinha do Brasil.
                </p>
            </div>
        </div>
    </header>

    <section class="py-16 md:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse($artigos as $artigo)
                    @include('site.partials.card-artigo', ['artigo' => $artigo])
                @empty
                    <p class="col-span-full rounded-2xl border border-dashed border-navy/15 p-10 text-center text-navy/60">
                        Nenhum artigo publicado ainda.
                    </p>
                @endforelse
            </div>

            @if($artigos->hasPages())
                <nav class="mt-12 flex items-center justify-between text-sm font-medium" aria-label="Paginação">
                    @if($artigos->previousPageUrl())
                        <a href="{{ $artigos->previousPageUrl() }}" class="rounded-xl px-5 py-3 ring-1 ring-navy/15 hover:ring-navy">← Anteriores</a>
                    @else
                        <span></span>
                    @endif
                    <span class="font-mono text-xs text-navy/50">Página {{ $artigos->currentPage() }} de {{ $artigos->lastPage() }}</span>
                    @if($artigos->nextPageUrl())
                        <a href="{{ $artigos->nextPageUrl() }}" class="rounded-xl px-5 py-3 ring-1 ring-navy/15 hover:ring-navy">Mais artigos →</a>
                    @else
                        <span></span>
                    @endif
                </nav>
            @endif
        </div>
    </section>
@endsection
