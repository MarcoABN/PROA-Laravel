<x-filament-panels::page>
    {{--
        Apenas a disposição dos botões e o espaçamento. A caixa de seleção de
        cliente/embarcação fica exatamente como o Filament a renderiza — mexer nela
        (envolvê-la em outra div, trocar o Section por Grid) foi o que quebrou a tela.

        Utilitários do Tailwind como grid-cols-4, lg: e 2xl: não existem no CSS
        compilado do Filament, por isso a grade usa auto-fill: ela se ajusta sozinha
        de 1333px a 1440p, sem depender de breakpoint.
    --}}
    <style>
        .proa-anexos { display: flex; flex-direction: column; gap: 1.5rem; }

        .proa-anexos__grupo-titulo { display: flex; align-items: center; gap: .625rem;
            margin: 0 0 .75rem; font-size: .9375rem; font-weight: 700;
            color: rgb(var(--gray-800)); }
        .dark .proa-anexos__grupo-titulo { color: rgb(var(--gray-100)); }

        .proa-anexos__marca { display: inline-block; width: .3125rem; height: 1.25rem;
            border-radius: 9999px; flex-shrink: 0; }

        /* Colunas fluidas, sem breakpoints. */
        .proa-anexos__grade { display: grid; gap: .625rem;
            grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr)); }

        /* Precisa ser mais específica que a regra de display abaixo, senão o
           [hidden] do navegador perde e o item oculto reaparece. */
        .proa-anexos__item[hidden] { display: none; }

        .proa-anexos__item { display: flex; flex-direction: column; gap: .5rem;
            padding: .75rem; border-radius: .625rem; background-color: #fff;
            border: 1px solid rgb(var(--gray-200)); transition: border-color .15s, box-shadow .15s; }
        .proa-anexos__item:hover { border-color: rgb(var(--primary-300));
            box-shadow: 0 1px 3px rgba(var(--gray-950), .08); }
        .dark .proa-anexos__item { background-color: rgb(var(--gray-900));
            border-color: rgba(255, 255, 255, .1); }
        .dark .proa-anexos__item:hover { border-color: rgba(var(--primary-400), .5); }

        .proa-anexos__rotulo { margin: 0; font-size: .8125rem; font-weight: 600;
            line-height: 1.35; color: rgb(var(--gray-700)); }
        .dark .proa-anexos__rotulo { color: rgb(var(--gray-200)); }

        .proa-anexos__item .fi-btn { width: 100%; justify-content: center;
            padding-top: .3125rem; padding-bottom: .3125rem; margin-top: auto; }
    </style>

    {{ $this->form }}

    <div class="proa-anexos">

        {{-- NORMAM 211 - CHA --}}
        <section>
            <h2 class="proa-anexos__grupo-titulo">
                <span class="proa-anexos__marca" style="background-color: rgb(var(--success-500));"></span>
                Normam 211 - CHA (Habilitação Amador/Motonauta)
            </h2>

            <div class="proa-anexos__grade">
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Requerimento Motonauta</p>
                    {{ $this->gerarAnexo3AClienteAction }}
                </div>
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Atestado Motonauta</p>
                    {{ $this->gerarAnexo3BClienteAction }}
                </div>
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Extravio CHA</p>
                    {{ $this->gerarAnexo5DClienteAction }}
                </div>
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Requerimento Arrais</p>
                    {{ $this->gerarAnexo5HClienteAction }}
                </div>
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Atestado Arrais</p>
                    {{ $this->gerarAnexo5EClienteAction }}
                </div>
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Declaração de Residência</p>
                    {{ $this->gerarDeclaracaoResidenciaClienteAction }}
                </div>
            </div>
        </section>

        {{-- NORMAM 211 - TIE --}}
        <section>
            <h2 class="proa-anexos__grupo-titulo">
                <span class="proa-anexos__marca" style="background-color: rgb(var(--primary-500));"></span>
                Normam 211 - TIE (Inscrição e Transferência)
            </h2>

            <div class="proa-anexos__grade">
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">BSADE - Boletim Simplificado</p>
                    {{ $this->gerarBsadeAction }}
                </div>
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Requerimento TIE</p>
                    {{ $this->gerarRequerimentoServicoAction }}
                </div>
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Declaração de Perda/Extravio</p>
                    {{ $this->gerarDeclaracaoPerdaAction }}
                </div>
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Comunicado Transferência</p>
                    {{ $this->gerarComunicadoTransferenciaAction }}
                </div>
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Declaração de Residência (Embarcação)</p>
                    {{ $this->gerarDeclaracaoResidenciaAction }}
                </div>
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Autorização Transferência</p>
                    {{ $this->gerarAutorizacaoTransferenciaAction }}
                </div>
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Termo Resp. Inscrição (DESCONTINUADO)</p>
                    {{ $this->gerarAnexo3CAction }}
                </div>
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Termo Construção</p>
                    {{ $this->gerarTermoResponsabilidadeAction }}
                </div>
            </div>
        </section>

        {{-- NORMAM 212 --}}
        <section>
            <h2 class="proa-anexos__grupo-titulo">
                <span class="proa-anexos__marca" style="background-color: rgb(var(--warning-500));"></span>
                Normam 212 (Motoaquática)
            </h2>

            <div class="proa-anexos__grade">
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Declaração de Residência</p>
                    {{ $this->gerarAnexo1CAction }}
                </div>
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Requerimento Geral</p>
                    {{ $this->gerarAnexo2AAction }}
                </div>
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Boletim BDMOTO</p>
                    {{ $this->gerarAnexo2BAction }}
                </div>
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Perda/Extravio</p>
                    {{ $this->gerarDeclaracaoPerdaMotoaquaticaAction }}
                </div>
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Autorização Transf.</p>
                    {{ $this->gerarAutorizacaoTransferenciaMotoaquaticaAction }}
                </div>
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Comunicação Transf.</p>
                    {{ $this->gerarComunicadoTransferenciaMotoaquaticaAction }}
                </div>
            </div>
        </section>

        {{-- ADMINISTRATIVOS --}}
        <section>
            <h2 class="proa-anexos__grupo-titulo">
                <span class="proa-anexos__marca" style="background-color: rgb(var(--danger-500));"></span>
                Documentos Administrativos
            </h2>

            <div class="proa-anexos__grade">
                {{-- Opção legada, mantida oculta --}}
                <div class="proa-anexos__item" hidden>
                    <p class="proa-anexos__rotulo">Representação</p>
                    {{ $this->gerarProcuracaoClienteAction }}
                </div>

                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Procuração</p>
                    {{ $this->gerarProcuracao02Action }}
                </div>
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Defesa de Infração</p>
                    {{ $this->gerarDefesaInfracaoAction }}
                </div>
                <div class="proa-anexos__item">
                    <p class="proa-anexos__rotulo">Pedido de Informação</p>
                    {{ $this->gerarPedidoInformacaoClienteAction }}
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
