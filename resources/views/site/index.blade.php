@extends('site.layout')

@section('title', 'Campeão Náutica | Despachante Marítimo e Escola Naval em Goiânia')
@section('description', '⚓Campeão Náutica: Arrais, Motonauta e Regularização. Atendemos todo o Brasil. +20 anos de tradição, simulado online e recursos. Fale conosco: (62) 99859-9357.')
@section('keywords', 'despachante náutico, escola naval, arrais amador, motonauta, marinha do brasil, regularização de barcos, goiânia')
@section('og_title', 'Campeão Náutica | Despachante Marítimo e Escola Naval')
@section('og_description', 'Regularize sua embarcação ou tire sua habilitação com especialistas. Mais de 20 anos de tradição.')

@section('content')
    @php
        $site = config('site');
        $mapa = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($site['endereco']['mapa_busca']);
        $mapaEmbed = 'https://www.google.com/maps?q=' . urlencode($site['endereco']['mapa_busca']) . '&output=embed';
    @endphp

    {{-- Hero --}}
    <header class="px-2 pt-[4.5rem] sm:px-3">
        <div class="relative overflow-hidden rounded-3xl bg-navy text-white">
            {{-- Linhas de profundidade, como numa carta náutica --}}
            <svg class="pointer-events-none absolute inset-0 h-full w-full opacity-[0.09]" preserveAspectRatio="none"
                viewBox="0 0 1200 700" fill="none" stroke="white" stroke-width="1" aria-hidden="true">
                <path d="M-20 520 C 180 470, 320 560, 520 500 S 860 400, 1220 470"/>
                <path d="M-20 560 C 200 510, 330 600, 540 540 S 880 440, 1220 510"/>
                <path d="M-20 600 C 220 550, 340 640, 560 580 S 900 480, 1220 550"/>
                <path d="M-20 640 C 240 590, 350 680, 580 620 S 920 520, 1220 590"/>
                <path d="M-20 680 C 260 630, 360 720, 600 660 S 940 560, 1220 630"/>
                <path d="M700 -20 C 760 80, 920 110, 980 210 S 1120 330, 1220 300"/>
                <path d="M760 -20 C 820 60, 960 90, 1020 180 S 1140 280, 1220 250"/>
                <path d="M820 -20 C 880 40, 1000 70, 1060 150 S 1160 230, 1220 200"/>
                <path d="M880 -20 C 940 20, 1040 50, 1100 120 S 1180 180, 1220 150"/>
            </svg>
            <div class="pointer-events-none absolute -right-32 -top-32 h-[28rem] w-[28rem] rounded-full bg-navy-700/60 blur-3xl" aria-hidden="true"></div>

            <div class="relative mx-auto grid max-w-7xl gap-12 px-5 pb-14 pt-16 sm:px-8 md:pt-24 lg:grid-cols-12 lg:gap-10 lg:pb-20">
                <div class="lg:col-span-7">
                    <h1 class="text-[2.5rem] font-semibold leading-[1.05] tracking-[-0.035em] sm:text-6xl lg:text-[4.25rem]">
                        Despachante náutico e escola de navegação.
                        <span class="text-white/45">Documentos em dia, habilitação na mão.</span>
                    </h1>
                    <p class="mt-7 max-w-xl text-lg leading-relaxed text-white/70">
                        Há mais de 20 anos no mercado. Habilitação de Arrais Amador e Motonauta em Goiás e
                        regularização de embarcações em todo o Brasil.
                    </p>
                    <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ $site['whatsapp'] }}" target="_blank" rel="noopener"
                            class="inline-flex items-center justify-center gap-2.5 rounded-xl bg-buoy px-6 py-3.5 font-medium text-white transition hover:bg-buoy-dark">
                            @include('site.partials.icone-whatsapp')
                            WhatsApp (62) 99859-9357
                        </a>
                        <a href="/login"
                            class="inline-flex items-center justify-center gap-2 rounded-xl px-6 py-3.5 font-medium text-white ring-1 ring-inset ring-white/20 transition hover:bg-white/10">
                            Simulador de provas
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>

                {{-- Cartão de atendimento --}}
                <aside class="self-end lg:col-span-5 lg:pl-6">
                    <div class="rounded-2xl bg-white/[0.06] p-2 ring-1 ring-inset ring-white/10 backdrop-blur">
                        @include('site.partials.atendimento')
                    </div>
                </aside>
            </div>

            {{-- Faixa de destaques --}}
            <div class="relative border-t border-white/10">
                <dl class="mx-auto grid max-w-7xl grid-cols-2 px-5 sm:px-8 lg:grid-cols-4">
                    <div class="border-white/10 py-6 pr-4 lg:border-r">
                        <dt class="font-mono text-xs uppercase tracking-wider text-white/45">Tradição</dt>
                        <dd class="mt-1.5 font-medium">Mais de 20 anos</dd>
                    </div>
                    <div class="border-white/10 py-6 pl-4 max-lg:border-l lg:border-r lg:px-6">
                        <dt class="font-mono text-xs uppercase tracking-wider text-white/45">Habilitação</dt>
                        <dd class="mt-1.5 font-medium">Arrais e Motonauta em todo GO</dd>
                    </div>
                    <div class="border-white/10 py-6 pr-4 max-lg:border-t lg:border-r lg:px-6">
                        <dt class="font-mono text-xs uppercase tracking-wider text-white/45">Alcance</dt>
                        <dd class="mt-1.5 font-medium">Regularização em todo o Brasil</dd>
                    </div>
                    <div class="border-white/10 py-6 pl-4 max-lg:border-l max-lg:border-t lg:px-6">
                        <dt class="font-mono text-xs uppercase tracking-wider text-white/45">Alunos</dt>
                        <dd class="mt-1.5 font-medium">Simulado online</dd>
                    </div>
                </dl>
            </div>
        </div>
    </header>

    {{-- Serviços --}}
    <section id="servicos" class="py-20 md:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                <div class="max-w-2xl">
                    <p class="font-mono text-xs uppercase tracking-[0.18em] text-buoy">Serviços</p>
                    <h2 class="mt-4 text-balance text-4xl font-semibold tracking-[-0.03em] md:text-5xl">
                        Tudo o que a Marinha pede, a gente resolve.
                    </h2>
                </div>
                <p class="max-w-sm text-navy/60 md:text-right">
                    Não achou o seu caso? Chame no WhatsApp — provavelmente já resolvemos algo parecido.
                </p>
            </div>

            <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse($servicos as $servico)
                    <a href="{{ route('site.servico', $servico) }}"
                        class="group flex flex-col rounded-2xl border border-navy/10 p-7 transition duration-300 hover:border-navy hover:bg-navy hover:text-white">
                        <span class="font-mono text-xs text-navy/40 transition group-hover:text-white/50">
                            {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <h3 class="mt-10 text-xl font-semibold tracking-tight">{{ $servico->nome }}</h3>
                        <p class="mt-3 flex-1 leading-relaxed text-navy/60 transition group-hover:text-white/70">{{ $servico->descricao }}</p>
                        <span class="mt-8 inline-flex items-center gap-2 text-sm font-medium text-buoy">
                            Saiba mais
                            <span class="transition group-hover:translate-x-1" aria-hidden="true">→</span>
                        </span>
                    </a>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-navy/15 p-10 text-center text-navy/60">
                        Nenhum serviço cadastrado no momento. Fale com a gente pelo
                        <a href="{{ $site['whatsapp'] }}" class="font-medium text-buoy">WhatsApp</a>.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    @include('site.partials.como-funciona')

    {{-- Simulado --}}
    <section id="simulado" class="py-20 md:py-28">
        <div class="mx-auto grid max-w-7xl items-center gap-14 px-4 sm:px-6 lg:grid-cols-2 lg:gap-20 lg:px-8">
            <div>
                <p class="font-mono text-xs uppercase tracking-[0.18em] text-buoy">Simulado online</p>
                <h2 class="mt-4 text-balance text-4xl font-semibold tracking-[-0.03em] md:text-5xl">
                    Chegue na prova já sabendo como ela é.
                </h2>
                <p class="mt-6 max-w-lg text-lg leading-relaxed text-navy/60">
                    Nossos alunos de Arrais Amador e Motonauta treinam em um simulado com questões no formato
                    da prova da Marinha. É só entrar na Área do Cliente com o seu CPF.
                </p>
                <a href="/login" class="mt-9 inline-flex items-center gap-2 rounded-xl bg-navy px-6 py-3.5 font-medium text-white transition hover:bg-navy-700">
                    Acessar o simulado
                    <span aria-hidden="true">→</span>
                </a>
            </div>

            {{-- Prévia ilustrativa do simulado --}}
            <div class="relative" aria-hidden="true">
                <div class="absolute -inset-3 -z-10 rounded-[1.75rem] bg-gradient-to-br from-mist to-white"></div>
                <div class="rounded-2xl border border-navy/10 bg-white p-6 shadow-xl shadow-navy/[0.06] sm:p-8">
                    <div class="flex items-center justify-between font-mono text-xs text-navy/50">
                        <span>ARRAIS AMADOR · QUESTÃO 12/40</span>
                        <span>18:42</span>
                    </div>
                    <div class="mt-3 h-1 overflow-hidden rounded-full bg-mist">
                        <div class="h-full w-[30%] rounded-full bg-buoy"></div>
                    </div>
                    <p class="mt-7 text-lg font-medium leading-snug">
                        Duas embarcações a motor navegam em rumos que se cruzam, com risco de colisão.
                        Qual delas deve se manter fora do caminho da outra?
                    </p>
                    <div class="mt-6 space-y-2.5 text-sm">
                        <div class="flex items-center gap-3 rounded-xl border-2 border-navy bg-navy/[0.03] px-4 py-3.5">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full border-2 border-navy"><span class="h-2 w-2 rounded-full bg-navy"></span></span>
                            A que avista a outra por boreste
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-navy/10 px-4 py-3.5">
                            <span class="h-5 w-5 rounded-full border-2 border-navy/20"></span>
                            A que avista a outra por bombordo
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-navy/10 px-4 py-3.5">
                            <span class="h-5 w-5 rounded-full border-2 border-navy/20"></span>
                            A de maior porte
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('site.partials.faq', ['faq' => $site['faq'], 'id' => 'perguntas'])

    {{-- Sobre --}}
    <section id="sobre" class="px-2 pb-2 sm:px-3 sm:pb-3">
        <div class="rounded-3xl bg-mist">
            <div class="mx-auto grid max-w-7xl gap-12 px-5 py-20 sm:px-8 md:py-28 lg:grid-cols-2 lg:gap-16">
                <div>
                    <p class="font-mono text-xs uppercase tracking-[0.18em] text-buoy">Sobre nós</p>
                    <h2 class="mt-4 text-balance text-4xl font-semibold tracking-[-0.03em] md:text-5xl">
                        Escola e despachante no mesmo lugar.
                    </h2>
                    <div class="mt-6 space-y-4 text-lg leading-relaxed text-navy/65">
                        <p>
                            A Campeão Náutica e Assessoria Naval trabalha há mais de duas décadas para tirar a
                            burocracia do caminho de quem quer navegar com segurança.
                        </p>
                        <p>
                            O mesmo time que prepara você para a prova cuida da inscrição, da documentação da
                            embarcação e dos recursos quando algo trava na Capitania.
                        </p>
                    </div>

                    <dl class="mt-10 grid gap-6 border-t border-navy/10 pt-8 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="font-mono text-xs uppercase tracking-wider text-navy/45">Endereço</dt>
                            <dd class="mt-2 leading-relaxed">
                                {{ $site['endereco']['rua'] }}<br>
                                Quadra 17 Lote 28, Bairro Aeroviário<br>
                                Goiânia - GO · CEP 74435-090
                            </dd>
                        </div>
                        <div>
                            <dt class="font-mono text-xs uppercase tracking-wider text-navy/45">CNPJ</dt>
                            <dd class="mt-2 font-mono">53.775.360/0001-21</dd>
                            <dt class="mt-5 font-mono text-xs uppercase tracking-wider text-navy/45">Horário</dt>
                            <dd class="mt-2">Segunda a sexta, 8h às 18h</dd>
                        </div>
                    </dl>
                </div>

                <div class="flex flex-col overflow-hidden rounded-2xl bg-white ring-1 ring-navy/10">
                    <iframe src="{{ $mapaEmbed }}" title="Mapa: Campeão Náutica, Goiânia"
                        class="min-h-[320px] w-full flex-1 border-0 grayscale-[0.3]" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
                    <a href="{{ $mapa }}" target="_blank" rel="noopener"
                        class="flex items-center justify-between px-5 py-4 text-sm font-medium hover:bg-mist">
                        Abrir rotas no Google Maps
                        <span aria-hidden="true">↗</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
