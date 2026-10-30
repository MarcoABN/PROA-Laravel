@php
    $resumo = $this->resumo();
@endphp

{{--
    Grade no estilo do KellHub: linha de resumo, abas em pílula, cabeçalho em caixa alta e colunas
    agrupadas por cor (cliente · documentos · serviço · status), linhas compactas. Como nos balões,
    o CSS é próprio daqui (o CSS compilado do Filament não tem utilitários novos), com o modo escuro.
--}}
<x-filament-panels::page
    @class([
        'fi-resource-list-records-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
        'proa-grade',
    ])
>
    <style>
        .proa-resumo { font-size: .875rem; color: rgb(var(--gray-500)); line-height: 1.6; }
        .proa-resumo b { font-weight: 600; color: rgb(var(--gray-700)); }
        .dark .proa-resumo b { color: rgb(var(--gray-200)); }
        .proa-resumo .proa-ok { color: rgb(var(--success-600)); }
        .proa-resumo .proa-espera { color: rgb(var(--warning-600)); }
        .proa-resumo .proa-erro { color: rgb(var(--danger-600)); }
        .dark .proa-resumo .proa-ok { color: rgb(var(--success-400)); }
        .dark .proa-resumo .proa-espera { color: rgb(var(--warning-400)); }
        .dark .proa-resumo .proa-erro { color: rgb(var(--danger-400)); }

        /* Barra da grade (barra-clientes.blade.php): abas em pílula e filtros compactos ao lado da busca. */
        .proa-grade .fi-ta-filters-dropdown { display: none; }
        .proa-grade .fi-ta-search-field { width: 13rem; }
        .proa-barra { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; min-width: 0; flex: 1 1 0; }
        .proa-aba { display: inline-flex; align-items: center; gap: .4rem; padding: .3rem .65rem; border-radius: .5rem;
            font-size: .8rem; font-weight: 500; color: rgb(var(--gray-600)); white-space: nowrap; }
        .proa-aba:hover { background: rgb(var(--gray-50)); }
        .proa-aba--ativa, .proa-aba--ativa:hover { background: rgb(var(--success-50)); color: rgb(var(--success-700)); font-weight: 600; }
        .proa-aba__total { font-size: .7rem; padding: 0 .35rem; border-radius: .375rem; background: rgb(var(--primary-50)); color: rgb(var(--primary-600)); }
        .dark .proa-aba { color: rgb(var(--gray-300)); }
        .dark .proa-aba:hover { background: rgb(255 255 255 / .05); }
        .dark .proa-aba--ativa, .dark .proa-aba--ativa:hover { background: rgb(var(--success-500) / .15); color: rgb(var(--success-400)); }
        .dark .proa-aba__total { background: rgb(var(--primary-500) / .15); color: rgb(var(--primary-400)); }
        .proa-barra__divisor { width: 1px; height: 1.25rem; margin: 0 .25rem; background: rgb(var(--gray-200)); }
        .dark .proa-barra__divisor { background: rgb(255 255 255 / .1); }
        .proa-filtro { height: 2rem; padding: 0 2rem 0 .6rem; border-radius: .5rem; font-size: .8rem; max-width: 12rem;
            border: 1px solid rgb(var(--gray-300)); background-color: #fff; color: rgb(var(--gray-700)); }
        .dark .proa-filtro { background-color: rgb(255 255 255 / .05); border-color: rgb(255 255 255 / .1); color: rgb(var(--gray-200)); }
        .proa-chave { display: inline-flex; align-items: center; gap: .45rem; margin-left: .25rem; font-size: .8rem;
            color: rgb(var(--gray-700)); cursor: pointer; white-space: nowrap; }
        .dark .proa-chave { color: rgb(var(--gray-200)); }
        .proa-chave input { position: absolute; opacity: 0; pointer-events: none; }
        .proa-chave__trilho { position: relative; width: 2rem; height: 1.1rem; border-radius: 999px; background: rgb(var(--gray-200)); transition: background .15s; }
        .proa-chave__trilho::after { content: ''; position: absolute; top: .15rem; left: .15rem; width: .8rem; height: .8rem;
            border-radius: 999px; background: #fff; box-shadow: 0 1px 2px rgb(0 0 0 / .2); transition: transform .15s; }
        .proa-chave input:checked + .proa-chave__trilho { background: rgb(var(--primary-600)); }
        .proa-chave input:checked + .proa-chave__trilho::after { transform: translateX(.9rem); }
        .proa-chave input:focus-visible + .proa-chave__trilho { outline: 2px solid rgb(var(--primary-500)); outline-offset: 2px; }

        /* Cabeçalho em caixa alta, pequeno; linhas compactas. */
        .proa-grade .fi-ta-header-cell { padding-top: .6rem; padding-bottom: .6rem; }
        .proa-grade .fi-ta-header-cell-label { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; color: rgb(var(--gray-600)); }
        .dark .proa-grade .fi-ta-header-cell-label { color: rgb(var(--gray-300)); }
        .proa-grade .fi-ta-text { padding-top: .45rem; padding-bottom: .45rem; }
        .proa-grade .fi-ta-text-item-label, .proa-grade .fi-ta-text .text-sm { font-size: .8rem; }
        .proa-grade .fi-ta-group-header { padding-top: .45rem; padding-bottom: .45rem; }

        /* Colunas agrupadas por cor: documentos (azul), serviço (âmbar), status (verde). */
        .proa-grade .proa-g-doc { background: rgb(239 246 255); }
        .proa-grade .proa-g-servico { background: rgb(254 252 232); }
        .proa-grade .proa-g-status { background: rgb(240 253 244); }
        .proa-grade th.proa-g-doc .fi-ta-header-cell-label { color: rgb(37 99 235); }
        .proa-grade th.proa-g-servico .fi-ta-header-cell-label { color: rgb(180 83 9); }
        .proa-grade th.proa-g-status .fi-ta-header-cell-label { color: rgb(21 128 61); }
        .dark .proa-grade .proa-g-doc { background: rgb(59 130 246 / .08); }
        .dark .proa-grade .proa-g-servico { background: rgb(234 179 8 / .07); }
        .dark .proa-grade .proa-g-status { background: rgb(34 197 94 / .07); }
        .dark .proa-grade th.proa-g-doc .fi-ta-header-cell-label { color: rgb(96 165 250); }
        .dark .proa-grade th.proa-g-servico .fi-ta-header-cell-label { color: rgb(250 204 21); }
        .dark .proa-grade th.proa-g-status .fi-ta-header-cell-label { color: rgb(74 222 128); }
    </style>

    <div class="flex flex-col gap-y-4">
        <div class="proa-resumo">
            <b>{{ $resumo['clientes'] }} cliente(s)</b> de {{ $resumo['procuradores'] }} procurador(es)
            • <b class="proa-ok">{{ $resumo['agendados'] }} agendado(s)</b>
            • <b class="proa-espera">{{ $resumo['aguardando'] }} aguardando</b>
            @if ($resumo['falharam'])
                • <b class="proa-erro">{{ $resumo['falharam'] }} com falha</b>
            @endif
            @if ($resumo['descartados'])
                • <b class="proa-erro">{{ $resumo['descartados'] }} descartado(s)</b>
            @endif
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
