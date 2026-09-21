<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @php
        $site = config('site');
        $end = $site['endereco'];
        $servicosSite = $servicos ?? collect();
    @endphp

    {{-- SEO Básico --}}
    <title>@yield('title')</title>
    <meta name="description" content="@yield('description')">
    @hasSection('keywords')
        <meta name="keywords" content="@yield('keywords')">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="theme-color" content="#0a1f33">

    {{-- Favicon para Pesquisa Google (Arquivo em /public) --}}
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}" type="image/jpeg">
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/jpeg" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">

    {{-- Open Graph / Social Media --}}
    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="{{ $site['nome'] }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('og_title')">
    <meta property="og:description" content="@yield('og_description')">
    <meta property="og:image" content="{{ asset('images/logo_campeao.jpg') }}">
    <meta property="og:image:type" content="image/jpeg">

    {{-- Schema.org: dados da empresa (presente em todas as páginas) --}}
    @php
        $negocio = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            '@id' => $site['url'],
            'name' => $site['nome'],
            'description' => $site['descricao'],
            'image' => asset('images/logo_campeao.jpg'),
            'logo' => asset('images/logo_campeao.jpg'),
            'url' => $site['url'],
            'telephone' => $site['telefone'],
            'email' => $site['email'],
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $end['rua'],
                'addressLocality' => $end['cidade'],
                'addressRegion' => $end['uf'],
                'postalCode' => $end['cep'],
                'addressCountry' => 'BR',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => $end['latitude'],
                'longitude' => $end['longitude'],
            ],
            'openingHoursSpecification' => [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'opens' => '08:00',
                'closes' => '18:00',
            ],
            'areaServed' => [
                ['@type' => 'State', 'name' => 'Goiás'],
                ['@type' => 'Country', 'name' => 'Brasil'],
            ],
            'sameAs' => [$site['instagram']],
        ];

        if ($servicosSite->isNotEmpty()) {
            $negocio['hasOfferCatalog'] = [
                '@type' => 'OfferCatalog',
                'name' => 'Serviços',
                'itemListElement' => $servicosSite->map(fn($s) => [
                    '@type' => 'Offer',
                    'itemOffered' => [
                        '@type' => 'Service',
                        'name' => $s->nome,
                        'url' => route('site.servico', $s),
                    ],
                ])->values()->all(),
            ];
        }

        $jsonFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG;
    @endphp
    <script type="application/ld+json">{!! json_encode($negocio, $jsonFlags) !!}</script>
    @stack('schema')

    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
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
        .menu details[open] .menu-closed, .menu details:not([open]) .menu-open { display: none; }
    </style>
</head>

