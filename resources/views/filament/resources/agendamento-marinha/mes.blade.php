@php
    $baloes = $this->baloes;
@endphp

{{--
    O painel usa o CSS compilado do Filament: classes utilitárias novas não existem, então os balões
    têm estilo próprio aqui (com o modo escuro do Filament, classe .dark no <html>).
--}}
<x-filament-panels::page
    @class([
        'fi-resource-list-records-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
    ])
>
    <style>
        .proa-baloes { display: grid; gap: .6rem; grid-template-columns: repeat(auto-fill, minmax(13.5rem, 1fr)); }
        .proa-balao {
            display: flex; flex-direction: column; gap: .3rem; padding: .6rem .75rem; border-radius: .75rem;
            background: #fff; border: 1px solid rgb(229 231 235); box-shadow: 0 1px 2px rgb(0 0 0 / .05);
            transition: box-shadow .15s, border-color .15s, transform .15s; text-align: left; width: 100%;
        }
        .proa-balao:hover { border-color: rgb(var(--primary-500)); box-shadow: 0 4px 14px rgb(0 0 0 / .08); transform: translateY(-1px); }
        .dark .proa-balao { background: rgb(var(--gray-900)); border-color: rgb(255 255 255 / .1); }
        .proa-balao__nome { font-weight: 600; font-size: .8rem; line-height: 1.2; color: rgb(var(--gray-950));
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .dark .proa-balao__nome { color: #fff; }
        .proa-capitania { display: flex; align-items: baseline; gap: .5rem; margin-bottom: .5rem;
            font-size: .85rem; font-weight: 600; color: rgb(var(--danger-600)); }
        .dark .proa-capitania { color: rgb(var(--danger-400)); }
        .proa-capitania__total { font-size: .75rem; font-weight: 400; color: rgb(var(--gray-500)); }
        .proa-balao__linha { display: flex; align-items: center; justify-content: space-between; gap: .4rem; font-size: .75rem;
            white-space: nowrap; color: rgb(var(--gray-600)); }
        .dark .proa-balao__linha { color: rgb(var(--gray-300)); }
        .proa-balao__rodape { font-size: .7rem; color: rgb(var(--gray-500)); }
        .proa-balao__aviso { font-size: .7rem; font-weight: 500; color: rgb(var(--warning-600)); }
        .dark .proa-balao__aviso { color: rgb(var(--warning-400)); }
        .proa-balao--novo { flex-direction: row; align-items: center; justify-content: center; gap: .4rem; min-height: 4.5rem;
            border-style: dashed; border-width: 2px; color: rgb(var(--primary-600)); font-size: .8rem; font-weight: 600; cursor: pointer; }
        .dark .proa-balao--novo { color: rgb(var(--primary-400)); }
        .proa-extensao { display: flex; align-items: center; gap: .5rem; padding: .6rem .9rem; border-radius: .75rem; font-size: .875rem;
            background: rgb(var(--primary-50)); color: rgb(var(--primary-700)); border: 1px solid rgb(var(--primary-200)); }
        .dark .proa-extensao { background: rgb(var(--primary-950) / .4); color: rgb(var(--primary-300)); border-color: rgb(var(--primary-800)); }
    </style>

    <div class="flex flex-col gap-y-6">
        @if ($textoExtensao = $this->textoExtensao())
            <div class="proa-extensao">
                <x-filament::icon icon="heroicon-o-puzzle-piece" style="width: 1.25rem; height: 1.25rem; flex-shrink: 0;" />
                <span>{{ $textoExtensao }}</span>
            </div>
        @endif

        {{-- Um bloco por capitania; o nome dela fica no título do bloco, não em cada balão. --}}
        @foreach ($baloes->groupBy('capitania') as $capitania => $baloesDaCapitania)
            <section>
                <div class="proa-capitania">
                    {{ $capitania }}
                    <span class="proa-capitania__total">{{ $baloesDaCapitania->count() }} procurador(es)</span>
                </div>

                <div class="proa-baloes">
                    @foreach ($baloesDaCapitania as $balao)
                        <a href="{{ $balao['url'] }}" class="proa-balao" title="Abrir e preencher: {{ $balao['procurador'] }}">
                            <div class="proa-balao__nome">{{ $balao['procurador'] }}</div>

                            @foreach ($balao['slots'] as $slot)
                                <div class="proa-balao__linha">
                                    <span>{{ \App\Models\AgendamentoMarinha::etiquetaDaOrdem($slot['ordem']) }} {{ $slot['clientes'] }}{{ $slot['quando'] ? ' · ' . $slot['quando'] : '' }}</span>
                                    <x-filament::badge :color="$slot['cor']" size="xs">{{ $slot['status'] }}</x-filament::badge>
                                </div>
                            @endforeach

                            @if ($balao['preferencia'])
                                <div class="proa-balao__rodape">{{ $balao['preferencia'] }}</div>
                            @endif

                            @if ($balao['repetidos'])
                                <div class="proa-balao__aviso" title="Permitido (serviços em dias diferentes); confira na lista de clientes.">
                                    ⚠ {{ $balao['repetidos'] }} cliente(s) em mais de um agendamento
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach

        @if ($this->podeCriar())
            <div class="proa-baloes">
                <button type="button" class="proa-balao proa-balao--novo" wire:click="mountAction('novoBalao')">
                    <x-filament::icon icon="heroicon-o-plus-circle" style="width: 1.25rem; height: 1.25rem;" />
                    Novo agendamento
                </button>
            </div>
        @elseif ($baloes->isEmpty())
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum agendamento neste mês.</p>
            </x-filament::section>
        @endif

        {{-- Sem a tabela nesta página, os modais das ações (novo agendamento) entram aqui. --}}
        <x-filament-actions::modals />
    </div>
</x-filament-panels::page>
