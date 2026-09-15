/*
 * Funções puras (sem DOM, sem Chrome) usadas pelo content script.
 * Testadas em node: node --test "tests/*.test.mjs" (dentro de extensao-chrome)
 */
(function (raiz) {
  'use strict';

  function normalizar(texto) {
    return String(texto ?? '')
      .normalize('NFD')
      .replace(/[̀-ͯ]/g, '')
      // Travessão/meia-risca (– — −) valem como hífen: o texto copiado de documentos costuma trazê-los.
      .replace(/[‐-―−]/g, '-')
      .replace(/\s+/g, ' ')
      .trim()
      .toUpperCase();
  }

  function somenteDigitos(valor) {
    return String(valor ?? '').replace(/\D/g, '');
  }

  function formatarCpf(valor) {
    const d = somenteDigitos(valor);
    return d.length === 11 ? d.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4') : String(valor ?? '');
  }

  function formatarData(ymd) {
    const [ano, mes, dia] = String(ymd ?? '').split('-');
    return ano && mes && dia ? `${dia}/${mes}/${ano}` : String(ymd ?? '');
  }

  /** Os endpoints do SISAP respondem ora objeto, ora lista com um objeto. */
  function primeiro(json) {
    return Array.isArray(json) ? json[0] : json;
  }

  /**
   * agendadespachante.php → horários livres do mês.
   * @returns {{ok: boolean, mensagem: string, horarios: Array<{data: string, hora: string, turno: 'manha'|'tarde', token: string|null}>}}
   */
  function extrairHorarios(json) {
    const r = primeiro(json);

    if (!r || r.lretorno !== true) {
      return { ok: false, mensagem: (r && r.cmensagem) || 'O SISAP não devolveu a agenda.', horarios: [] };
    }

    const horarios = [];

    for (const dia of r.agenda || []) {
      const partes = String(dia.ddata || '').split('/');
      if (partes.length !== 3) {
        continue;
      }

      const data = `${partes[0]}-${partes[1].padStart(2, '0')}-${partes[2].padStart(2, '0')}`;

      for (const turno of ['turnomanha', 'turnotarde']) {
        for (const horario of dia[turno] || []) {
          const m = String(horario.choriarioinicial || '').match(/^(\d{1,2}):(\d{2})/);
          if (m) {
            horarios.push({
              data,
              hora: `${m[1].padStart(2, '0')}:${m[2]}`,
              turno: turno === 'turnomanha' ? 'manha' : 'tarde',
              token: horario.cidshorarios || null,
            });
          }
        }
      }
    }

    return { ok: true, mensagem: r.cmensagem || '', horarios };
  }

  /** enviaagendamentodespachante.php → número, chave e id do agendamento. */
  function resultadoDoEnvio(json) {
    const r = primeiro(json);

    if (r && r.lretorno === true && r.cnumeroagendamento) {
      return {
        ok: true,
        numero: String(r.cnumeroagendamento),
        chave: String(r.cchaveconfirmacao || ''),
        sisapId: r.cidagendamento != null ? String(r.cidagendamento) : null,
      };
    }

    return { ok: false, mensagem: (r && r.cmensagem) || 'O SISAP recusou o agendamento.' };
  }

  /** Descobre qual horário foi realmente enviado, pelo token cidshorario do formulário. */
  function horarioEnviado(corpo, horarios) {
    const token = new URLSearchParams(corpo || '').get('cidshorario');
    const encontrado = token ? (horarios || []).find((h) => h.token === token) : null;
    return encontrado ? { data: encontrado.data, hora: encontrado.hora } : null;
  }

  /** mainpage.php → lista de OMs {nidom, nome}, onde quer que estejam no JSON. */
  function encontrarOms(json) {
    const oms = new Map();

    const visitar = (valor) => {
      if (Array.isArray(valor)) {
        valor.forEach(visitar);
      } else if (valor && typeof valor === 'object') {
        if ('nidom' in valor && 'cnomedaom' in valor) {
          oms.set(String(valor.nidom), { nidom: String(valor.nidom), nome: String(valor.cnomedaom) });
        }
        Object.values(valor).forEach(visitar);
      }
    };

    visitar(json);
    return [...oms.values()];
  }

  /** "AGENDAMENTO PARA REPRESENTANTE LEGAL - ETAPA 3 DE 5" → 3 */
  function etapaDoTexto(texto) {
    const m = normalizar(texto).match(/REPRESENTANTE LEGAL - ETAPA (\d) DE 5/);
    return m ? Number(m[1]) : null;
  }

  /** O primeiro CPF da página é o do usuário logado, no cabeçalho ("NOME | 000.000.000-00 | OURO"). */
  function cpfDoCabecalho(texto) {
    const m = String(texto ?? '').match(/\d{3}\.\d{3}\.\d{3}-\d{2}/);
    return m ? somenteDigitos(m[0]) : null;
  }

  /** auth.php → CPF (11 dígitos) do usuário logado no SISAP, ou null. */
  function cpfDoLogin(json) {
    const r = primeiro(json);
    const cpf = somenteDigitos(r && r.logado !== false ? r.cpfcnpj : '');
    return cpf.length === 11 ? cpf : null;
  }

  /** "NOME | 000.000.000-00 | OURO | SAIR" → "NOME" */
  function nomeDoCabecalho(texto) {
    const m = String(texto ?? '').match(/([^\n|]+?)\s*\|\s*\d{3}\.\d{3}\.\d{3}-\d{2}/);
    return m ? m[1].trim() : null;
  }

  /** "SERVIÇOS 2 DE 5" → {usados: 2, total: 5} */
  function contadorServicos(texto) {
    const m = normalizar(texto).match(/SERVICOS (\d+) DE (\d+)/);
    return m ? { usados: Number(m[1]), total: Number(m[2]) } : null;
  }

  const Lib = {
    normalizar,
    somenteDigitos,
    formatarCpf,
    formatarData,
    primeiro,
    extrairHorarios,
    resultadoDoEnvio,
    horarioEnviado,
    encontrarOms,
    etapaDoTexto,
    cpfDoCabecalho,
    cpfDoLogin,
    nomeDoCabecalho,
    contadorServicos,
  };

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = Lib;
  } else {
    raiz.ProaLib = Lib;
  }
})(typeof window !== 'undefined' ? window : globalThis);
