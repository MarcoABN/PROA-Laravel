@php
    use Illuminate\Support\Number;

    $moeda = fn ($valor) => Number::currency((float) $valor, 'BRL', 'pt_BR');

    $totais          = $this->totais;
    $porResponsavel  = $this->porResponsavel;
    $maioresGastos   = $this->maioresGastos;
    $maioresEntradas = $this->maioresEntradas;
@endphp

{{--
    Atenção ao mexer no visual desta página: o painel usa o CSS já compilado do
    Filament, que só contém as classes utilitárias usadas pelas telas dele. Classes
    como text-success-600, bg-danger-500 ou lg:grid-cols-2 NÃO existem nesse arquivo
    e simplesmente não pintam nada. Por isso as cores saem de <x-filament::badge> e
    as grades de <x-filament::grid>, que trazem o próprio estilo.
--}}
<x-filament-panels::page>

    <x-filament::section>
        <x-slot name="heading">Período</x-slot>
        <x-slot name="description">{{ $this->rotuloPeriodo }} — {{ $totais['quantidade'] }} lançamento(s)</x-slot>

        {{ $this->form }}
    </x-filament::section>

    {{-- Consolidado total do período --}}
    <x-filament::grid :default="1" :md="3" class="gap-4">
        <x-filament::section>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Entradas</div>
            <div class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ $moeda($totais['entradas']) }}</div>
            <div class="mt-1">
                <x-filament::badge color="success">Recebido no período</x-filament::badge>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Saídas</div>
            <div class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ $moeda($totais['saidas']) }}</div>
            <div class="mt-1">
                <x-filament::badge color="danger">Gasto no período</x-filament::badge>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Saldo</div>
            <div class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ $moeda($totais['saldo']) }}</div>
            <div class="mt-1">
                <x-filament::badge :color="$totais['saldo'] >= 0 ? 'success' : 'danger'">
                    {{ $totais['saldo'] >= 0 ? 'Positivo' : 'Negativo' }}
                </x-filament::badge>
            </div>
        </x-filament::section>
    </x-filament::grid>

    {{-- Fluxo de cada pessoa e da empresa --}}
    <x-filament::section>
        <x-slot name="heading">Entradas e saídas por usuário</x-slot>
        <x-slot name="description">A linha Empresa reúne os lançamentos que não pertencem a ninguém em particular.</x-slot>

        @if (empty($porResponsavel))
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum lançamento no período selecionado.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-start text-xs font-medium text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <th class="py-2 pe-4 text-start font-medium">Pertence a</th>
                            <th class="py-2 pe-4 text-center font-medium">Lançamentos</th>
                            <th class="py-2 pe-4 text-end font-medium">Entradas</th>
                            <th class="py-2 pe-4 text-end font-medium">Saídas</th>
                            <th class="py-2 text-end font-medium">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($porResponsavel as $linha)
                            <tr>
                                <td class="py-3 pe-4">
                                    @if ($linha['empresa'])
                                        <x-filament::badge color="warning" class="w-max">{{ $linha['nome'] }}</x-filament::badge>
                                    @else
                                        <span class="font-medium text-gray-950 dark:text-white">{{ $linha['nome'] }}</span>
                                    @endif
                                </td>
                                <td class="py-3 pe-4 text-center text-gray-500 dark:text-gray-400">{{ $linha['quantidade'] }}</td>
                                <td class="py-3 pe-4 text-end text-gray-500 dark:text-gray-300">{{ $moeda($linha['entradas']) }}</td>
                                <td class="py-3 pe-4 text-end text-danger-600 dark:text-danger-400">{{ $moeda($linha['saidas']) }}</td>
                                <td class="py-3 text-end">
                                    <span class="font-semibold text-gray-950 dark:text-white">{{ $moeda($linha['saldo']) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-gray-200 font-bold dark:border-white/10">
                            <td class="py-3 pe-4 text-gray-950 dark:text-white">Consolidado total</td>
                            <td class="py-3 pe-4 text-center text-gray-950 dark:text-white">{{ $totais['quantidade'] }}</td>
                            <td class="py-3 pe-4 text-end text-gray-950 dark:text-white">{{ $moeda($totais['entradas']) }}</td>
                            <td class="py-3 pe-4 text-end text-danger-600 dark:text-danger-400">{{ $moeda($totais['saidas']) }}</td>
                            <td class="py-3 text-end text-gray-950 dark:text-white">{{ $moeda($totais['saldo']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </x-filament::section>

    {{-- Rankings por tipo --}}
    <x-filament::grid :default="1" :md="2" class="gap-4">
        <x-filament::section>
            <x-slot name="heading">Maiores tipos de gasto</x-slot>
            <x-slot name="description">Top 10 do período</x-slot>

            @if (empty($maioresGastos))
                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma saída no período selecionado.</p>
            @else
                <div class="space-y-3">
                    @foreach ($maioresGastos as $item)
                        <div>
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <span class="min-w-0 truncate font-medium text-gray-950 dark:text-white">
                                    {{ $item['categoria'] }}
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">({{ $item['quantidade'] }}x)</span>
                                </span>
                                <span class="whitespace-nowrap font-semibold text-danger-600 dark:text-danger-400">
                                    {{ $moeda($item['total']) }}
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ number_format($item['percentual'], 1, ',', '.') }}%</span>
                                </span>
                            </div>
                            <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                <div class="h-full rounded-full bg-primary-500" style="width: {{ max(round($item['percentual'], 1), 1) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Maiores tipos de entrada</x-slot>
            <x-slot name="description">Top 10 do período</x-slot>

            @if (empty($maioresEntradas))
                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma entrada no período selecionado.</p>
            @else
                <div class="space-y-3">
                    @foreach ($maioresEntradas as $item)
                        <div>
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <span class="min-w-0 truncate font-medium text-gray-950 dark:text-white">
                                    {{ $item['categoria'] }}
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">({{ $item['quantidade'] }}x)</span>
                                </span>
                                <span class="whitespace-nowrap font-semibold text-gray-950 dark:text-white">
                                    {{ $moeda($item['total']) }}
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ number_format($item['percentual'], 1, ',', '.') }}%</span>
                                </span>
                            </div>
                            <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                <div class="h-full rounded-full bg-primary-500" style="width: {{ max(round($item['percentual'], 1), 1) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </x-filament::grid>

</x-filament-panels::page>
