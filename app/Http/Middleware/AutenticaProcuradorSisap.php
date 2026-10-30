<?php

namespace App\Http\Middleware;

use App\Models\Prestador;
use App\Models\SisapToken;
use App\Models\User;
use App\Support\ExtensaoChrome;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica a extensão do Chrome e descobre de qual procurador são os agendamentos.
 *
 * - Token de usuário do PROA: o procurador vem do CPF logado no SISAP (cabeçalho X-Procurador-Cpf) ou do
 *   CNPJ, quando a empresa entra com certificado digital.
 *   Um navegador só atende vários procuradores trocando o login do gov.br.
 * - Token de procurador (individual): só aquele procurador; se o SISAP estiver logado com outro CPF, recusa.
 *
 * Deixa em $request->attributes: 'usuario' (se token de usuário), 'prestador' (quando identificado)
 * e 'erroProcurador' (motivo, quando não identificado).
 */
class AutenticaProcuradorSisap
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $cpf = preg_replace('/\D/', '', (string) $request->header('X-Procurador-Cpf'));

        // Uso por navegador: cada token é de um Chrome.
        if ($token) {
            SisapToken::porValor($token)?->registrarUso($request->header('X-Extensao-Versao'));
        }

        if ($token && ($prestador = Prestador::porTokenSisap($token))) {
            if ($cpf !== '' && $cpf !== preg_replace('/\D/', '', (string) $prestador->cpfcnpj)) {
                return response()->json([
                    'message' => "O SISAP está logado com outro CPF/CNPJ, mas o token da extensão é do procurador {$prestador->nome}. "
                        . 'Use um token de usuário (Agendamentos Marinha → Tokens da extensão → Gerar token) para trocar de procurador.',
                ], 403);
            }

            $request->attributes->set('prestador', $prestador);
            ExtensaoChrome::registrarUso($prestador, $request->header('X-Extensao-Versao'));

            return $next($request);
        }

        $usuario = $token ? User::porTokenSisap($token) : null;

        if (!$usuario) {
            return response()->json(['message' => 'Token da extensão inválido, revogado ou sem permissão de agendamento.'], 401);
        }

        $request->attributes->set('usuario', $usuario);
        ExtensaoChrome::registrarUso($usuario, $request->header('X-Extensao-Versao'));

        if ($cpf === '') {
            $request->attributes->set('erroProcurador', 'Faça login no SISAP pelo gov.br para carregar os agendamentos do procurador.');
        } elseif ($prestador = Prestador::procuradorPorCpf($cpf)) {
            $request->attributes->set('prestador', $prestador);
        } else {
            // CNPJ: login com certificado digital da empresa, que precisa estar cadastrada como procurador.
            $documento = match (strlen($cpf)) {
                11 => 'O CPF ' . preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf),
                14 => 'O CNPJ ' . preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cpf),
                default => "O documento {$cpf}",
            };
            $request->attributes->set('erroProcurador', "{$documento} logado no SISAP não está cadastrado como procurador no PROA.");
        }

        return $next($request);
    }
}