<body class="bg-white text-navy">

    {{-- Navegação --}}
    <nav class="menu fixed inset-x-0 top-0 z-50 bg-white/85 backdrop-blur-lg border-b border-navy/[0.06]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('site.index') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('images/logo_campeao.jpg') }}" alt="Logotipo Campeão Náutica"
                    class="h-9 w-9 rounded-lg object-contain bg-white ring-1 ring-navy/10">
                <span class="font-semibold tracking-tight">Campeão Náutica</span>
            </a>

            <div class="hidden md:flex items-center gap-1 text-sm">
                <a href="{{ route('site.index') }}#servicos" class="px-3 py-2 rounded-lg text-navy/70 hover:text-navy hover:bg-mist transition">Serviços</a>
                <a href="{{ route('site.index') }}#simulado" class="px-3 py-2 rounded-lg text-navy/70 hover:text-navy hover:bg-mist transition">Simulado</a>
                <a href="{{ route('site.index') }}#sobre" class="px-3 py-2 rounded-lg text-navy/70 hover:text-navy hover:bg-mist transition">Sobre nós</a>
                <a href="{{ $site['instagram'] }}" target="_blank" rel="noopener" class="px-3 py-2 rounded-lg text-navy/70 hover:text-navy hover:bg-mist transition">Instagram</a>
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
                    <a href="{{ route('site.index') }}#servicos" class="block rounded-lg px-3 py-3 hover:bg-mist">Serviços</a>
                    <a href="{{ route('site.index') }}#simulado" class="block rounded-lg px-3 py-3 hover:bg-mist">Simulado</a>
                    <a href="{{ route('site.index') }}#sobre" class="block rounded-lg px-3 py-3 hover:bg-mist">Sobre nós</a>
                    <a href="{{ $site['instagram'] }}" target="_blank" rel="noopener" class="block rounded-lg px-3 py-3 hover:bg-mist">Instagram</a>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <a href="/admin/login" class="rounded-lg border border-navy/15 px-3 py-2.5 text-center font-mono text-xs leading-5">PROA</a>
                        <a href="/login" class="rounded-lg bg-navy px-3 py-2.5 text-center text-sm font-medium text-white">Área do Cliente</a>
                    </div>
                </div>
            </details>
        </div>
    </nav>

    @yield('content')

    {{-- Rodapé --}}
    <footer class="px-2 pb-2 sm:px-3 sm:pb-3">
        <div class="rounded-3xl bg-navy text-white">
            <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8">
                <div class="flex flex-col justify-between gap-8 border-b border-white/10 pb-12 md:flex-row md:items-center">
                    <p class="max-w-lg text-2xl font-semibold tracking-tight md:text-3xl">
                        Precisa regularizar a embarcação ou tirar a habilitação?
                    </p>
                    <a href="{{ $site['whatsapp'] }}" target="_blank" rel="noopener"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-buoy px-6 py-3.5 font-medium transition hover:bg-buoy-dark">
                        Fale com um despachante
                        <span aria-hidden="true">→</span>
                    </a>
                </div>

                <div class="grid gap-10 pt-12 text-sm md:grid-cols-12">
                    <div class="md:col-span-4">
                        <div class="flex items-center gap-2.5">
                            <img src="{{ asset('images/logo_campeao.jpg') }}" alt="Campeão Náutica"
                                class="h-10 w-10 rounded-lg bg-white object-contain">
                            <span class="font-semibold">Campeão Náutica</span>
                        </div>
                        <p class="mt-4 max-w-xs leading-relaxed text-white/55">
                            Referência em assessoria naval e regularização junto à Marinha do Brasil.
                        </p>
                    </div>

                    <div class="md:col-span-3">
                        <h4 class="font-mono text-xs uppercase tracking-wider text-white/40">Serviços</h4>
                        <ul class="mt-4 space-y-2.5 text-white/75">
                            @forelse($servicosSite as $s)
                                <li><a href="{{ route('site.servico', $s) }}" class="hover:text-white">{{ $s->nome }}</a></li>
                            @empty
                                <li><a href="{{ route('site.index') }}#servicos" class="hover:text-white">Ver serviços</a></li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="md:col-span-3">
                        <h4 class="font-mono text-xs uppercase tracking-wider text-white/40">Contatos</h4>
                        <ul class="mt-4 space-y-2.5 text-white/75">
                            <li><a href="mailto:{{ $site['email'] }}" class="break-all hover:text-white">{{ $site['email'] }}</a></li>
                            <li><a href="mailto:{{ $site['email2'] }}" class="break-all hover:text-white">{{ $site['email2'] }}</a></li>
                            <li><a href="{{ $site['whatsapp'] }}" target="_blank" rel="noopener" class="hover:text-white">{{ $site['whatsapp_exibicao'] }} · WhatsApp</a></li>
                            <li><a href="tel:{{ $site['telefone2'] }}" class="hover:text-white">{{ $site['telefone2_exibicao'] }}</a></li>
                            <li><a href="{{ $site['instagram'] }}" target="_blank" rel="noopener" class="hover:text-white">Instagram: {{ $site['instagram_usuario'] }}</a></li>
                        </ul>
                    </div>

                    <div class="md:col-span-2">
                        <h4 class="font-mono text-xs uppercase tracking-wider text-white/40">Acesso rápido</h4>
                        <ul class="mt-4 space-y-2.5 text-white/75">
                            <li><a href="/login" class="hover:text-white">Área do Cliente (Simulador)</a></li>
                            <li><a href="/admin/login" class="hover:text-white">PROA</a></li>
                        </ul>
                    </div>
                </div>

                <p class="mt-14 font-mono text-xs text-white/35">
                    &copy; {{ date('Y') }} Campeão Náutica · CNPJ {{ $site['cnpj'] }} · Goiânia - GO
                </p>
            </div>
        </div>
    </footer>

</body>

</html>
