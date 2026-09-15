/*
 * Roda no contexto da própria página do SISAP (world: MAIN), antes do app carregar.
 * Não faz nenhuma requisição: só observa as respostas que o site já recebe e repassa
 * ao content script as que interessam ao agendamento.
 */
(() => {
  if (window.__proaSisapHook) {
    return;
  }
  window.__proaSisapHook = true;

  const INTERESSA = /(auth|mainpage|buscarcpfcnpj|validargruinformada|agendadespachante|enviaagendamentodespachante|imprimiragendamento)\.php/;

  const abrir = XMLHttpRequest.prototype.open;
  const enviar = XMLHttpRequest.prototype.send;

  XMLHttpRequest.prototype.open = function (metodo, url) {
    this.__proa = { metodo, url: String(url) };
    return abrir.apply(this, arguments);
  };

  XMLHttpRequest.prototype.send = function (corpo) {
    const info = this.__proa;
    const encontrado = info && info.url.match(INTERESSA);

    if (encontrado) {
      this.addEventListener('loadend', () => {
        let json = null;
        try {
          json = JSON.parse(this.responseText);
        } catch (e) {
          // resposta não-JSON (ex.: bloqueio do Cloudflare)
        }

        window.postMessage({
          __proaSisap: true,
          endpoint: encontrado[1],
          status: this.status,
          json,
          corpo: typeof corpo === 'string' ? corpo : null,
        }, window.location.origin);
      });
    }

    return enviar.apply(this, arguments);
  };
})();
