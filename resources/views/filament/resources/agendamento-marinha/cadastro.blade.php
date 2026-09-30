<x-filament-panels::page>
    {{--
        Clientes de um agendamento como linhas de tabela (telas largas): lixeira ao lado dos campos,
        sem a faixa de cabeçalho de cada item, e rótulos só na primeira linha.
    --}}
    <style>
        @media (min-width: 1024px) {
            .proa-clientes :has(> .fi-fo-repeater-item) {
                gap: 0.5rem;
            }

            .proa-clientes .fi-fo-repeater-item {
                display: flex;
                flex-direction: row-reverse;
                align-items: flex-end;
            }

            .proa-clientes .fi-fo-repeater-item > * {
                border-top-width: 0 !important;
            }

            .proa-clientes .fi-fo-repeater-item-header {
                flex-shrink: 0;
                padding: 0 0.75rem 1rem 0.25rem;
            }

            .proa-clientes .fi-fo-repeater-item-content {
                flex: 1 1 auto;
                min-width: 0;
                padding: 0.5rem 0.75rem;
            }

            /* Rótulo visível só na primeira linha; nas demais fica só para leitores de tela. */
            .proa-clientes .fi-fo-repeater-item:not(:first-child) .fi-fo-field-wrp > div > div:has(> .fi-fo-field-wrp-label) {
                position: absolute;
                width: 1px;
                height: 1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
            }
        }
    </style>

    <x-filament-panels::form wire:submit="salvar">
        {{ $this->form }}

        <x-filament-panels::form.actions :actions="$this->getFormActions()" />
    </x-filament-panels::form>
</x-filament-panels::page>
