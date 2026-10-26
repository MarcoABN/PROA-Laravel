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

    {{--
        CPF/CNPJ completo e válido → o cursor vai para a GRU da mesma linha. CNPJ completa já na hora;
        CPF espera um instante, porque os 11 primeiros dígitos de um CNPJ podem formar um CPF válido.
    --}}
    <script>
        window.proaPularParaGru ??= (() => {
            let espera;
            const digitos = (v) => (v || '').replace(/\D/g, '');

            const cpfValido = (c) => {
                if (c.length !== 11 || /^(\d)\1{10}$/.test(c)) return false;
                for (let p = 9; p < 11; p++) {
                    let soma = 0;
                    for (let i = 0; i < p; i++) soma += Number(c[i]) * (p + 1 - i);
                    if (Number(c[p]) !== ((10 * soma) % 11) % 10) return false;
                }
                return true;
            };

            const cnpjValido = (c) => {
                if (c.length !== 14 || /^(\d)\1{13}$/.test(c)) return false;
                const dv = (base) => {
                    let soma = 0, peso = base.length - 7;
                    for (const n of base) {
                        soma += Number(n) * peso--;
                        if (peso < 2) peso = 9;
                    }
                    const r = soma % 11;
                    return r < 2 ? 0 : 11 - r;
                };
                return dv(c.slice(0, 12)) === Number(c[12]) && dv(c.slice(0, 13)) === Number(c[13]);
            };

            return (event) => {
                const campo = event.target;
                const pular = () => campo.closest('.fi-fo-repeater-item')?.querySelector('[data-campo="gru"]')?.focus();
                const d = digitos(campo.value);

                clearTimeout(espera);

                if (cnpjValido(d)) {
                    pular();
                } else if (cpfValido(d)) {
                    espera = setTimeout(() => {
                        if (document.activeElement === campo && digitos(campo.value).length === 11) pular();
                    }, 700);
                }
            };
        })();
    </script>

    <x-filament-panels::form wire:submit="salvar">
        {{ $this->form }}

        <x-filament-panels::form.actions :actions="$this->getFormActions()" />
    </x-filament-panels::form>
</x-filament-panels::page>
