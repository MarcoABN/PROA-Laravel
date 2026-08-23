<?php

namespace App\Models\Concerns;

use App\Models\FinanceiroLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Grava na trilha de auditoria quem criou, alterou ou excluiu o registro.
 *
 * Os valores são formatados na hora da gravação, não na exibição: se amanhã uma
 * categoria for renomeada ou um usuário removido, o log continua mostrando o que
 * de fato estava valendo quando a movimentação aconteceu.
 *
 * O model que usa este trait precisa de:
 *   - rotulosAuditoria(): array   campo => rótulo dos campos que devem ser auditados
 *   - descricaoAuditoria(): string  retrato curto do registro
 *   - formatarValorAuditoria(string $campo, $valor): ?string  (opcional)
 */
trait RegistraLogFinanceiro
{
    /**
     * Retrato do registro colhido em "deleting", enquanto a linha ainda existe.
     * Propriedade declarada de propósito: se fosse atribuída solta viraria um
     * atributo do Eloquent e tentaria ir para o banco.
     */
    protected ?string $retratoParaExclusao = null;

    public static function bootRegistraLogFinanceiro(): void
    {
        static::created(function (Model $model) {
            $model->gravarLogFinanceiro(FinanceiroLog::EVENTO_CRIADO, $model->alteracoesDaCriacao());
        });

        static::updated(function (Model $model) {
            $alteracoes = $model->alteracoesDaEdicao();

            // Salvar sem mudar nada de relevante (só o updated_at) não vira log.
            if (empty($alteracoes)) {
                return;
            }

            $model->gravarLogFinanceiro(FinanceiroLog::EVENTO_ATUALIZADO, $alteracoes);
        });

        // O retrato é colhido antes de a linha sumir e a partir do que está
        // gravado: a instância em memória pode estar desatualizada (alguém editou
        // o registro em outra tela), e um log que descreve errado o que foi
        // excluído não serve como prova.
        static::deleting(function (Model $model) {
            $model->retratoParaExclusao = ($model->fresh() ?? $model)->descricaoAuditoria();
        });

        static::deleted(function (Model $model) {
            $model->gravarLogFinanceiro(FinanceiroLog::EVENTO_EXCLUIDO, []);
        });
    }

    protected function gravarLogFinanceiro(string $evento, array $alteracoes): void
    {
        $user = Auth::user();

        FinanceiroLog::create([
            'registro_tipo'      => static::class,
            'registro_id'        => $this->getKey(),
            'registro_descricao' => $this->retratoParaExclusao ?? $this->descricaoAuditoria(),
            'evento'             => $evento,
            'user_id'            => $user?->getKey(),
            // Ações fora do painel (comando, seeder, tinker) não têm usuário logado.
            'user_nome'          => $user?->name ?? 'Sistema',
            'alteracoes'         => $alteracoes ?: null,
            'ip'                 => request()->ip(),
        ]);
    }

    /**
     * Na criação registra o estado inicial dos campos preenchidos.
     */
    protected function alteracoesDaCriacao(): array
    {
        $alteracoes = [];

        foreach ($this->rotulosAuditoria() as $campo => $rotulo) {
            $valor = $this->formatarValorAuditoria($campo, $this->getAttribute($campo));

            if ($valor === null || $valor === '') {
                continue;
            }

            $alteracoes[] = [
                'campo'  => $campo,
                'rotulo' => $rotulo,
                'de'     => null,
                'para'   => $valor,
            ];
        }

        return $alteracoes;
    }

    /**
     * Na edição registra apenas o que mudou, com o antes e o depois.
     */
    protected function alteracoesDaEdicao(): array
    {
        $alteracoes = [];
        $rotulos = $this->rotulosAuditoria();

        foreach (array_keys($this->getChanges()) as $campo) {
            if (! isset($rotulos[$campo])) {
                continue;
            }

            $de   = $this->formatarValorAuditoria($campo, $this->getOriginal($campo));
            $para = $this->formatarValorAuditoria($campo, $this->getAttribute($campo));

            // getChanges() compara o valor bruto; o formatado pode ser igual
            // (ex.: '10.00' e 10.0 viram "R$ 10,00"). Aí não houve mudança real.
            if ($de === $para) {
                continue;
            }

            $alteracoes[] = [
                'campo'  => $campo,
                'rotulo' => $rotulos[$campo],
                'de'     => $de,
                'para'   => $para,
            ];
        }

        return $alteracoes;
    }

    /**
     * Formatação padrão; os models sobrescrevem para tratar seus campos.
     */
    protected function formatarValorAuditoria(string $campo, $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_bool($valor)) {
            return $valor ? 'Sim' : 'Não';
        }

        if (is_array($valor)) {
            return count($valor) . ' item(ns)';
        }

        return (string) $valor;
    }

    public function logs()
    {
        return $this->hasMany(FinanceiroLog::class, 'registro_id')
            ->where('registro_tipo', static::class)
            ->latest('id');
    }
}
