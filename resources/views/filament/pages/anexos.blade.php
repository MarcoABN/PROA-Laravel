<x-filament-panels::page>
    {{ $this->form }}

    <hr class="my-6 border-gray-200 dark:border-gray-700">

    <div class="mb-8">
        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
            <span class="w-2 h-8 bg-green-500 rounded-sm inline-block"></span>
            Normam 211 - CHA (Habilitação Amador/Motonauta)
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Requerimento Motonauta</span></x-slot>
                {{ $this->gerarAnexo3AClienteAction }}
            </x-filament::section>

            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Atestado Motonauta</span></x-slot>
                {{ $this->gerarAnexo3BClienteAction }}
            </x-filament::section>

            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Extravio CHA</span></x-slot>
                {{ $this->gerarAnexo5DClienteAction }}
            </x-filament::section>

            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Atestado Arrais</span></x-slot>
                {{ $this->gerarAnexo5EClienteAction }}
            </x-filament::section>

            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Requerimento Geral CHA</span></x-slot>
                {{ $this->gerarAnexo5HClienteAction }}
            </x-filament::section>

            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Residência (Cliente)</span></x-slot>
                {{ $this->gerarAnexo2LClienteAction }}
            </x-filament::section>
        </div>
    </div>

    <div>
        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
            <span class="w-2 h-8 bg-blue-500 rounded-sm inline-block"></span>
            Normam 211 - TIE (Inscrição e Transferência)
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Atualização (BSADE)</span></x-slot>
                {{ $this->gerarAnexo2DAction }}
            </x-filament::section>

            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Requerimento TIE</span></x-slot>
                {{ $this->gerarAnexo2EAction }}
            </x-filament::section>

            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Perda/Extravio</span></x-slot>
                {{ $this->gerarAnexo2JAction }}
            </x-filament::section>

            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Comunicação Transf.</span></x-slot>
                {{ $this->gerarAnexo2KAction }}
            </x-filament::section>

            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Residência (Embarcação)</span></x-slot>
                {{ $this->gerarAnexo2LAction }}
            </x-filament::section>

            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Autorização Transf.</span></x-slot>
                {{ $this->gerarAnexo2MAction }}
            </x-filament::section>

            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Termo Resp. Inscrição</span></x-slot>
                {{ $this->gerarAnexo3CAction }}
            </x-filament::section>

            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Termo Construção</span></x-slot>
                {{ $this->gerarAnexo3DAction }}
            </x-filament::section>
        </div>
    </div>

    <div class="mt-8 mb-12">
        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
            <span class="w-2 h-8 bg-orange-500 rounded-sm inline-block"></span>
            Normam 212 (Motoaquática)
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Declaração Residência</span></x-slot>
                {{ $this->gerarAnexo1CAction }}
            </x-filament::section>
            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Requerimento Geral</span></x-slot>
                {{ $this->gerarAnexo2AAction }}
            </x-filament::section>
            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Boletim BDMOTO</span></x-slot>
                {{ $this->gerarAnexo2BAction }}
            </x-filament::section>
            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Perda/Extravio</span></x-slot>
                {{ $this->gerarAnexo2D212Action }}
            </x-filament::section>
            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Autorização Transf.</span></x-slot>
                {{ $this->gerarAnexo2E212Action }}
            </x-filament::section>
            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Recibo Venda (2F)</span></x-slot>
                {{ $this->gerarAnexo2F212Action }}
            </x-filament::section>
        </div>
    </div>

    <div class="mt-8 mb-12">
        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
            <span class="w-2 h-8 bg-red-500 rounded-sm inline-block"></span>
            Documentos Administrativos
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Representação</span></x-slot>
                {{ $this->gerarProcuracaoClienteAction }}
            </x-filament::section>

            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Procuração 02</span></x-slot>
                {{ $this->gerarProcuracao02Action }}
            </x-filament::section>

            <x-filament::section class="h-full">
                <x-slot name="heading"><span class="text-sm">Defesa de Infração</span></x-slot>
                {{ $this->gerarDefesaInfracaoAction }}
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>