const campo = (id) => document.getElementById(id);

/**
 * Mantém o caminho (o PROA pode estar num subcaminho do nginx, ex.: https://servidor/proa),
 * mas tira barra final, parâmetros e um /admin colado por engano.
 */
function normalizarEndereco(texto) {
  const url = new URL(texto.trim());
  const caminho = url.pathname.replace(/\/admin(\/.*)?$/i, '').replace(/\/+$/, '');
  return `${url.origin}${caminho}`;
}

function mostrar(texto, classe = '') {
  const mensagem = campo('mensagem');
  mensagem.textContent = texto;
  mensagem.className = classe;
}

chrome.storage.local.get(['proaUrl', 'proaToken']).then((salvo) => {
  campo('url').value = salvo.proaUrl || 'http://localhost:8000';
  campo('token').value = salvo.proaToken || '';
});

campo('formulario').addEventListener('submit', (evento) => {
  evento.preventDefault();

  let endereco;
  try {
    endereco = normalizarEndereco(campo('url').value);
  } catch (e) {
    mostrar('Endereço inválido.', 'erro');
    return;
  }
  campo('url').value = endereco;

  // permissions.request precisa acontecer direto no clique, antes de qualquer await.
  chrome.permissions.request({ origins: [`${new URL(endereco).origin}/*`] }).then(async (concedida) => {
    if (!concedida) {
      mostrar('Sem permissão para acessar o PROA nesse endereço.', 'erro');
      return;
    }

    await chrome.storage.local.set({ proaUrl: endereco, proaToken: campo('token').value.trim() });
    mostrar('Salvo. Testando conexão…');

    const resposta = await chrome.runtime.sendMessage({ tipo: 'proa', acao: 'eu' });

    if (!resposta || !resposta.ok) {
      mostrar((resposta && resposta.erro) || 'Falha ao testar.', 'erro');
      return;
    }

    const eu = resposta.dados;
    const conexao = eu.tipo === 'usuario'
      ? `Conectado com o token de ${eu.nome}. A extensão carrega os agendamentos do procurador logado no SISAP — para trocar, saia do gov.br e entre com outra conta.`
      : `Conectado com o token individual do procurador ${eu.nome}. Este token só atende esse procurador.`;

    if (eu.extensao && eu.extensao.desatualizada) {
      mostrar(`${conexao} ATENÇÃO: esta extensão está na versão ${eu.extensao.versao_usada} e a atual é ${eu.extensao.versao_atual}. `
        + 'Baixe a nova no PROA (Agendamentos Marinha → Baixar extensão).', 'erro');
      return;
    }

    mostrar(conexao, 'ok');
  });
});
