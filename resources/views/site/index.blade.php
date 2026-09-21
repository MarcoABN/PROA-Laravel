<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- SEO Básico --}}
    <title>Campeão Náutica | Despachante Marítimo e Escola Naval em Goiânia</title>
    <meta name="description"
        content="⚓Campeão Náutica: Arrais, Motonauta e Regularização. Atendemos todo o Brasil. +20 anos de tradição, simulado online e recursos. Fale conosco: (62) 99859-9357.">
    <meta name="keywords"
        content="despachante náutico, escola naval, arrais amador, motonauta, marinha do brasil, regularização de barcos, goiânia">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="theme-color" content="#0a1f33">

    {{-- Favicon para Pesquisa Google (Arquivo em /public) --}}
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}" type="image/jpeg">
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/jpeg" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">

    {{-- Open Graph / Social Media --}}
    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="Campeão Náutica | Despachante Marítimo e Escola Naval">
    <meta property="og:description"
        content="Regularize sua embarcação ou tire sua habilitação com especialistas. Mais de 20 anos de tradição.">
    <meta property="og:image" content="{{ asset('images/logo_campeao.jpg') }}">
    <meta property="og:image:type" content="image/jpeg">

    {{-- Schema.org Structured Data (Correção com @@ para evitar ParseError) --}}
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "LocalBusiness",
      "name": "Campeão Náutica",
      "image": "{{ asset('images/logo_campeao.jpg') }}",
      "logo": "{{ asset('images/logo_campeao.jpg') }}",
      "@@id": "https://campeaonautica.com.br",
      "url": "https://campeaonautica.com.br",
      "telephone": "+5562998599357",
      "address": {
        "@@type": "PostalAddress",
        "streetAddress": "Avenida 24 de Outubro, 3047",
        "addressLocality": "Goiânia",
        "addressRegion": "GO",
        "postalCode": "74435-090",
        "addressCountry": "BR"
      },
      "geo": {
        "@@type": "GeoCoordinates",
        "latitude": -16.6710,
        "longitude": -49.2845
      },
      "openingHoursSpecification": {
        "@@type": "OpeningHoursSpecification",
        "dayOfWeek": [
          "Monday",
          "Tuesday",
          "Wednesday",
          "Thursday",
          "Friday"
        ],
        "opens": "08:00",
        "closes": "18:00"
      }
    }
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { DEFAULT: '#0a1f33', 800: '#12304d', 700: '#1c4266' },
                        buoy: { DEFAULT: '#ff5a1f', dark: '#e04710' },
                        mist: '#f3f5f7',
                    },
                    fontFamily: {
                        sans: ['Geist', 'system-ui', 'sans-serif'],
                        mono: ['"Geist Mono"', 'ui-monospace', 'monospace'],
                    },
                },
            },
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap"
        rel="stylesheet">

    <style>
        html { scroll-behavior: smooth; scroll-padding-top: 6rem; }
        body { font-family: 'Geist', system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        details > summary { list-style: none; }
        details > summary::-webkit-details-marker { display: none; }
        details[open] .menu-closed, details:not([open]) .menu-open { display: none; }
    </style>
</head>

<body class="bg-white text-navy">

    @php
        $whatsapp = 'https://wa.me/5562998599357';
        $instagram = 'https://www.instagram.com/campeao.despachantenautico10';
        $endereco = 'Avenida 24 de Outubro, 3047, Aeroviário, Goiânia - GO, 74435-090';
        $mapa = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($endereco);
        $mapaEmbed = 'https://www.google.com/maps?q=' . urlencode($endereco) . '&output=embed';
    @endphp

    {{-- Navegação --}}
    <nav class="fixed inset-x-0 top-0 z-50 bg-white/85 backdrop-blur-lg border-b border-navy/[0.06]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('images/logo_campeao.jpg') }}" alt="Logotipo Campeão Náutica"
                    class="h-9 w-9 rounded-lg object-contain bg-white ring-1 ring-navy/10">
                <span class="font-semibold tracking-tight">Campeão Náutica</span>
            </a>

            <div class="hidden md:flex items-center gap-1 text-sm">
                <a href="#servicos" class="px-3 py-2 rounded-lg text-navy/70 hover:text-navy hover:bg-mist transition">Serviços</a>
                <a href="#simulado" class="px-3 py-2 rounded-lg text-navy/70 hover:text-navy hover:bg-mist transition">Simulado</a>
                <a href="#sobre" class="px-3 py-2 rounded-lg text-navy/70 hover:text-navy hover:bg-mist transition">Sobre nós</a>
                <a href="{{ $instagram }}" target="_blank" rel="noopener" class="px-3 py-2 rounded-lg text-navy/70 hover:text-navy hover:bg-mist transition">Instagram</a>
                <span class="mx-2 h-5 w-px bg-navy/10"></span>
                <a href="/admin/login" class="px-3 py-2 font-mono text-xs text-navy/50 hover:text-navy transition">PROA</a>
                <a href="/login" class="ml-1 rounded-lg bg-navy px-4 py-2 font-medium text-white hover:bg-navy-700 transition">
                    Área do Cliente
                </a>
            </div>

            {{-- Menu mobile --}}
            <details class="md:hidden">
                <summary class="cursor-pointer rounded-lg p-2 hover:bg-mist" aria-label="Abrir menu">
                    <svg class="menu-closed h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                    <svg class="menu-open h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </summary>
                <div class="absolute inset-x-0 top-16 border-b border-navy/10 bg-white px-4 pb-5 pt-2 shadow-lg shadow-navy/5">
                    <a href="#servicos" class="block rounded-lg px-3 py-3 hover:bg-mist">Serviços</a>
                    <a href="#simulado" class="block rounded-lg px-3 py-3 hover:bg-mist">Simulado</a>
                    <a href="#sobre" class="block rounded-lg px-3 py-3 hover:bg-mist">Sobre nós</a>
                    <a href="{{ $instagram }}" target="_blank" rel="noopener" class="block rounded-lg px-3 py-3 hover:bg-mist">Instagram</a>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <a href="/admin/login" class="rounded-lg border border-navy/15 px-3 py-2.5 text-center font-mono text-xs leading-5">PROA</a>
                        <a href="/login" class="rounded-lg bg-navy px-3 py-2.5 text-center text-sm font-medium text-white">Área do Cliente</a>
                    </div>
                </div>
            </details>
        </div>
    </nav>

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
                        <a href="{{ $whatsapp }}" target="_blank" rel="noopener"
                            class="inline-flex items-center justify-center gap-2.5 rounded-xl bg-buoy px-6 py-3.5 font-medium text-white transition hover:bg-buoy-dark">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.5-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.3zM12 21.8c-1.8 0-3.5-.5-5-1.4l-.4-.2-3.7 1 1-3.6-.2-.4C2.7 15.6 2.2 13.8 2.2 12 2.2 6.6 6.6 2.2 12 2.2S21.8 6.6 21.8 12 17.4 21.8 12 21.8zM12 0C5.4 0 0 5.4 0 12c0 2.1.6 4.2 1.6 6L0 24l6.2-1.6c1.8 1 3.8 1.5 5.8 1.5 6.6 0 12-5.4 12-12S18.6 0 12 0z"/></svg>
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
                        <div class="rounded-xl bg-white p-6 text-navy">
                            <div class="flex items-center justify-between">
                                <h2 class="font-semibold">Atendimento</h2>
                                <span class="inline-flex items-center gap-1.5 font-mono text-xs text-navy/60">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Seg–Sex · 8h–18h
                                </span>
                            </div>
                            <ul class="mt-5 space-y-1 text-sm">
                                <li>
                                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="-mx-2 flex items-center justify-between rounded-lg px-2 py-2.5 hover:bg-mist">
                                        <span class="text-navy/60">WhatsApp</span>
                                        <span class="font-mono font-medium">(62) 9 9859-9357</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="tel:+5562996577973" class="-mx-2 flex items-center justify-between rounded-lg px-2 py-2.5 hover:bg-mist">
                                        <span class="text-navy/60">Telefone</span>
                                        <span class="font-mono font-medium">(62) 9 9657-7973</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="mailto:contato@campeaonautica.com.br" class="-mx-2 flex items-center justify-between gap-4 rounded-lg px-2 py-2.5 hover:bg-mist">
                                        <span class="text-navy/60">E-mail</span>
                                        <span class="truncate font-medium">contato@campeaonautica.com.br</span>
                                    </a>
                                </li>
                            </ul>
                            <a href="{{ $mapa }}" target="_blank" rel="noopener"
                                class="mt-4 flex items-start gap-3 rounded-xl bg-mist p-4 text-sm transition hover:bg-navy/[0.07]">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-buoy" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a7 7 0 00-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 00-7-7zm0 9.5A2.5 2.5 0 1112 6.5a2.5 2.5 0 010 5z"/></svg>
                                <span>
                                    Av. 24 de Outubro, 3047 — Aeroviário<br>
                                    <span class="text-navy/60">Goiânia, GO · Ver no mapa</span>
                                </span>
                            </a>
                        </div>
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
                    <a href="{{ $whatsapp }}?text={{ rawurlencode('Olá! Gostaria de um orçamento para: ' . $servico->nome) }}"
                        target="_blank" rel="noopener"
                        class="group flex flex-col rounded-2xl border border-navy/10 p-7 transition duration-300 hover:border-navy hover:bg-navy hover:text-white">
                        <span class="font-mono text-xs text-navy/40 transition group-hover:text-white/50">
                            {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <h3 class="mt-10 text-xl font-semibold tracking-tight">{{ $servico->nome }}</h3>
                        <p class="mt-3 flex-1 leading-relaxed text-navy/60 transition group-hover:text-white/70">{{ $servico->descricao }}</p>
                        <span class="mt-8 inline-flex items-center gap-2 text-sm font-medium text-buoy">
                            Pedir orçamento
                            <span class="transition group-hover:translate-x-1" aria-hidden="true">→</span>
                        </span>
                    </a>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-navy/15 p-10 text-center text-navy/60">
                        Nenhum serviço cadastrado no momento. Fale com a gente pelo
                        <a href="{{ $whatsapp }}" class="font-medium text-buoy">WhatsApp</a>.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Como funciona --}}
    <section class="bg-mist py-20 md:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="font-mono text-xs uppercase tracking-[0.18em] text-buoy">Como funciona</p>
            <h2 class="mt-4 text-balance max-w-2xl text-4xl font-semibold tracking-[-0.03em] md:text-5xl">
                Você fala com a gente. O resto é com a gente.
            </h2>

            <ol class="mt-14 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <li class="rounded-2xl bg-white p-7">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-navy font-mono text-sm text-white">1</span>
                    <h3 class="mt-6 font-semibold">Conte o que precisa</h3>
                    <p class="mt-2 text-sm leading-relaxed text-navy/60">Pelo WhatsApp, por telefone ou aqui no escritório.</p>
                </li>
                <li class="rounded-2xl bg-white p-7">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-navy font-mono text-sm text-white">2</span>
                    <h3 class="mt-6 font-semibold">Receba a lista de documentos</h3>
                    <p class="mt-2 text-sm leading-relaxed text-navy/60">Conferimos tudo antes de dar entrada, para evitar exigências depois.</p>
                </li>
                <li class="rounded-2xl bg-white p-7">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-navy font-mono text-sm text-white">3</span>
                    <h3 class="mt-6 font-semibold">Damos entrada na Marinha</h3>
                    <p class="mt-2 text-sm leading-relaxed text-navy/60">Agendamento, protocolo e acompanhamento junto à Capitania.</p>
                </li>
                <li class="rounded-2xl bg-white p-7">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-buoy font-mono text-sm text-white">4</span>
                    <h3 class="mt-6 font-semibold">Pronto, é só retirar</h3>
                    <p class="mt-2 text-sm leading-relaxed text-navy/60">Avisamos você assim que o documento estiver liberado.</p>
                </li>
            </ol>
        </div>
    </section>

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
                                Avenida 24 de Outubro, 3047<br>
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

    {{-- Rodapé --}}
    <footer class="px-2 pb-2 sm:px-3 sm:pb-3">
        <div class="rounded-3xl bg-navy text-white">
            <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8">
                <div class="flex flex-col justify-between gap-8 border-b border-white/10 pb-12 md:flex-row md:items-center">
                    <p class="max-w-lg text-2xl font-semibold tracking-tight md:text-3xl">
                        Precisa regularizar a embarcação ou tirar a habilitação?
                    </p>
                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-buoy px-6 py-3.5 font-medium transition hover:bg-buoy-dark">
                        Fale com um despachante
                        <span aria-hidden="true">→</span>
                    </a>
                </div>

                <div class="grid gap-10 pt-12 text-sm md:grid-cols-12">
                    <div class="md:col-span-5">
                        <div class="flex items-center gap-2.5">
                            <img src="{{ asset('images/logo_campeao.jpg') }}" alt="Campeão Náutica"
                                class="h-10 w-10 rounded-lg bg-white object-contain">
                            <span class="font-semibold">Campeão Náutica</span>
                        </div>
                        <p class="mt-4 max-w-xs leading-relaxed text-white/55">
                            Referência em assessoria naval e regularização junto à Marinha do Brasil.
                        </p>
                    </div>

                    <div class="md:col-span-4">
                        <h4 class="font-mono text-xs uppercase tracking-wider text-white/40">Contatos</h4>
                        <ul class="mt-4 space-y-2.5 text-white/75">
                            <li><a href="mailto:contato@campeaonautica.com.br" class="hover:text-white">contato@campeaonautica.com.br</a></li>
                            <li><a href="mailto:campeaonautica@gmail.com" class="hover:text-white">campeaonautica@gmail.com</a></li>
                            <li><a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="hover:text-white">(62) 9 9859-9357 · WhatsApp</a></li>
                            <li><a href="tel:+5562996577973" class="hover:text-white">(62) 9 9657-7973</a></li>
                            <li><a href="{{ $instagram }}" target="_blank" rel="noopener" class="hover:text-white">Instagram: @campeao.despachantenautico10</a></li>
                        </ul>
                    </div>

                    <div class="md:col-span-3">
                        <h4 class="font-mono text-xs uppercase tracking-wider text-white/40">Acesso rápido</h4>
                        <ul class="mt-4 space-y-2.5 text-white/75">
                            <li><a href="/login" class="hover:text-white">Área do Cliente (Simulador)</a></li>
                            <li><a href="#servicos" class="hover:text-white">Serviços</a></li>
                            <li><a href="/admin/login" class="hover:text-white">PROA</a></li>
                        </ul>
                    </div>
                </div>

                <p class="mt-14 font-mono text-xs text-white/35">
                    &copy; {{ date('Y') }} Campeão Náutica · CNPJ 53.775.360/0001-21 · Goiânia - GO
                </p>
            </div>
        </div>
    </footer>

</body>

</html>
