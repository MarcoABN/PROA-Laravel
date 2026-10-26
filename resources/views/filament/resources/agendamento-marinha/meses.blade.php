@php
    $meses = $this->meses;
@endphp

{{--
    O painel usa o CSS compilado do Filament: classes utilitárias novas não existem, então o calendário
    tem estilo próprio aqui (com o modo escuro do Filament, classe .dark no <html>).
--}}
<x-filament-panels::page>
    <style>
        .proa-ano { display: flex; align-items: center; justify-content: center; gap: 1rem; }
        .proa-ano__numero { font-size: 1.5rem; font-weight: 700; min-width: 5rem; text-align: center; color: rgb(var(--gray-950)); }
        .dark .proa-ano__numero { color: #fff; }
        .proa-legenda { display: flex; flex-wrap: wrap; justify-content: center; gap: 1.25rem; font-size: .8rem; color: rgb(var(--gray-500)); }
        .proa-legenda span { display: inline-flex; align-items: center; gap: .4rem; }
        .proa-meses { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(15.5rem, 1fr)); }
        .proa-mes {
            display: block; padding: .9rem 1rem 1rem; border-radius: 1rem; background: #fff;
            border: 1px solid rgb(229 231 235); box-shadow: 0 1px 2px rgb(0 0 0 / .05);
            transition: box-shadow .15s, border-color .15s, transform .15s;
        }
        .proa-mes:hover { border-color: rgb(var(--primary-500)); box-shadow: 0 4px 14px rgb(0 0 0 / .08); transform: translateY(-1px); }
        .proa-mes--atual { border: 2px solid rgb(var(--primary-500)); }
        .dark .proa-mes { background: rgb(var(--gray-900)); border-color: rgb(255 255 255 / .1); }
        .dark .proa-mes--atual { border-color: rgb(var(--primary-400)); }
        .proa-mes__topo { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .5rem; }
        .proa-mes__nome { font-weight: 700; color: rgb(var(--gray-950)); }
        .dark .proa-mes__nome { color: #fff; }
        .proa-mes__situacao { display: flex; flex-wrap: wrap; gap: .25rem; justify-content: flex-end; }
        .proa-mes__grade { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; text-align: center; font-size: .75rem; }
        .proa-mes__semana { font-weight: 600; color: rgb(var(--gray-400)); padding-bottom: .15rem; }
        .proa-dia { height: 1.85rem; display: flex; align-items: center; justify-content: center; border-radius: 999px; color: rgb(var(--gray-700)); }
        .dark .proa-dia { color: rgb(var(--gray-300)); }
        .proa-dia--folga { color: rgb(var(--danger-500)); opacity: .75; }
        .proa-dia--hoje { box-shadow: inset 0 0 0 2px rgb(var(--primary-500)); font-weight: 700; }
        .proa-dia--marcado, .proa-legenda__marcado { background: rgb(var(--success-600)); color: #fff !important; opacity: 1; font-weight: 700; }
        .proa-legenda__marcado, .proa-legenda__hoje { width: 1rem; height: 1rem; border-radius: 999px; display: inline-block; }
        .proa-legenda__hoje { box-shadow: inset 0 0 0 2px rgb(var(--primary-500)); }
        .proa-mes__abrir { margin-top: .6rem; font-size: .8rem; font-weight: 600; color: rgb(var(--primary-600)); text-align: right; }
        .dark .proa-mes__abrir { color: rgb(var(--primary-400)); }
    </style>

    @if ($alertaExtensao = $this->alertaExtensao)
        <x-filament::section>
            <div class="flex flex-wrap items-center gap-2">
                <x-filament::badge color="danger">Extensão desatualizada</x-filament::badge>
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $alertaExtensao }}</span>
            </div>
        </x-filament::section>
    @endif

    <div class="proa-ano">
        <x-filament::icon-button icon="heroicon-o-chevron-left" label="Ano anterior" size="lg" wire:click="anoAnterior" />
        <div class="proa-ano__numero">{{ $this->ano }}</div>
        <x-filament::icon-button icon="heroicon-o-chevron-right" label="Próximo ano" size="lg" wire:click="proximoAno" />
    </div>

    <div class="proa-legenda">
        <span><i class="proa-legenda__marcado"></i> Dia com agendamento marcado</span>
        <span><i class="proa-legenda__hoje"></i> Hoje</span>
        <span>Clique num mês para abrir os agendamentos dele.</span>
    </div>

    <div class="proa-meses">
        @foreach ($meses as $mes)
            <a href="{{ $mes['url'] }}" @class(['proa-mes', 'proa-mes--atual' => $mes['atual']])>
                <div class="proa-mes__topo">
                    <span class="proa-mes__nome">{{ $mes['nome'] }}</span>

                    <span class="proa-mes__situacao">
                        @if ($mes['naExtensao'])
                            <x-filament::badge color="primary" size="sm" icon="heroicon-m-puzzle-piece" tooltip="Os agendamentos deste mês aparecem na extensão">extensão</x-filament::badge>
                        @endif
                        @if ($mes['agendados'])
                            <x-filament::badge color="success" size="sm">{{ $mes['agendados'] }} marcado(s)</x-filament::badge>
                        @endif
                        @if ($mes['aguardando'])
                            <x-filament::badge color="info" size="sm">{{ $mes['aguardando'] }} aguardando</x-filament::badge>
                        @endif
                        @if ($mes['falhas'])
                            <x-filament::badge color="danger" size="sm">{{ $mes['falhas'] }} com falha</x-filament::badge>
                        @endif
                    </span>
                </div>

                <div class="proa-mes__grade">
                    @foreach (['D', 'S', 'T', 'Q', 'Q', 'S', 'S'] as $letra)
                        <span class="proa-mes__semana">{{ $letra }}</span>
                    @endforeach

                    @foreach ($mes['semanas'] as $semana)
                        @foreach ($semana as $dia)
                            @if ($dia)
                                <span
                                    @class([
                                        'proa-dia',
                                        'proa-dia--folga' => $dia['folga'],
                                        'proa-dia--hoje' => $dia['hoje'],
                                        'proa-dia--marcado' => $dia['marcados'],
                                    ])
                                    @if ($dia['marcados'] || $dia['feriado'])
                                        title="{{ implode("\n", array_filter([$dia['feriado'], ...$dia['marcados']])) }}"
                                    @endif
                                >{{ $dia['dia'] }}</span>
                            @else
                                <span></span>
                            @endif
                        @endforeach
                    @endforeach
                </div>

                <div class="proa-mes__abrir">Abrir →</div>
            </a>
        @endforeach
    </div>
</x-filament-panels::page>
