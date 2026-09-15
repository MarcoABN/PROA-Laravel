/*
 * Painel do PROA dentro do SISAP + automação das 5 etapas do agendamento para representante legal.
 *
 * Regras:
 * - Só clica e digita na tela do SISAP; nunca chama os endpoints dele por conta própria.
 * - Qualquer passo que não der para automatizar vira uma pausa: o usuário faz à mão e clica em Continuar.
 * - O CAPTCHA e o clique em FINALIZAR são sempre do usuário.
 *
 * O SISAP usa Quasar 0.x (classes q-if, q-popover, q-option) e um stepper próprio (.stepper-button).
 * Os seletores aceitam também as classes do Quasar 1.x, caso o site seja atualizado.
 */
(() => {
  'use strict';

  if (window.__proaPainel) {
    return;
  }
  window.__proaPainel = true;

  const L = window.ProaLib;

  class Parado extends Error {}
  class ErroSisap extends Error {} // recusa do SISAP: vira "Falhou" no PROA

  // ------------------------------------------------------------------
  // Respostas do SISAP (vindas de page-hook.js)
  // ------------------------------------------------------------------

  const capturas = {};
  const esperas = [];

  window.addEventListener('message', (evento) => {
    if (evento.source !== window || !evento.data || evento.data.__proaSisap !== true) {
      return;
    }

    const captura = { ...evento.data, t: Date.now() };
    capturas[captura.endpoint] = captura;

    for (const espera of [...esperas]) {
      if (espera.endpoint === captura.endpoint) {
        esperas.splice(esperas.indexOf(espera), 1);
        espera.resolve(captura);
      }
    }

    aoCapturar(captura).catch((erro) => registrar(`Erro ao registrar no PROA: ${erro.message}`, 'erro'));
  });

  function esperarResposta(endpoint, desde, timeout = 25000) {
    const existente = capturas[endpoint];
    if (existente && existente.t >= desde) {
      return Promise.resolve(existente);
    }

    return new Promise((resolve, reject) => {
      const espera = { endpoint, resolve };
      esperas.push(espera);

      setTimeout(() => {
        const i = esperas.indexOf(espera);
        if (i >= 0) {
          esperas.splice(i, 1);
          reject(new Error(`o SISAP não respondeu (${endpoint})`));
        }
      }, timeout);
    });
  }

  // ------------------------------------------------------------------
  // PROA (via background.js)
  // ------------------------------------------------------------------

  /** Toda chamada leva o CPF logado no SISAP: com token de usuário, é ele que escolhe o procurador. */
  async function proa(acao, dados = {}) {
    const resposta = await chrome.runtime.sendMessage({ tipo: 'proa', acao, dados: { cpf: cpfLogado, ...dados } });
    if (!resposta || !resposta.ok) {
      throw new Error((resposta && resposta.erro) || 'Sem resposta do PROA.');
    }
    return resposta.dados;
  }

  // ------------------------------------------------------------------
  // Estado
  // ------------------------------------------------------------------

  let dadosProa = null;   // { procurador, agendamentos }
  let erroProa = null;
  let cpfLogado = null;   // CPF do usuário logado no SISAP (auth.php ou cabeçalho)
  let nomeLogado = null;
  let job = null;         // { id, horarios, slot, resultado }
  let executando = false;
  let parar = false;
  let passoAPasso = true;
  let repetirSemHorario = true;
  let continuar = null;   // resolve da pausa atual
  let minimizado = false;
  let desdeAgenda = 0;
  const logs = [];

  const salvarJob = () => chrome.storage.local.set({ proaJob: job });

  // ------------------------------------------------------------------
  // Utilitários de DOM
  // ------------------------------------------------------------------

  const dormir = (ms) => new Promise((r) => setTimeout(r, ms));
  const visivel = (el) => !!el && el.getClientRects().length > 0;
  const textoPagina = () => (document.body ? document.body.innerText : '');
  const todos = (seletor, raiz = document) => [...raiz.querySelectorAll(seletor)].filter(visivel);

  const SEL_CAMPO = '.q-if, .q-field';
  const SEL_ROTULO = '.q-if-label, .q-field__label';
  const SEL_OPCOES = '.q-popover .q-item, .q-menu .q-item';

  async function esperar(fn, { timeout = 15000, intervalo = 150, descricao = 'elemento' } = {}) {
    const fim = Date.now() + timeout;

    while (Date.now() < fim) {
      if (parar) {
        throw new Parado();
      }
      const resultado = fn();
      if (resultado) {
        return resultado;
      }
      await dormir(intervalo);
    }

    throw new Error(`não encontrei ${descricao}`);
  }

  function porTexto(seletor, texto, raiz = document) {
    const alvo = L.normalizar(texto);
    return todos(seletor, raiz).filter((el) => L.normalizar(el.innerText).includes(alvo));
  }

  function desabilitado(el) {
    return el.disabled
      || el.classList.contains('disabled')
      || el.classList.contains('deactivated')
      || el.getAttribute('aria-disabled') === 'true';
  }

  function botao(texto, raiz = document) {
    return porTexto('button, .q-btn', texto, raiz).find((el) => !desabilitado(el));
  }

  /**
   * Campo (moldura .q-if) cujo rótulo contém o texto.
   * No Quasar 0.x o .q-field é um invólucro externo que contém o .q-if; clicar no invólucro não abre
   * listas. Por isso fica só o campo mais interno (o que não contém outro campo dentro).
   */
  function camposPorRotulo(rotulo, raiz = document) {
    const alvo = L.normalizar(rotulo);
    return todos(SEL_CAMPO, raiz)
      .filter((campo) => !campo.querySelector(SEL_CAMPO))
      .filter((campo) => L.normalizar(campo.querySelector(SEL_ROTULO)?.innerText).includes(alvo));
  }

  function inputsPorRotulo(rotulo, raiz = document) {
    return camposPorRotulo(rotulo, raiz).map((campo) => campo.querySelector('input')).filter(Boolean);
  }

  function clicar(el) {
    el.scrollIntoView({ block: 'center' });
    for (const tipo of ['pointerdown', 'mousedown', 'pointerup', 'mouseup']) {
      el.dispatchEvent(new MouseEvent(tipo, { bubbles: true, cancelable: true, view: window }));
    }
    el.click();
  }

  /**
   * Digita tecla a tecla. A máscara do SISAP (jquery.mask) e as consultas de CPF/GRU só reagem a
   * eventos de teclado + saída do campo; definir o valor de uma vez não funciona.
   */
  async function digitar(input, valor, { sair = true } = {}) {
    const definir = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
    const tecla = (tipo, caractere) => input.dispatchEvent(new KeyboardEvent(tipo, {
      key: caractere, keyCode: caractere.charCodeAt(0), which: caractere.charCodeAt(0), bubbles: true, cancelable: true,
    }));

    input.scrollIntoView({ block: 'center' });
    input.focus();
    input.dispatchEvent(new FocusEvent('focusin', { bubbles: true }));

    definir.call(input, '');
    input.dispatchEvent(new Event('input', { bubbles: true }));

    for (const caractere of String(valor)) {
      tecla('keydown', caractere);
      tecla('keypress', caractere);
      definir.call(input, input.value + caractere);
      input.dispatchEvent(new InputEvent('input', { bubbles: true, data: caractere, inputType: 'insertText' }));
      tecla('keyup', caractere);
      await dormir(30);
    }

    if (sair) {
      input.dispatchEvent(new Event('change', { bubbles: true }));
      input.blur();
      input.dispatchEvent(new FocusEvent('focusout', { bubbles: true }));
    }
  }

  /** Sobe a partir de um elemento até o menor ancestral que satisfaz a condição. */
  function subirAte(el, condicao) {
    for (let atual = el.parentElement; atual; atual = atual.parentElement) {
      if (condicao(atual)) {
        return atual;
      }
    }
    return null;
  }

  function marcado(caixa) {
    return caixa.getAttribute('aria-checked') === 'true'
      || !!caixa.querySelector('.q-option-inner.active, .q-checkbox__inner--truthy');
  }

  /**
   * Caixa de marcação de um texto. Na etapa 1 o texto fica dentro da caixa ("Li e concordo");
   * na etapa 5 fica num parágrafo ao lado ("Estou ciente de que..."), então procura a caixa
   * mais próxima subindo a partir do texto.
   */
  function caixaDoTexto(texto) {
    const dentro = porTexto('.q-checkbox', texto)[0];
    if (dentro) {
      return dentro;
    }

    const alvo = L.normalizar(texto);
    const textoSolto = todos('p, span, div, label')
      .filter((el) => !el.closest('#proa-agendamento-painel'))
      .filter((el) => L.normalizar(el.innerText).startsWith(alvo))
      .sort((a, b) => a.innerText.length - b.innerText.length)[0];

    const bloco = textoSolto && subirAte(textoSolto, (el) => todos('.q-checkbox', el).length > 0);
    return bloco ? todos('.q-checkbox', bloco)[0] : null;
  }

  async function marcar(texto) {
    const caixa = await esperar(() => caixaDoTexto(texto), { descricao: `a caixa "${texto}"` });
    if (!marcado(caixa)) {
      clicar(caixa);
    }
    await esperar(() => marcado(caixa), { timeout: 3000, descricao: `a caixa "${texto}" marcada` });
  }

  /** Abre um q-select, opcionalmente filtra, e escolhe a opção com o texto exato (normalizado). */
  async function escolherNoSelect(select, texto, { filtrar = false, descricao } = {}) {
    const alvo = L.normalizar(texto);

    if (L.normalizar(select.innerText).includes(alvo)) {
      return;
    }

    // Um campo recém-criado (ex.: logo após "Adicionar interessado") ainda não abre a lista no primeiro
    // clique: o Quasar 0.x liga o popover um instante depois. Espera um pouco e repete, alternando
    // clique simples e sequência completa de eventos do mouse.
    const abriu = () => todos(SEL_OPCOES).length > 0;
    select.scrollIntoView({ block: 'center' });
    await dormir(400);

    for (let tentativa = 1; !abriu(); tentativa++) {
      if (tentativa > 4) {
        throw new Error('não encontrei a lista de opções');
      }
      if (tentativa % 2 === 1) {
        select.click();
      } else {
        clicar(select);
      }
      await esperar(abriu, { timeout: 1200, descricao: 'a lista de opções' }).catch(() => null);
    }

    if (filtrar) {
      const filtro = todos('.q-popover input, .q-menu input')[0] || select.querySelector('input');
      if (filtro) {
        await digitar(filtro, texto, { sair: false });
      }
    }

    const opcao = await esperar(
      () => todos(SEL_OPCOES).find((item) => L.normalizar(item.innerText) === alvo),
      { descricao: descricao || `a opção "${texto}"` },
    );

    clicar(opcao);
    await esperar(() => L.normalizar(select.innerText).includes(alvo), { timeout: 5000, descricao: `"${texto}" selecionado` });
  }

  async function avancar(proximaEtapa) {
    const proximo = await esperar(
      () => todos('.stepper-button.next').find((b) => !desabilitado(b)) || botao('PROXIMO'),
      { descricao: 'o botão Próximo liberado' },
    );
    clicar(proximo);
    await esperarEtapa(proximaEtapa);
  }

  const esperarEtapa = (n) => esperar(() => L.etapaDoTexto(textoPagina()) === n, { timeout: 20000, descricao: `a etapa ${n}` });

  /** Janela de aviso do SISAP ("Atenção!") cujo texto contém o trecho informado (normalizado). */
  function avisoDoSisap(trecho) {
    return todos('.modal-content, .modal, .q-dialog').find((m) => L.normalizar(m.innerText).includes(trecho)) || null;
  }

  /** Clica em OK na janela de aviso do SISAP com o trecho, se estiver aberta. */
  function fecharAviso(trecho) {
    const aviso = avisoDoSisap(trecho);
    const ok = aviso && todos('button', aviso).find((b) => L.normalizar(b.innerText) === 'OK');
    if (ok) {
      clicar(ok);
      return true;
    }
    return false;
  }

  const fecharAvisoDeErro = () => fecharAviso('ERRO NA COMUNICACAO');

  /**
   * Digita num campo que dispara consulta ao SISAP e espera a resposta.
   * Se o Cloudflare barrar (403), fecha o aviso, pede para renovar a verificação e tenta de novo.
   */
  async function digitarEConsultar(input, valor, endpoint, descricao) {
    for (let tentativa = 1; tentativa <= 3; tentativa++) {
      const antes = Date.now();
      await digitar(input, valor);
      const captura = await esperarResposta(endpoint, antes);

      if (captura.status === 200 && captura.json) {
        return L.primeiro(captura.json);
      }

      await dormir(600);
      fecharAvisoDeErro();

      if (tentativa === 3) {
        break;
      }

      await pausar(`O Cloudflare do SISAP bloqueou a consulta de ${descricao} (erro ${captura.status}). `
        + 'Abra o SISAP numa aba nova, espere carregar (resolva a verificação se aparecer), volte aqui e clique em Continuar para tentar de novo.');

      if (parar) {
        throw new Parado();
      }
    }

    throw new Error(`o SISAP continuou bloqueando a consulta de ${descricao}`);
  }

  // ------------------------------------------------------------------
  // Fluxo
  // ------------------------------------------------------------------

  /** Pausa até o usuário clicar em Continuar. Por padrão avisa (notificação), porque precisa de alguém. */
  function pausar(mensagem, avisarUsuario = true) {
    registrar(mensagem, 'pausa');
    if (avisarUsuario) {
      avisar('A extensão precisa de você', mensagem);
    }
    return new Promise((resolve) => {
      continuar = resolve;
      render();
    });
  }

  async function passo(nome, fn) {
    if (passoAPasso) {
      await pausar(`Próximo: ${nome}. Clique em Continuar.`, false);
    }
    if (parar) {
      throw new Parado();
    }

    try {
      registrar(`${nome}…`, 'info');
      await fn();
    } catch (erro) {
      if (erro instanceof Parado || erro instanceof ErroSisap) {
        throw erro;
      }
      await pausar(`Não consegui fazer "${nome}" sozinho (${erro.message}). Faça esse passo na tela do SISAP e clique em Continuar.`);
      if (parar) {
        throw new Parado();
      }
    }
  }

  async function agendar(agendamento) {
    if (executando) {
      return;
    }

    executando = true;
    parar = false;
    logs.length = 0;
    job = { id: agendamento.id, horarios: [], slot: null, resultado: null };
    await salvarJob();
    render();

    try {
      conferirProcurador();

      await passo('abrir o agendamento para representante legal', () => abrirFluxo(agendamento));
      await passo('etapa 1 – orientações', etapa1);
      await passo('etapa 2 – identificação', etapa2);
      await passo('etapa 3 – clientes e GRUs', () => etapa3(agendamento));
      await passo('etapa 4 – data e horário', () => etapa4(agendamento));
      await passo('etapa 5 – confirmação', etapa5);

      registrar('Digite o CAPTCHA e clique em FINALIZAR. O resultado vai para o PROA automaticamente.', 'pausa');
    } catch (erro) {
      if (erro instanceof Parado) {
        registrar('Automação parada.', 'info');
      } else {
        registrar(erro.message, 'erro');
        if (erro instanceof ErroSisap) {
          await proa('falha', { id: agendamento.id, corpo: { mensagem: erro.message } }).catch(() => {});
        }
      }
    } finally {
      executando = false;
      continuar = null;
      render();
    }
  }

  function conferirProcurador() {
    const cpfLogado = L.cpfDoCabecalho(textoPagina());

    if (!cpfLogado) {
      throw new Error('Faça login no SISAP pelo gov.br antes de agendar.');
    }

    const procurador = dadosProa.procurador;
    if (cpfLogado !== L.somenteDigitos(procurador.cpf)) {
      throw new Error(`Este Chrome está logado no SISAP com o CPF ${L.formatarCpf(cpfLogado)}, `
        + `mas o token da extensão é de ${procurador.nome} (${L.formatarCpf(procurador.cpf)}).`);
    }
  }

  function nomeDaOm(capitania) {
    const oms = capturas.mainpage ? L.encontrarOms(capturas.mainpage.json) : [];
    const porCodigo = capitania.nidom != null ? oms.find((om) => om.nidom === String(capitania.nidom)) : null;
    return porCodigo ? porCodigo.nome : capitania.nome;
  }

  const TELA_INICIAL = '#/homemenu';

  /**
   * Sempre recomeça pela tela inicial do SISAP (#/homemenu), qualquer que seja a tela atual,
   * e segue: Abrir o agendamento eletrônico → capitania do PROA → Agendamento para representante legal.
   */
  async function abrirFluxo(agendamento) {
    if (location.hash !== TELA_INICIAL) {
      location.hash = TELA_INICIAL;
    }

    const entrar = await esperar(() => location.hash === TELA_INICIAL && botao('ABRIR O AGENDAMENTO ELETRONICO'), {
      descricao: 'o botão "Abrir o agendamento eletrônico e geração de GRU" na tela inicial',
    });

    const antesDaLista = Date.now();
    clicar(entrar);

    const seletorOm = await esperar(
      () => todos('.q-select').find((s) => /ORGANIZACAO MILITAR|CAPITANIA|DELEGACIA|AGENCIA|CENTRO|COMANDO/.test(L.normalizar(s.innerText))),
      { descricao: 'o seletor de Organização Militar' },
    );

    // A lista de OMs (com os códigos) chega junto com a tela; sem ela, usa o nome cadastrado no PROA.
    await esperarResposta('mainpage', antesDaLista, 8000).catch(() => null);
    const nomeOm = nomeDaOm(agendamento.capitania);

    await escolherNoSelect(seletorOm, nomeOm, { filtrar: true, descricao: `a OM "${nomeOm}"` });

    if (!botao('CLIQUE AQUI PARA AGENDAR')) {
      const secao = await esperar(() => porTexto('.q-collapsible .q-item, .q-expansion-item .q-item, .q-item', 'AGENDAMENTO PARA REPRESENTANTE LEGAL')[0], {
        descricao: 'a seção "Agendamento para representante legal"',
      });
      clicar(secao);
    }

    await entrarNoAgendamento(nomeOm);
  }

  // Intervalo entre tentativas quando a OM está sem horários: variável, para não bater em ritmo fixo.
  const INTERVALO_SEM_HORARIO = [8000, 12000];
  // Após "Erro na comunicação" (Cloudflare/sessão), espera cada vez mais antes de tentar de novo.
  const ESPERAS_APOS_ERRO = [30000, 60000, 120000];

  /**
   * Clica em "Clique aqui para agendar" até o SISAP liberar a etapa 1.
   * Com a opção "Repetir sozinho" ligada, "Não temos horários disponível para esta OM" vira nova tentativa.
   */
  async function entrarNoAgendamento(nomeOm) {
    const inicio = Date.now();
    let tentativa = 0;
    let errosSeguidos = 0;

    for (;;) {
      tentativa++;
      clicar(await esperar(() => botao('CLIQUE AQUI PARA AGENDAR'), { descricao: 'o botão "Clique aqui para agendar"' }));

      const resposta = await esperar(() => {
        if (L.etapaDoTexto(textoPagina()) === 1) {
          return 'liberado';
        }
        if (avisoDoSisap('NAO TEMOS HORARIOS')) {
          return 'sem-horario';
        }
        if (avisoDoSisap('ERRO NA COMUNICACAO')) {
          return 'erro';
        }
        return null;
      }, { timeout: 30000, descricao: 'a resposta do SISAP ao botão "Clique aqui para agendar"' });

      if (resposta === 'liberado') {
        if (tentativa > 1) {
          registrar(`Horários liberados na tentativa ${tentativa}, após ${tempoDesde(inicio)}.`, 'ok');
          avisar('Horários liberados no SISAP', `${nomeOm}: a extensão entrou no agendamento após ${tentativa} tentativas.`);
        }
        return;
      }

      fecharAviso(resposta === 'erro' ? 'ERRO NA COMUNICACAO' : 'NAO TEMOS HORARIOS');

      if (resposta === 'sem-horario' && !repetirSemHorario) {
        throw new Error('a OM está sem horários neste momento (marque "Repetir sozinho" no painel para a extensão continuar tentando)');
      }

      errosSeguidos = resposta === 'erro' ? errosSeguidos + 1 : 0;

      if (errosSeguidos > ESPERAS_APOS_ERRO.length) {
        await pausar('O SISAP continua respondendo "Erro na comunicação" (bloqueio do Cloudflare ou login expirado). '
          + 'Abra o SISAP numa aba nova, confira o login e clique em Continuar para voltar a tentar.');
        if (parar) {
          throw new Parado();
        }
        errosSeguidos = 0;
        continue;
      }

      const espera = resposta === 'erro'
        ? ESPERAS_APOS_ERRO[errosSeguidos - 1]
        : INTERVALO_SEM_HORARIO[0] + Math.random() * (INTERVALO_SEM_HORARIO[1] - INTERVALO_SEM_HORARIO[0]);
      const motivo = resposta === 'erro' ? 'Erro na comunicação com o SISAP' : `Sem horários em ${nomeOm}`;

      await contagemRegressiva(espera, (segundos) => `${motivo} — tentativa ${tentativa}, tentando há ${tempoDesde(inicio)}. `
        + `Nova tentativa em ${segundos}s. Deixe esta aba visível (sem minimizar) para as tentativas não ficarem lentas.`);
    }
  }

  async function contagemRegressiva(ms, texto) {
    const fim = Date.now() + ms;

    while (Date.now() < fim) {
      if (parar) {
        throw new Parado();
      }
      registrar(texto(Math.ceil((fim - Date.now()) / 1000)), 'espera');
      await dormir(Math.min(1000, fim - Date.now()));
    }
  }

  function tempoDesde(inicio) {
    const segundos = Math.round((Date.now() - inicio) / 1000);
    return segundos < 60 ? `${segundos}s` : `${Math.floor(segundos / 60)} min ${String(segundos % 60).padStart(2, '0')}s`;
  }

  /** Notificação do Windows + traz a aba do SISAP para frente. */
  function avisar(titulo, mensagem) {
    chrome.runtime.sendMessage({ tipo: 'avisar', titulo, mensagem }).catch(() => {});
  }

  async function etapa1() {
    await esperarEtapa(1);
    await marcar('LI E CONCORDO');
    await avancar(2);
  }

  async function etapa2() {
    await esperarEtapa(2);
    await avancar(3);
  }

  /** "Tipo doc" de uma linha de interessado ainda sem escolha ("SELECIONE"). Cria a linha se não houver. */
  async function tipoDocLivre() {
    const achar = () => camposPorRotulo('TIPO DOC').find((t) => L.normalizar(t.innerText).includes('SELECIONE'));

    if (!achar()) {
      clicar(await esperar(() => botao('ADICIONAR INTERESSADO'), { descricao: 'o botão "Adicionar interessado"' }));
    }

    return esperar(achar, { descricao: 'uma linha de interessado em branco' });
  }

  async function etapa3(agendamento) {
    await esperarEtapa(3);

    for (const pessoa of agendamento.interessados) {
      const cpfFormatado = L.formatarCpf(pessoa.cpf);

      const tipoDoc = await tipoDocLivre();
      await escolherNoSelect(tipoDoc, 'CPF', { descricao: 'a opção CPF em "Tipo doc"' });

      const campoCpf = await esperar(() => inputsPorRotulo('DO CPF').find((input) => !input.value && !input.disabled), {
        descricao: 'o campo "Nº do CPF"',
      });

      const consulta = await digitarEConsultar(campoCpf, pessoa.cpf, 'buscarcpfcnpj', `CPF ${cpfFormatado}`);

      if (!consulta || consulta.lvalido === false) {
        throw new ErroSisap(`O SISAP considerou inválido o CPF ${cpfFormatado}.`);
      }

      if (consulta.lencontrado === false) {
        await pausar(`O CPF ${cpfFormatado} não tem cadastro na Marinha. Preencha os dados obrigatórios dele no SISAP e clique em Continuar.`);
      }

      const bloco = await esperar(
        () => subirAte(campoCpf, (el) => inputsPorRotulo('DO CPF', el).length === 1 && botao('ADICIONAR SERVICO', el)),
        { descricao: `a área de serviços do CPF ${cpfFormatado}` },
      );

      for (const servico of pessoa.servicos) {
        clicar(await esperar(() => botao('ADICIONAR SERVICO', bloco), { descricao: 'o botão "Adicionar serviço"' }));

        const campoGru = await esperar(() => inputsPorRotulo('GRU', bloco).find((input) => !input.value && !input.disabled), {
          descricao: 'o campo da GRU',
        });

        const validacao = await digitarEConsultar(campoGru, servico.gru, 'validargruinformada', `GRU ${servico.gru}`);

        if (!validacao || validacao.lretorno !== true) {
          throw new ErroSisap(`GRU ${servico.gru} (CPF ${cpfFormatado}): ${(validacao && validacao.cmensagem) || 'recusada pelo SISAP'}.`);
        }

        await escolherServico(campoGru, servico.descricao_sisap);
      }
    }

    const esperado = agendamento.interessados.reduce((total, p) => total + p.servicos.length, 0);
    const contador = L.contadorServicos(textoPagina());
    if (contador && contador.usados !== esperado) {
      throw new Error(`o SISAP mostra ${contador.usados} serviço(s), mas o PROA tem ${esperado}`);
    }

    desdeAgenda = Date.now();
    await avancar(4);
  }

  async function escolherServico(campoGru, descricao) {
    const ehListaDeServicos = (s) => !/TIPO DOC|SEXO|ORGANIZACAO MILITAR/.test(L.normalizar(s.innerText));

    const linha = await esperar(
      () => subirAte(campoGru, (el) => todos('.q-select', el).some(ehListaDeServicos)),
      { descricao: 'a lista de serviços ao lado da GRU' },
    );

    const lista = todos('.q-select', linha).filter(ehListaDeServicos).pop();

    await escolherNoSelect(lista, descricao, {
      descricao: `o serviço "${descricao}" na lista (confira o texto em Serviços do SISAP no PROA)`,
    });
  }

  async function etapa4(agendamento) {
    await esperarEtapa(4);

    const captura = await esperarResposta('agendadespachante', desdeAgenda);
    if (captura.status !== 200) {
      fecharAvisoDeErro();
      throw new Error(`o Cloudflare bloqueou a agenda (erro ${captura.status}); clique em Voltar e Próximo no SISAP para recarregar os horários`);
    }

    const agenda = L.extrairHorarios(captura.json);
    if (!agenda.ok) {
      throw new ErroSisap(agenda.mensagem);
    }
    if (agenda.horarios.length === 0) {
      throw new ErroSisap('O SISAP não mostrou nenhum horário livre.');
    }

    job.horarios = agenda.horarios;
    await salvarJob();

    const escolha = await proa('horario', {
      id: agendamento.id,
      corpo: { horarios: agenda.horarios.map(({ data, hora, turno }) => ({ data, hora, turno })) },
    });

    if (!escolha || !escolha.horario) {
      throw new Error('o PROA não escolheu um horário');
    }

    job.slot = { data: escolha.horario.data, hora: escolha.horario.hora };
    await salvarJob();

    const quando = (h) => `${L.formatarData(h.data)} às ${h.hora}`;
    let detalhe = '';
    if (escolha.referencia) {
      detalhe = ` (junto do outro agendamento, marcado para ${escolha.referencia})`;
    } else if (escolha.plano) {
      detalhe = ` (plano para os dois agendamentos: ${quando(escolha.plano[0])} e ${quando(escolha.plano[1])})`;
    }
    registrar(`Horário escolhido pelo PROA: ${quando(job.slot)}${detalhe}.`, 'info');

    await selecionarData(job.slot.data);
    await selecionarHora(job.slot.hora);
    await avancar(5);
  }

  /** Elementos sem filhos cujo texto é exatamente o informado. */
  const folhasComTexto = (texto, raiz = document) => todos('*', raiz).filter((el) => el.children.length === 0 && el.innerText.trim() === texto);


  async function selecionarData(data) {
    const [ano, mes, dia] = data.split('-').map(Number);
    const alvo = ano * 12 + mes;
    const rotuloMes = () => todos('*').find((el) => el.children.length === 0 && /^\d{2}\/\d{4}$/.test(el.innerText.trim()));

    let rotulo;
    for (let tentativa = 0; tentativa < 13; tentativa++) {
      rotulo = await esperar(rotuloMes, { descricao: 'o mês do calendário' });
      const [m, a] = rotulo.innerText.trim().split('/').map(Number);
      const atual = a * 12 + m;

      if (atual === alvo) {
        break;
      }

      // As setas ficam no mesmo cabeçalho do rótulo "MM/AAAA": a primeira volta, a última avança.
      const cabecalho = subirAte(rotulo, (el) => todos('button, .q-btn, i', el).length >= 2);
      const setas = cabecalho ? todos('button, .q-btn', cabecalho) : [];
      const seta = atual < alvo ? setas[setas.length - 1] : setas[0];
      if (!seta) {
        throw new Error('não achei as setas do calendário');
      }
      clicar(seta);
      await dormir(400);
    }

    const calendario = subirAte(rotulo, (el) => folhasComTexto('28', el).length > 0);
    const numeros = calendario ? folhasComTexto(String(dia), calendario) : [];

    if (numeros.length === 0) {
      throw new Error(`não achei o dia ${dia} no calendário`);
    }

    // O título ao lado do calendário ("SEGUNDA-FEIRA, 21 DE SETEMBRO DE 2026") muda quando o dia é aceito.
    const diaAceito = () => new RegExp(`, ${dia} DE `).test(L.normalizar(textoPagina()));

    // Clica no próprio número (o evento sobe pela célula do dia até quem trata o clique). Se o calendário
    // não reagir, tenta os elementos logo acima, um nível por vez, sem sair do calendário.
    for (const numero of numeros) {
      for (let alvo = numero, nivel = 0; alvo && alvo !== calendario && nivel < 4; alvo = alvo.parentElement, nivel++) {
        alvo.scrollIntoView({ block: 'center' });
        alvo.click();

        const aceito = await esperar(diaAceito, { timeout: 1200, descricao: 'o dia selecionado' }).catch(() => false);
        if (aceito) {
          return;
        }

        clicar(alvo);
        if (await esperar(diaAceito, { timeout: 1200, descricao: 'o dia selecionado' }).catch(() => false)) {
          return;
        }
      }
    }

    throw new Error(`cliquei no dia ${dia}, mas o calendário não mostrou os horários desse dia`);
  }

  async function selecionarHora(hora) {
    const opcao = await esperar(
      () => todos('.q-radio').find((r) => L.normalizar(r.innerText).endsWith(hora)),
      { descricao: `o horário ${hora} (pode ter sido ocupado — escolha o mais próximo)` },
    );

    clicar(opcao);
    await esperar(() => marcado(opcao), { timeout: 3000, descricao: 'o horário marcado' });
  }

  async function etapa5() {
    await esperarEtapa(5);
    await marcar('ESTOU CIENTE');

    const captcha = await esperar(
      () => todos('input').find((input) => input.maxLength === 6),
      { descricao: 'o campo do CAPTCHA' },
    );

    captcha.scrollIntoView({ block: 'center' });
    captcha.focus();
    captcha.style.outline = '3px solid #f59e0b';

    avisar('Digite o CAPTCHA no SISAP', 'O agendamento chegou à confirmação. Digite o CAPTCHA e clique em Finalizar.');
  }

  async function aoCapturar(captura) {
    if (!job) {
      return;
    }

    if (captura.endpoint === 'enviaagendamentodespachante') {
      const resultado = L.resultadoDoEnvio(captura.json);

      if (!resultado.ok) {
        registrar(`O SISAP recusou: ${resultado.mensagem}`, 'erro');
        await proa('falha', { id: job.id, corpo: { mensagem: resultado.mensagem } });
        return;
      }

      const horario = L.horarioEnviado(captura.corpo, job.horarios) || job.slot;
      if (!horario) {
        registrar(`Agendado (nº ${resultado.numero}, chave ${resultado.chave}), mas não identifiquei a data. Registre no PROA à mão.`, 'erro');
        return;
      }

      job.resultado = {
        numero: resultado.numero,
        chave: resultado.chave,
        sisap_id: resultado.sisapId,
        data_hora: `${horario.data} ${horario.hora}`,
      };
      await salvarJob();

      // Registra já: a janela de impressão que vem depois trava a página.
      await proa('resultado', { id: job.id, corpo: job.resultado });
      registrar(`Agendado para ${L.formatarData(horario.data)} às ${horario.hora} — nº ${resultado.numero}, chave ${resultado.chave}. Registrado no PROA.`, 'ok');
      atualizarLista();
    }

    if (captura.endpoint === 'imprimiragendamento' && job.resultado) {
      const link = L.primeiro(captura.json)?.link;
      if (link) {
        await proa('resultado', {
          id: job.id,
          corpo: { ...job.resultado, comprovante_link: new URL(link, location.href).href },
        });
      }

      job = null;
      await salvarJob();
      render();
    }
  }

  // ------------------------------------------------------------------
  // Painel
  // ------------------------------------------------------------------

  let raiz = null;

  function registrar(mensagem, tipo = 'info') {
    // A contagem das tentativas atualiza a mesma linha em vez de encher o registro.
    if (tipo === 'espera' && logs.length && logs[logs.length - 1].tipo === 'espera') {
      logs[logs.length - 1].mensagem = mensagem;
      render();
      return;
    }

    logs.push({ mensagem, tipo });
    if (logs.length > 8) {
      logs.shift();
    }
    render();
  }

  async function atualizarLista() {
    detectarLogin();

    if (!cpfLogado) {
      dadosProa = null;
      erroProa = null;
      render();
      return;
    }

    const cpfDaConsulta = cpfLogado;

    try {
      const dados = await proa('listar');
      // Se trocaram de conta enquanto a consulta ia e voltava, descarta a resposta antiga.
      if (cpfDaConsulta === cpfLogado) {
        dadosProa = dados;
        erroProa = null;
      }
    } catch (erro) {
      if (cpfDaConsulta === cpfLogado) {
        dadosProa = null;
        erroProa = erro.message;
      }
    }
    render();
  }

  /** Lê quem está logado no SISAP: resposta do auth.php e, na falta dela, o cabeçalho da página. */
  function detectarLogin() {
    const texto = textoPagina();
    cpfLogado = (capturas.auth && L.cpfDoLogin(capturas.auth.json)) || L.cpfDoCabecalho(texto) || null;
    nomeLogado = cpfLogado ? L.nomeDoCabecalho(texto) : null;
  }

  /** Ao sair de uma conta gov.br e entrar com outra, recarrega os agendamentos do novo procurador. */
  function vigiarTrocaDeLogin() {
    setInterval(() => {
      const anterior = cpfLogado;
      detectarLogin();

      if (cpfLogado === anterior) {
        return;
      }

      if (executando) {
        registrar('O login do SISAP mudou durante o agendamento. Clique em Parar e comece de novo com o procurador certo.', 'erro');
        return;
      }

      logs.length = 0;
      dadosProa = null;
      erroProa = null;
      if (cpfLogado) {
        registrar(`SISAP logado como ${nomeLogado || L.formatarCpf(cpfLogado)}. Carregando os agendamentos desse procurador…`, 'info');
      }
      atualizarLista();
    }, 2000);
  }

  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  function textoPreferencia(preferencia) {
    if (!preferencia) {
      return '';
    }
    const partes = [
      preferencia.data ? L.formatarData(preferencia.data) : null,
      { manha: 'Matutino', tarde: 'Vespertino' }[preferencia.periodo] || null,
    ].filter(Boolean);
    return partes.length ? `Preferência: ${partes.join(' · ')}` : '';
  }

  function htmlAgendamento(ag) {
    const [ano, mes] = ag.competencia.split('-');
    const servicos = ag.interessados.reduce((t, p) => t + p.servicos.length, 0);

    const linhas = ag.interessados.map((p) => p.servicos.map((s, i) => `
      <tr>
        <td>${i === 0 ? esc(p.nome || '—') : ''}</td>
        <td>${i === 0 ? `<button class="copiar" data-acao="copiar" data-valor="${esc(p.cpf)}" title="Copiar CPF">${esc(L.formatarCpf(p.cpf))}</button>` : ''}</td>
        <td><button class="copiar" data-acao="copiar" data-valor="${esc(s.gru)}" title="Copiar GRU">${esc(s.gru)}</button></td>
        <td title="${esc(s.descricao_sisap)}">${esc(s.sigla)}</td>
      </tr>`).join('')).join('');

    return `
      <div class="ag">
        <div class="ag-topo">
          <strong>Nº ${ag.ordem} · ${esc(ag.capitania.sigla)} · ${mes}/${ano}</strong>
          <span class="selo ${ag.status === 'falhou' ? 'selo-erro' : ''}">${ag.status === 'falhou' ? 'Falhou' : 'Aguardando'}</span>
        </div>
        <div class="sub">${esc(ag.capitania.nome)} · ${servicos} serviço(s)${ag.referencia_horario ? ` · tentar perto de ${esc(ag.referencia_horario)}` : ''}</div>
        ${textoPreferencia(ag.preferencia) ? `<div class="sub">${esc(textoPreferencia(ag.preferencia))}</div>` : ''}
        ${ag.status === 'falhou' && ag.erro ? `<div class="sub erro">${esc(ag.erro)}</div>` : ''}
        <table>${linhas}</table>
        <button class="principal" data-acao="agendar" data-id="${ag.id}" ${executando ? 'disabled' : ''}>Agendar no SISAP</button>
      </div>`;
  }

  function render() {
    if (!raiz) {
      return;
    }

    let corpo;

    const linhaLogin = cpfLogado
      ? `<p class="sub">SISAP logado como: <strong>${esc(nomeLogado || '—')}</strong> (${esc(L.formatarCpf(cpfLogado))})</p>`
      : '';

    if (!cpfLogado) {
      corpo = '<p class="sub">Faça login no SISAP pelo gov.br. A extensão carrega os agendamentos do procurador que estiver logado.</p>';
    } else if (erroProa) {
      corpo = `${linhaLogin}<p class="erro">${esc(erroProa)}</p><button data-acao="opcoes">Abrir configurações</button>`;
    } else if (!dadosProa) {
      corpo = `${linhaLogin}<p class="sub">Carregando do PROA…</p>`;
    } else {
      const lista = dadosProa.agendamentos.length
        ? dadosProa.agendamentos.map(htmlAgendamento).join('')
        : '<p class="sub">Nenhum agendamento pendente para este procurador.</p>';

      corpo = `
        ${linhaLogin}
        <p class="sub">Procurador no PROA: <strong>${esc(dadosProa.procurador.nome)}</strong></p>
        <label class="opcao"><input type="checkbox" data-acao="passo" ${passoAPasso ? 'checked' : ''}> Pausar antes de cada etapa</label>
        <label class="opcao"><input type="checkbox" data-acao="repetir" ${repetirSemHorario ? 'checked' : ''}> Repetir sozinho quando a OM estiver sem horários</label>
        ${lista}`;
    }

    const registro = logs.map((l) => `<div class="log log-${l.tipo}">${esc(l.mensagem)}</div>`).join('');

    const extensao = dadosProa && dadosProa.extensao;
    const faixaVersao = extensao && extensao.desatualizada
      ? `<div class="faixa">Extensão desatualizada: esta é a versão ${esc(extensao.versao_usada)}, a atual é ${esc(extensao.versao_atual)}. `
        + 'Baixe a nova no PROA (Agendamentos Marinha → Baixar extensão), extraia por cima da mesma pasta e clique em Recarregar em chrome://extensions.</div>'
      : '';

    raiz.innerHTML = `
      <style>${CSS}</style>
      <div class="painel ${minimizado ? 'min' : ''}">
        <header>
          <span>PROA · Agendamento <small class="versao">v${esc(chrome.runtime.getManifest().version)}</small></span>
          <span>
            <button class="icone" data-acao="atualizar" title="Atualizar">⟳</button>
            <button class="icone" data-acao="minimizar" title="Minimizar">${minimizado ? '▢' : '–'}</button>
          </span>
        </header>
        <div class="corpo">
          ${faixaVersao}
          ${corpo}
          ${registro ? `<div class="registro">${registro}</div>` : ''}
          <div class="acoes">
            ${continuar ? '<button class="principal" data-acao="continuar">Continuar</button>' : ''}
            ${executando ? '<button data-acao="parar">Parar</button>' : ''}
          </div>
        </div>
      </div>`;
  }

  async function aoClicar(evento) {
    const alvo = evento.target.closest('[data-acao]');
    if (!alvo) {
      return;
    }

    const acao = alvo.dataset.acao;

    if (acao === 'copiar') {
      await navigator.clipboard.writeText(alvo.dataset.valor);
      const original = alvo.textContent;
      alvo.textContent = 'Copiado!';
      setTimeout(() => { alvo.textContent = original; }, 900);
    } else if (acao === 'agendar') {
      const agendamento = dadosProa.agendamentos.find((a) => String(a.id) === alvo.dataset.id);
      if (agendamento) {
        agendar(agendamento);
      }
    } else if (acao === 'continuar' && continuar) {
      const resolver = continuar;
      continuar = null;
      render();
      resolver();
    } else if (acao === 'parar') {
      parar = true;
      if (continuar) {
        const resolver = continuar;
        continuar = null;
        resolver();
      }
    } else if (acao === 'passo') {
      passoAPasso = alvo.checked;
      chrome.storage.local.set({ proaPassoAPasso: passoAPasso });
    } else if (acao === 'repetir') {
      repetirSemHorario = alvo.checked;
      chrome.storage.local.set({ proaRepetir: repetirSemHorario });
    } else if (acao === 'minimizar') {
      minimizado = !minimizado;
      render();
    } else if (acao === 'atualizar') {
      atualizarLista();
    } else if (acao === 'opcoes') {
      chrome.runtime.sendMessage({ tipo: 'abrirOpcoes' });
    }
  }

  const CSS = `
    :host { all: initial; }
    .painel { position: fixed; right: 16px; bottom: 16px; z-index: 2147483647; width: 380px; max-height: 75vh;
      display: flex; flex-direction: column; background: #fff; color: #111827; border: 1px solid #d1d5db;
      border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,.2); font: 13px/1.4 system-ui, -apple-system, Segoe UI, sans-serif; }
    .painel.min { width: auto; }
    .painel.min .corpo { display: none; }
    header { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 8px 10px;
      background: #1e3a8a; color: #fff; border-radius: 10px 10px 0 0; font-weight: 600; }
    .corpo { padding: 10px; overflow: auto; }
    .sub { color: #4b5563; margin: 2px 0; }
    .erro { color: #b91c1c; }
    .opcao { display: flex; gap: 6px; align-items: center; margin: 6px 0 8px; color: #374151; }
    .ag { border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px; margin-bottom: 8px; }
    .ag-topo { display: flex; justify-content: space-between; align-items: center; gap: 6px; }
    .selo { background: #dbeafe; color: #1e40af; border-radius: 999px; padding: 1px 8px; font-size: 11px; }
    .selo-erro { background: #fee2e2; color: #991b1b; }
    table { width: 100%; border-collapse: collapse; margin: 6px 0; font-size: 12px; }
    td { padding: 2px 3px; border-top: 1px solid #f3f4f6; vertical-align: middle; }
    button { font: inherit; cursor: pointer; border: 1px solid #d1d5db; background: #f9fafb; border-radius: 6px; padding: 4px 10px; }
    button:disabled { opacity: .5; cursor: default; }
    button.principal { background: #1d4ed8; border-color: #1d4ed8; color: #fff; width: 100%; padding: 6px 10px; }
    button.icone { background: transparent; border: none; color: #fff; padding: 0 4px; font-size: 15px; }
    button.copiar { font-family: ui-monospace, Consolas, monospace; font-size: 11px; padding: 1px 4px; background: #fff; }
    .registro { margin-top: 8px; border-top: 1px solid #e5e7eb; padding-top: 6px; }
    .log { padding: 3px 0; }
    .log-erro { color: #b91c1c; }
    .log-ok { color: #047857; font-weight: 600; }
    .log-pausa { color: #92400e; font-weight: 600; }
    .log-espera { color: #1d4ed8; }
    .versao { font-weight: 400; opacity: .75; margin-left: 4px; }
    .faixa { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; border-radius: 6px; padding: 6px 8px; margin-bottom: 8px; }
    .acoes { display: flex; gap: 6px; margin-top: 6px; }
    .acoes button { flex: 1; }
  `;

  function montar() {
    const hospedeiro = document.createElement('div');
    hospedeiro.id = 'proa-agendamento-painel';
    document.documentElement.appendChild(hospedeiro);

    raiz = hospedeiro.attachShadow({ mode: 'open' });
    raiz.addEventListener('click', aoClicar);
    raiz.addEventListener('change', aoClicar);

    chrome.storage.local.get(['proaJob', 'proaPassoAPasso', 'proaRepetir']).then((salvo) => {
      job = salvo.proaJob || null;
      passoAPasso = salvo.proaPassoAPasso !== false;
      repetirSemHorario = salvo.proaRepetir !== false;
      render();
    });

    render();
    // Dá um instante para o SISAP responder o auth.php antes da primeira leitura do login.
    setTimeout(atualizarLista, 1500);
    vigiarTrocaDeLogin();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', montar, { once: true });
  } else {
    montar();
  }
})();
