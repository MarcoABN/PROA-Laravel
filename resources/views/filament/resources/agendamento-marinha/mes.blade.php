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
        .proa-baloes { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(17rem, 1fr)); }
        .proa-balao {
            display: flex; flex-direction: column; gap: .5rem; padding: 1rem 1.1rem; border-radius: 1rem;
            background: #fff; border: 1px solid rgb(229 231 235); box-shadow: 0 1px 2px rgb(0 0 0 / .05);
            transition: box-shadow .15s, border-color .15s, transform .15s; text-align: left; width: 100%;
        }
        .proa-balao:hover { border-color: rgb(var(--primary-500)); box-shadow: 0 4px 14px rgb(0 0 0 / .08); transform: translateY(-1px); }
        .dark .proa-balao { background: rgb(var(--gray-900)); border-color: rgb(255 255 255 / .1); }
        .proa-balao__nome { font-weight: 600; font-size: 1rem; color: rgb(var(--gray-950)); }
        .dark .proa-balao__nome { color: #fff; }
        .proa-balao__capitania { font-size: .8rem; font-weight: 500; color: rgb(var(--danger-600)); }
        .dark .proa-balao__capitania { color: rgb(var(--danger-400)); }
        .proa-balao__linha { display: flex; align-items: center; justify-content: space-between; gap: .5rem; font-size: .85rem; color: rgb(var(--gray-600)); }
        .dark .proa-balao__linha { color: rgb(var(--gray-300)); }
        .proa-balao__rodape { font-size: .75rem; color: rgb(var(--gray-500)); }
        .proa-balao__abrir { margin-top: auto; font-size: .8rem; font-weight: 600; color: rgb(var(--primary-600)); }
        .dark .proa-balao__abrir { color: rgb(var(--primary-400)); }
        .proa-balao--novo { align-items: center; justify-content: center; min-height: 9rem; border-style: dashed; border-width: 2px; color: rgb(var(--primary-600)); font-weight: 600; cursor: pointer; }
        .dark .proa-balao--novo { color: rgb(var(--primary-400)); }
        .proa-extensao { display: flex; align-items: center; gap: .5rem; padding: .6rem .9rem; border-radius: .75rem; font-size: .875rem;
            background: rgb(var(--primary-50)); color: rgb(var(--primary-700)); border: 1px solid rgb(var(--primary-200)); }
        .dark .proa-extensao { background: rgb(var(--primary-950) / .4); color: rgb(var(--primary-300)); border-color: rgb(var(--primary-800)); }
        .proa-titulo-lista { font-size: 1rem; font-weight: 600; color: rgb(var(--gray-950)); margin-top: .5rem; }
        .dark .proa-titulo-lista { color: #fff; }
    </style>

    <div class="flex flex-col gap-y-6">
        @if ($textoExtensao = $this->textoExtensao())
            <div class="proa-extensao">
                <x-filament::icon icon="heroicon-o-puzzle-piece" style="width: 1.25rem; height: 1.25rem; flex-shrink: 0;" />
                <span>{{ $textoExtensao }}</span>
            </div>
        @endif

        <div class="proa-baloes">
            @foreach ($baloes as $balao)
                <a href="{{ $balao['url'] }}" class="proa-balao">
                    <div>
                        <div class="proa-balao__nome">{{ $balao['procurador'] }}</div>
                        <div class="proa-balao__capitania">{{ $balao['capitania'] }}</div>
                    </div>

                    @foreach ($balao['slots'] as $slot)
                        <div class="proa-balao__linha">
                            <span>{{ \App\Models\AgendamentoMarinha::etiquetaDaOrdem($slot['ordem']) }} {{ $slot['clientes'] }}{{ $slot['quando'] ? ' · ' . $slot['quando'] : '' }}</span>
                            <x-filament::badge :color="$slot['cor']" size="sm">{{ $slot['status'] }}</x-filament::badge>
                        </div>
                    @endforeach

                    @if ($balao['preferencia'])
                        <div class="proa-balao__rodape">{{ $balao['preferencia'] }}</div>
                    @endif

                    <div class="proa-balao__abrir">Abrir e preencher →</div>
                </a>
            @endforeach

            @if ($this->podeCriar())
                <button type="button" class="proa-balao proa-balao--novo" wire:click="mountAction('novoBalao')">
                    <x-filament::icon icon="heroicon-o-plus-circle" style="width: 2rem; height: 2rem;" />
                    Novo agendamento
                </button>
            @elseif ($baloes->isEmpty())
                <x-filament::section>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum agendamento neste mês.</p>
                </x-filament::section>
            @endif
        </div>

        @if ($baloes->isNotEmpty())
            <div class="proa-titulo-lista">Clientes do mês</div>

            {{ $this->table }}
        @else
            {{-- Em página com tabela, os modais das ações vêm junto da tabela; sem ela, entram aqui. --}}
            <x-filament-actions::modals />
        @endif
    </div>
</x-filament-panels::page>
