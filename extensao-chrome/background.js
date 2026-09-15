/*
 * Service worker: única parte da extensão que conversa com o PROA.
 * O token fica no chrome.storage.local deste perfil do Chrome.
 */

const ROTAS = {
  eu: (d) => ['GET', '/eu'],
  listar: (d) => ['GET', '/agendamentos'],
  horario: (d) => ['POST', `/agendamentos/${d.id}/escolher-horario`],
  resultado: (d) => ['POST', `/agendamentos/${d.id}/resultado`],
  falha: (d) => ['POST', `/agendamentos/${d.id}/falha`],
};

async function chamarProa(acao, dados) {
  const { proaUrl, proaToken } = await chrome.storage.local.get(['proaUrl', 'proaToken']);

  if (!proaUrl || !proaToken) {
    throw new Error('Configure o endereço do PROA e o token nas opções da extensão.');
  }

  const rota = ROTAS[acao];
  if (!rota) {
    throw new Error(`Ação desconhecida: ${acao}`);
  }

  const [metodo, caminho] = rota(dados);
  let resposta;

  try {
    resposta = await fetch(`${proaUrl.replace(/\/+$/, '')}/api/sisap${caminho}`, {
      method: metodo,
      headers: {
        Authorization: `Bearer ${proaToken}`,
        Accept: 'application/json',
        // O PROA compara com a versão publicada e avisa quem está desatualizado.
        'X-Extensao-Versao': chrome.runtime.getManifest().version,
        // CPF logado no SISAP: com token de usuário, é ele que define o procurador.
        ...(dados.cpf ? { 'X-Procurador-Cpf': String(dados.cpf) } : {}),
        ...(metodo === 'POST' ? { 'Content-Type': 'application/json' } : {}),
      },
      body: metodo === 'POST' ? JSON.stringify(dados.corpo || {}) : undefined,
    });
  } catch (erro) {
    throw new Error('Não consegui acessar o PROA. Confira o endereço e salve as opções de novo para liberar o acesso.');
  }

  const texto = await resposta.text();
  let json = null;
  try {
    json = JSON.parse(texto);
  } catch (e) {
    // resposta não-JSON (ex.: página de erro)
  }

  if (resposta.status === 401) {
    throw new Error('Token inválido, revogado ou sem permissão de agendamento. Gere um novo no PROA (Agendamentos Marinha → Token da extensão).');
  }

  if (!resposta.ok) {
    throw new Error((json && json.message) || `O PROA respondeu com erro ${resposta.status}.`);
  }

  return json;
}

chrome.runtime.onMessage.addListener((mensagem, remetente, responder) => {
  if (remetente.id !== chrome.runtime.id || !mensagem) {
    return;
  }

  if (mensagem.tipo === 'abrirOpcoes') {
    chrome.runtime.openOptionsPage();
    return;
  }

  // Chama o usuário: notificação do Windows e a aba do SISAP à frente.
  if (mensagem.tipo === 'avisar') {
    chrome.notifications.create({
      type: 'basic',
      iconUrl: 'icons/icone128.png',
      title: String(mensagem.titulo || 'PROA – Agendamento Marinha'),
      message: String(mensagem.mensagem || ''),
      priority: 2,
      requireInteraction: true,
    });

    if (remetente.tab) {
      chrome.tabs.update(remetente.tab.id, { active: true });
      chrome.windows.update(remetente.tab.windowId, { focused: true, drawAttention: true });
    }
    return;
  }

  if (mensagem.tipo !== 'proa') {
    return;
  }

  chamarProa(mensagem.acao, mensagem.dados || {}).then(
    (dados) => responder({ ok: true, dados }),
    (erro) => responder({ ok: false, erro: erro.message }),
  );

  return true; // resposta assíncrona
});

chrome.action.onClicked.addListener(() => chrome.runtime.openOptionsPage());
