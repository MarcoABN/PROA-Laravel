@php
    use App\Support\OrganizacoesMilitaresSisap;

    $oms = OrganizacoesMilitaresSisap::lista();
@endphp

{{--
    Lista de OMs do SISAP para consulta ao cadastrar a capitania.
    Estilos inline de propósito: o painel usa o CSS compilado do Filament, sem as utilitárias novas.
--}}
<div
    x-data="{
        busca: '',
        normalizar(texto) { return String(texto).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); },
        mostra(linha) { return this.busca === '' || this.normalizar(linha).includes(this.normalizar(this.busca)); },
    }"
>
    <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.75rem;">
        Código que o SISAP usa para cada Organização Militar (levantado em {{ OrganizacoesMilitaresSisap::LEVANTADO_EM }}).
        Copie o código da OM desta capitania para o campo "Código da OM no SISAP".
    </p>

    <input
        type="search"
        x-model="busca"
        placeholder="Buscar por nome ou código…"
        style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; margin-bottom: 0.75rem; font-size: 0.875rem;"
    >

    <div style="max-height: 55vh; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
            <thead>
                <tr style="position: sticky; top: 0; background: #f9fafb;">
                    <th style="text-align: left; padding: 0.5rem 0.75rem; border-bottom: 1px solid #e5e7eb;">Organização Militar</th>
                    <th style="text-align: right; padding: 0.5rem 0.75rem; border-bottom: 1px solid #e5e7eb; width: 7rem;">Código SISAP</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($oms as $codigo => $nome)
                    <tr x-show="mostra(@js($nome . ' ' . $codigo))">
                        <td style="padding: 0.4rem 0.75rem; border-bottom: 1px solid #f3f4f6;">{{ $nome }}</td>
                        <td style="padding: 0.4rem 0.75rem; border-bottom: 1px solid #f3f4f6; text-align: right; font-family: ui-monospace, Consolas, monospace; font-weight: 600;">
                            <span style="user-select: all;">{{ $codigo }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
