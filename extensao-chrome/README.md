# PROA – Agendamento Marinha (extensão do Chrome)

Preenche o **Agendamento para representante legal** do SISAP com os agendamentos cadastrados no PROA
e devolve o resultado (data, hora, número e chave) ao PROA.

- A extensão **só clica e digita na tela do SISAP**, como o usuário faria. Ela não chama os endereços
  internos do site por conta própria, para não acionar o bloqueio do Cloudflare.
- O **CAPTCHA e o clique em FINALIZAR são sempre do usuário**.
- Se algum passo não puder ser automatizado, a extensão **pausa** e pede para você fazer aquele passo
  à mão. O painel tem botões para copiar CPF e GRU.

## Instalação

1. No PROA: **Agendamentos Marinha** → **Baixar extensão**. Extraia o zip numa pasta fixa (ex.: `C:\PROA`).
2. No Chrome: abra `chrome://extensions`, ligue o **Modo do desenvolvedor**, clique em
   **Carregar sem compactação** e escolha a pasta `proa-agendamento-marinha`.
3. No PROA: **Agendamentos Marinha** → **Token da extensão**. Copie o endereço e o token (aparecem uma
   única vez). É um token do seu usuário: vale para **qualquer procurador** e deixa de funcionar se você
   perder a permissão de agendamento.
4. Clique no ícone da extensão → informe o **endereço do PROA** e o **token** → **Salvar e testar**.
   Os dois aparecem juntos na notificação do PROA ao gerar o token. O endereço pode ter caminho
   (ex.: `https://servidor.com.br/proa`), caso o PROA fique num subcaminho do nginx.

### Produção atrás de nginx

- O nginx precisa encaminhar para o PROA as rotas `/api/sisap/...` (no mesmo caminho do painel), com os
  cabeçalhos `Authorization` e `X-Procurador-Cpf` (o nginx repassa cabeçalhos com hífen por padrão;
  não use `underscores_in_headers off` com nomes que tenham `_`).
- Envie `X-Forwarded-Proto` e `X-Forwarded-Host` (`proxy_set_header`), para o PROA montar links em https.
- Se houver Cloudflare na frente, libere `/api/sisap/*` da verificação anti-robô.

### Vários procuradores no mesmo navegador

A extensão lê o **CPF logado no SISAP** e carrega os agendamentos daquele procurador. Para trocar de
procurador, clique em **SAIR** no SISAP e entre pelo gov.br com a outra conta: o painel troca sozinho.
Não troque de conta no meio de um agendamento.

O token individual de procurador (Instrutores / Procuradores → Token da extensão) continua funcionando,
mas só atende aquele procurador.

### Atualização

A extensão manda a versão dela ao PROA. Quando o servidor tiver uma versão mais nova, aparece um aviso no
painel da extensão, nas opções e no topo das telas de Agendamentos Marinha. Para atualizar: baixe o zip
de novo, extraia **por cima da mesma pasta**, clique em **Recarregar** (↻) em `chrome://extensions` e
recarregue a aba do SISAP. Endereço e token continuam salvos (a identidade da extensão é fixa pelo
campo `key` do `manifest.json`).

**Para quem mantém a extensão:** a cada alteração, aumente a `version` do `manifest.json`. É ela que o PROA
compara para avisar quem está desatualizado.

## Uso

1. Cadastre os agendamentos do mês no PROA (**Agendamentos Marinha → Cadastrar agendamentos**).
2. Abra o SISAP (`https://atendimento-dpc.marinha.mil.br/sisap/agendamento/`) e faça login pelo gov.br.
   O painel **PROA · Agendamento** aparece no canto inferior direito com os agendamentos pendentes
   deste procurador.
3. Clique em **Agendar no SISAP** no agendamento desejado. A extensão:
   - confere se o CPF logado é o do procurador do token;
   - volta sempre à tela inicial do SISAP (`#/homemenu`), clica em "Abrir o agendamento eletrônico",
     escolhe a capitania do cadastro no PROA (pelo código SISAP da capitania) e abre o agendamento para
     representante legal;
   - passa pelas etapas 1 e 2;
   - na etapa 3, informa cada CPF, cada GRU e escolhe o serviço pelo texto cadastrado em
     **Serviços do SISAP** no PROA;
   - na etapa 4, envia os horários livres ao PROA, que escolhe pela preferência do cadastro (data sugerida e
     período) mantendo os dois agendamentos do procurador na mesma data e período, e marca data e hora;
   - na etapa 5, marca a ciência, coloca o cursor no CAPTCHA e mostra no painel a imagem do CAPTCHA
     ampliada e sem as letras de ruído (as claras), para facilitar a leitura. Se o SISAP trocar a
     imagem, o painel acompanha.
4. **Digite o CAPTCHA e clique em FINALIZAR.** O resultado vai para o PROA na hora. Depois imprima o
   comprovante normalmente.

### Pausar antes de cada etapa

Com a opção marcada (padrão), a extensão para antes de cada etapa e espera **Continuar**. Use assim nos
primeiros testes. No dia da abertura das vagas, desmarque para ir direto.

### OM sem horários: tentativas automáticas

Com **"Repetir sozinho quando a OM estiver sem horários"** marcado (padrão), quando o SISAP responde
"Não temos horários disponível para esta OM", a extensão clica em OK e tenta de novo a cada 8 a 12 segundos,
sem limite, até liberar. O painel mostra a tentativa e a contagem. **Parar** interrompe.

- "Erro na comunicação" (Cloudflare/login) espera 30 s, 1 min e 2 min; se continuar, pausa e pede para
  conferir o login numa aba nova.
- Quando os horários liberam e o fluxo chega ao CAPTCHA (ou quando precisa de você), aparece uma
  **notificação do Windows** e a janela do SISAP vem para a frente.
- **Deixe a aba do SISAP visível** (pode ficar atrás de outras janelas, mas não minimizada): o Chrome
  deixa lentos os temporizadores de abas minimizadas ou escondidas há mais de 5 minutos.
- Nas notificações do Windows, permita as notificações do Google Chrome.

### Dicas para o dia da abertura

- Abra o SISAP em cada perfil alguns minutos antes, com calma, para o Cloudflare liberar a sessão.
- Não recarregue a página no meio do fluxo.
- Se o SISAP mostrar "Erro na comunicação com o servidor" (bloqueio do Cloudflare), a extensão pausa:
  abra o SISAP numa aba nova, espere carregar, volte e clique em **Continuar** para ela tentar de novo.
- Preencha em **Capitanias** no PROA o "Código da OM no SISAP" (GO 136, Brasília 63, Minas Gerais 133,
  Mato Grosso 78, Araguaia Tocantins 42, Pantanal 76).
- Se a GRU for recusada ou não houver horário, o agendamento fica como **Falhou** no PROA, com o
  motivo. Corrija e clique em **Agendar no SISAP** de novo.

## Desenvolvimento

- `page-hook.js` roda no contexto da página e só observa as respostas do SISAP.
- `content.js` é o painel e a automação. `lib.js` tem as funções puras.
- `background.js` é o único que fala com a API do PROA (`/api/sisap/...`).
- Testes das funções puras (Node 22), dentro de `extensao-chrome`: `node --test "tests/*.test.mjs"`.
  O `package.json` desta pasta existe só para o Node tratar os arquivos como CommonJS; o Chrome o ignora.
