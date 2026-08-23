@php
    $alteracoes = $getState() ?? [];
@endphp

{{--
    Só classes que existem no CSS compilado do Filament (o painel não tem build
    próprio de Tailwind). Ver o comentário em consolidado-financeiro.blade.php.
--}}
@if (empty($alteracoes))
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Sem detalhamento de campos para esta movimentação.
    </p>
@else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 text-xs font-medium text-gray-500 dark:border-white/10 dark:text-gray-400">
                    <th class="py-2 pe-4 text-start font-medium">Campo</th>
                    <th class="py-2 pe-4 text-start font-medium">Antes</th>
                    <th class="py-2 text-start font-medium">Depois</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($alteracoes as $alteracao)
                    <tr>
                        <td class="py-3 pe-4 font-medium text-gray-950 dark:text-white">
                            {{ $alteracao['rotulo'] ?? $alteracao['campo'] ?? '—' }}
                        </td>
                        <td class="py-3 pe-4 text-gray-500 dark:text-gray-400">
                            {{ filled($alteracao['de'] ?? null) ? $alteracao['de'] : '—' }}
                        </td>
                        <td class="py-3 font-medium text-gray-950 dark:text-white">
                            {{ filled($alteracao['para'] ?? null) ? $alteracao['para'] : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
