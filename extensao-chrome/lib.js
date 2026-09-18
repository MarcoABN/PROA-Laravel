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

  /**
   * Separa as letras do CAPTCHA (cinza-escuro) das letras de ruído (cinza-claro) para o usuário
   * ler com mais facilidade. Só trata a imagem: quem lê e digita é sempre o usuário.
   *
   * rgba: pixels da imagem (ImageData.data). Retorna a máscara (1 = letra) e o limiar de brilho usado.
   * O limiar é automático (Otsu entre os pixels de tinta, preso à faixa mais escura) ou o informado.
   */
  function limparCaptcha(rgba, largura, altura, { limiar = null, fundo = 235, menorMancha = 6 } = {}) {
    const total = largura * altura;
    const brilho = new Uint8Array(total);
    const histograma = new Array(256).fill(0);
    let tinta = 0;

    for (let i = 0; i < total; i++) {
      const p = i * 4;
      // Pixel transparente conta como fundo branco.
      const alfa = rgba[p + 3] / 255;
      const l = Math.round((0.299 * rgba[p] + 0.587 * rgba[p + 1] + 0.114 * rgba[p + 2]) * alfa + 255 * (1 - alfa));
      brilho[i] = l;
      if (l < fundo) {
        histograma[l]++;
        tinta++;
      }
    }

    if (limiar === null) {
      limiar = limiarAutomatico(histograma, tinta, fundo);
    }

    const mascara = new Uint8Array(total);
    for (let i = 0; i < total; i++) {
      mascara[i] = brilho[i] <= limiar ? 1 : 0;
    }

    removerManchas(mascara, largura, altura, menorMancha);

    return { mascara, limiar };
  }

  function limiarAutomatico(histograma, tinta, fundo) {
    if (tinta === 0) {
      return 0;
    }

    // Otsu: o corte que melhor separa os tons de tinta em duas turmas (letras x ruído).
    let somaTotal = 0;
    for (let l = 0; l < fundo; l++) {
      somaTotal += l * histograma[l];
    }

    let melhor = 0;
    let melhorVariancia = -1;
    let pesoEscuro = 0;
    let somaEscuro = 0;

    for (let l = 0; l < fundo; l++) {
      pesoEscuro += histograma[l];
      if (pesoEscuro === 0) {
        continue;
      }
      const pesoClaro = tinta - pesoEscuro;
      if (pesoClaro === 0) {
        break;
      }
      somaEscuro += l * histograma[l];
      const mediaEscuro = somaEscuro / pesoEscuro;
      const mediaClaro = (somaTotal - somaEscuro) / pesoClaro;
      const variancia = pesoEscuro * pesoClaro * (mediaEscuro - mediaClaro) ** 2;
      if (variancia > melhorVariancia) {
        melhorVariancia = variancia;
        melhor = l;
      }
    }

    // Com vários tons de ruído, o Otsu pode cortar entre dois cinzas claros: limita a faixa das
    // letras a pouco acima do tom mais escuro presente (desconsidera 0,5% de pontos soltos).
    let acumulado = 0;
    let maisEscuro = 0;
    for (let l = 0; l < fundo; l++) {
      acumulado += histograma[l];
      if (acumulado >= tinta * 0.005) {
        maisEscuro = l;
        break;
      }
    }

    return Math.min(melhor, maisEscuro + 35);
  }

  /** Apaga grupos de pixels de letra menores que `menor` (pontas de ruído que passaram no limiar). */
  function removerManchas(mascara, largura, altura, menor) {
    if (menor <= 1) {
      return;
    }

    const visto = new Uint8Array(mascara.length);
    const pilha = [];
    const grupo = [];

    for (let inicio = 0; inicio < mascara.length; inicio++) {
      if (!mascara[inicio] || visto[inicio]) {
        continue;
      }

      grupo.length = 0;
      pilha.push(inicio);
      visto[inicio] = 1;

      while (pilha.length) {
        const i = pilha.pop();
        grupo.push(i);
        const x = i % largura;
        const y = (i - x) / largura;

        for (let dy = -1; dy <= 1; dy++) {
          for (let dx = -1; dx <= 1; dx++) {
            const nx = x + dx;
            const ny = y + dy;
            if (nx < 0 || ny < 0 || nx >= largura || ny >= altura) {
              continue;
            }
            const j = ny * largura + nx;
            if (mascara[j] && !visto[j]) {
              visto[j] = 1;
              pilha.push(j);
            }
          }
        }
      }

      if (grupo.length < menor) {
        for (const i of grupo) {
          mascara[i] = 0;
        }
      }
    }
  }

  /** Máscara → pixels RGBA: letras pretas sobre fundo branco. */
  function pintarMascara(mascara) {
    const rgba = new Uint8ClampedArray(mascara.length * 4);
    for (let i = 0; i < mascara.length; i++) {
      const cor = mascara[i] ? 17 : 255;
      rgba[i * 4] = cor;
      rgba[i * 4 + 1] = cor;
      rgba[i * 4 + 2] = cor;
      rgba[i * 4 + 3] = 255;
    }
    return rgba;
  }

  const Lib = {
    limparCaptcha,
    pintarMascara,
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
