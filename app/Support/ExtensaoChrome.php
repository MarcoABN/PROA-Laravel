<?php

namespace App\Support;

use FilesystemIterator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

/**
 * A extensão do Chrome distribuída pelo próprio PROA (pasta extensao-chrome do projeto).
 *
 * A versão "oficial" é a do manifest.json publicado no servidor: ao alterar a extensão,
 * aumente a version do manifest para o PROA avisar quem ficou com a antiga.
 */
class ExtensaoChrome
{
    private const PASTA = 'extensao-chrome';
    private const PASTA_NO_ZIP = 'proa-agendamento-marinha';

    /** Arquivos de desenvolvimento que não vão para os usuários. */
    private const FORA_DO_PACOTE = ['tests', 'package.json'];

    private static ?string $versaoAtual = null;

    public static function versaoAtual(): ?string
    {
        if (static::$versaoAtual === null) {
            $manifesto = json_decode((string) @file_get_contents(base_path(self::PASTA . '/manifest.json')), true);
            static::$versaoAtual = (string) ($manifesto['version'] ?? '');
        }

        return static::$versaoAtual !== '' ? static::$versaoAtual : null;
    }

    public static function desatualizada(?string $versao): bool
    {
        $atual = static::versaoAtual();

        return filled($versao) && $atual !== null && version_compare($versao, $atual, '<');
    }

    /** Guarda a versão usada com o token. Grava no máximo uma vez por hora se nada mudou. */
    public static function registrarUso(Model $dono, ?string $versao): void
    {
        $versao = substr(preg_replace('/[^0-9.]/', '', (string) $versao), 0, 20);

        if ($versao === '') {
            return;
        }

        if ($dono->sisap_extensao_versao === $versao && $dono->sisap_extensao_vista_em?->gt(now()->subHour())) {
            return;
        }

        $dono->forceFill(['sisap_extensao_versao' => $versao, 'sisap_extensao_vista_em' => now()])->saveQuietly();
    }

    /** Aviso para quem usa uma versão antiga (null se está em dia ou nunca usou). */
    public static function aviso(?string $versaoUsada): ?string
    {
        if (!static::desatualizada($versaoUsada)) {
            return null;
        }

        return "A extensão do Chrome usada com o seu token está na versão {$versaoUsada}, mas a atual é " . static::versaoAtual() . '. '
            . 'Clique em "Baixar extensão", substitua a pasta da extensão pelo conteúdo do zip e clique em Recarregar (↻) em chrome://extensions.';
    }

    public static function nomeDoArquivo(): string
    {
        return self::PASTA_NO_ZIP . '-' . (static::versaoAtual() ?? 'dev') . '.zip';
    }

    /** Monta o zip num arquivo temporário e devolve o caminho. */
    public static function gerarZip(): string
    {
        $origem = realpath(base_path(self::PASTA));

        if (!$origem || !class_exists(ZipArchive::class)) {
            throw new RuntimeException('Não foi possível gerar o pacote da extensão neste servidor.');
        }

        $destino = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'proa-extensao-' . Str::random(12) . '.zip';
        $zip = new ZipArchive();
        $zip->open($destino, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $arquivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($origem, FilesystemIterator::SKIP_DOTS));

        foreach ($arquivos as $arquivo) {
            if (!$arquivo->isFile()) {
                continue;
            }

            $relativo = str_replace('\\', '/', substr($arquivo->getPathname(), strlen($origem) + 1));

            if (in_array(Str::before($relativo, '/'), self::FORA_DO_PACOTE, true)) {
                continue;
            }

            $zip->addFile($arquivo->getPathname(), self::PASTA_NO_ZIP . '/' . $relativo);
        }

        $zip->close();

        return $destino;
    }
}
