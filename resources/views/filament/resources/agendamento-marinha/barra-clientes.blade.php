@php
    /** @var \App\Filament\Resources\AgendamentoMarinhaResource\Pages\ClientesDoMesAgendamento $pagina */
    $pagina = \Livewire\Livewire::current();
    $abaAtiva = (string) $pagina->activeTab;
@endphp

{{--
    Barra da grade "Clientes do mês" (gancho tables::toolbar.start): abas por capitania e filtros
    compactos na mesma linha da busca, como no KellHub. Os controles escrevem direto no estado dos
    filtros da tabela (tableFilters.*); o formulário de filtros do Filament fica oculto (clientes.blade.php).
--}}
<div class="proa-barra">
    @foreach ($abas = $pagina->getCachedTabs() as $chave => $aba)
        <button
            type="button"
            @class(['proa-aba', 'proa-aba--ativa' => $abaAtiva === (string) $chave])
            title="{{ $aba->getExtraAttributes()['title'] ?? '' }}"
            wire:click="$set('activeTab', '{{ $chave }}')"
        >
            {{ $aba->getLabel() }}
            <span class="proa-aba__total">{{ $aba->getBadge() }}</span>
        </button>
    @endforeach

    @if (count($abas))
        <span class="proa-barra__divisor"></span>
    @endif

    <select class="proa-filtro" wire:model.live="tableFilters.prestador_id.value" title="Procurador">
        <option value="">Todos os procuradores</option>
        @foreach ($pagina->procuradoresDoMes() as $id => $nome)
            <option value="{{ $id }}">{{ $nome }}</option>
        @endforeach
    </select>

    <select class="proa-filtro" wire:model.live="tableFilters.status.value" title="Status do agendamento">
        <option value="">Todos os status</option>
        @foreach (\App\Models\AgendamentoMarinha::statuses() as $valor => $rotulo)
            <option value="{{ $valor }}">{{ $rotulo }}</option>
        @endforeach
    </select>

    <label class="proa-chave" title="Aguardando, com falha ou descartados">
        <input type="checkbox" wire:model.live="tableFilters.nao_realizados.isActive">
        <span class="proa-chave__trilho"></span>
        Só os não agendados
    </label>
</div>
