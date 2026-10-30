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

        /* Abas em pílula: a ativa em verde claro. */
        .proa-grade .fi-tabs { background: transparent; box-shadow: none; padding: 0; gap: .5rem; margin-inline: 0; }
        .proa-grade .fi-tabs-item { border-radius: .5rem; padding: .35rem .75rem; font-size: .85rem; }
        .proa-grade .fi-tabs-item.fi-active { background: rgb(var(--success-50)); }
        .proa-grade .fi-tabs-item.fi-active .fi-tabs-item-label { color: rgb(var(--success-700)); font-weight: 600; }
        .dark .proa-grade .fi-tabs-item.fi-active { background: rgb(var(--success-500) / .15); }
        .dark .proa-grade .fi-tabs-item.fi-active .fi-tabs-item-label { color: rgb(var(--success-400)); }

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

        <x-filament-panels::resources.tabs />

        {{ $this->table }}
    </div>
</x-filament-panels::page>
