import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const L = require('../lib.js');

// Formato registrado no SISAP em 15/09/2026 (Capitania Fluvial de Mato Grosso).
const agenda = {
  lretorno: true,
  cmensagem: '',
  agenda: [
    {
      ddata: '2026/9/21',
      ddatacompleta: 'Segunda-feira, 21 de Setembro de 2026',
      turnomanha: [{ choriarioinicial: '10:30', cidshorarios: 'tok-21-1030' }],
      turnotarde: [{ choriarioinicial: '14:00', cidshorarios: 'tok-21-1400' }],
    },
    {
      ddata: '2026/9/23',
      turnomanha: [{ choriarioinicial: '8:30', cidshorarios: 'tok-23-0830' }],
      turnotarde: [{ choriarioinicial: '14:00', cidshorarios: 'tok-23-1400' }],
    },
  ],
};

test('extrai horarios da agenda com data e hora padronizadas', () => {
  const r = L.extrairHorarios(agenda);

  assert.equal(r.ok, true);
  assert.deepEqual(r.horarios, [
    { data: '2026-09-21', hora: '10:30', turno: 'manha', token: 'tok-21-1030' },
    { data: '2026-09-21', hora: '14:00', turno: 'tarde', token: 'tok-21-1400' },
    { data: '2026-09-23', hora: '08:30', turno: 'manha', token: 'tok-23-0830' },
    { data: '2026-09-23', hora: '14:00', turno: 'tarde', token: 'tok-23-1400' },
  ]);
});

test('agenda recusada devolve a mensagem do SISAP', () => {
  const r = L.extrairHorarios([{ lretorno: false, cmensagem: 'Sem vagas' }]);
  assert.equal(r.ok, false);
  assert.equal(r.mensagem, 'Sem vagas');
});

test('resultado do envio com sucesso e com recusa', () => {
  assert.deepEqual(
    L.resultadoDoEnvio({ lretorno: true, cnumeroagendamento: '483-000836/2026', cchaveconfirmacao: '2Y6B4C', cidagendamento: 991 }),
    { ok: true, numero: '483-000836/2026', chave: '2Y6B4C', sisapId: '991' },
  );

  assert.deepEqual(
    L.resultadoDoEnvio({ lretorno: false, cmensagem: 'Captcha inválido' }),
    { ok: false, mensagem: 'Captcha inválido' },
  );
});

test('identifica o horario enviado pelo token do formulario', () => {
  const { horarios } = L.extrairHorarios(agenda);
  const corpo = 'timeout=30&nidom=178&ddata=2026-09-23&cidshorario=tok-23-1400&_captcha=AB12CD';

  assert.deepEqual(L.horarioEnviado(corpo, horarios), { data: '2026-09-23', hora: '14:00' });
  assert.equal(L.horarioEnviado('cidshorario=outro', horarios), null);
});

test('encontra OMs em qualquer nivel do JSON', () => {
  const json = [{ oms: [{ nidom: 136, cnomedaom: 'CAPITANIA FLUVIAL DE GOIÁS' }, { nidom: '178', cnomedaom: 'CAPITANIA FLUVIAL DE MATO GROSSO' }] }];

  assert.deepEqual(L.encontrarOms(json), [
    { nidom: '136', nome: 'CAPITANIA FLUVIAL DE GOIÁS' },
    { nidom: '178', nome: 'CAPITANIA FLUVIAL DE MATO GROSSO' },
  ]);
});

test('le etapa, CPF do cabecalho e contador de servicos', () => {
  const pagina = 'MARCO ANTONIO BORGES NUNES | 036.746.601-56 | OURO | SAIR\n'
    + 'AGENDAMENTO PARA REPRESENTANTE LEGAL - ETAPA 3 DE 5\nKENTARO I*** 002.691.071-34\nSERVIÇOS 2 DE 5';

  assert.equal(L.etapaDoTexto(pagina), 3);
  assert.equal(L.cpfDoCabecalho(pagina), '03674660156');
  assert.deepEqual(L.contadorServicos(pagina), { usados: 2, total: 5 });
  assert.equal(L.etapaDoTexto('Página inicial'), null);
});

test('identifica o procurador logado pelo auth.php e pelo cabecalho', () => {
  assert.equal(L.cpfDoLogin({ logado: true, usuario: 'EDIVANIA BORGES NUNES', cpfcnpj: '54983363149', nivel: 'OURO' }), '54983363149');
  assert.equal(L.cpfDoLogin([{ logado: true, cpfcnpj: '549.833.631-49' }]), '54983363149');
  assert.equal(L.cpfDoLogin({ logado: false, cpfcnpj: '' }), null);
  assert.equal(L.cpfDoLogin(null), null);

  const cabecalho = 'Portos e Costas\nMARINHA DO BRASIL\nEDIVANIA BORGES NUNES | 549.833.631-49 | OURO | SAIR';
  assert.equal(L.nomeDoCabecalho(cabecalho), 'EDIVANIA BORGES NUNES');
  assert.equal(L.cpfDoCabecalho(cabecalho), '54983363149');
  assert.equal(L.nomeDoCabecalho('sem login'), null);
});

test('normaliza acentos, espacos e caixa para comparar textos do SISAP', () => {
  assert.equal(
    L.normalizar('  TIE (Título de Inscrição)  -  RENOVAÇÃO '),
    'TIE (TITULO DE INSCRICAO) - RENOVACAO',
  );
  assert.equal(
    L.normalizar('TIE - MOTO AQUATICA – RENOVACAO — INSCRITA'),
    'TIE - MOTO AQUATICA - RENOVACAO - INSCRITA',
  );
  assert.equal(L.formatarCpf('00269107134'), '002.691.071-34');
  assert.equal(L.formatarData('2026-09-23'), '23/09/2026');
});
