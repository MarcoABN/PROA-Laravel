@php
    $meses = $this->meses;
@endphp

{{--
    Mesmo cuidado de consolidado-financeiro.blade.php: o painel usa o CSS compilado do
    Filament, então cores e grades saem dos componentes <x-filament::*>, não de classes utilitárias novas.
--}}
<x-filament-panels::page>

    @if ($alertaExtensao = $this->alertaExtensao)
        <x-filament::section>
            <div class="flex flex-wrap items-center gap-2">
                <x-filament::badge color="danger">Extensão desatualizada</x-filament::badge>
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $alertaExtensao }}</span>
            </div>
        </x-filament::section>
    @endif

    @if ($meses->isEmpty())
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Nenhum agendamento cadastrado ainda. Use <strong>Cadastrar agendamentos</strong> para registrar os procuradores e clientes de um mês.
            </p>
        </x-filament::section>
    @else
        <x-filament::grid :default="1" :md="2" :xl="3" class="gap-4">
            @foreach ($meses as $mes)
                <x-filament::section>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="text-lg font-bold text-gray-950 dark:text-white">{{ $mes['nome'] }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $mes['procuradores'] }} procurador(es) · {{ $mes['agendamentos'] }} agendamento(s) · {{ $mes['servicos'] }} serviço(s)
                            </div>
                            @if ($mes['capitanias'])
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $mes['capitanias'] }}</div>
                            @endif
                        </div>

                        <x-filament::button tag="a" :href="$mes['url']" size="sm" icon="heroicon-m-arrow-right" icon-position="after">
                            Abrir
                        </x-filament::button>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        @if ($mes['agendados'] > 0)
                            <x-filament::badge color="success">{{ $mes['agendados'] }} agendado(s)</x-filament::badge>
                        @endif

                        @if ($mes['pendentes'] - $mes['falhas'] > 0)
                            <x-filament::badge color="info">{{ $mes['pendentes'] - $mes['falhas'] }} aguardando</x-filament::badge>
                        @endif

                        @if ($mes['falhas'] > 0)
                            <x-filament::badge color="danger">{{ $mes['falhas'] }} com falha</x-filament::badge>
                        @endif

                        @if ($mes['agendamentos'] > 0 && $mes['pendentes'] === 0)
                            <x-filament::badge color="gray">Mês concluído</x-filament::badge>
                        @endif
                    </div>
                </x-filament::section>
            @endforeach
        </x-filament::grid>
    @endif

</x-filament-panels::page>
