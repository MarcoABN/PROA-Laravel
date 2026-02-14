<p align="center">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="PROA Logo">
    </p>

<p align="center">
    <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-10.x-red?style=for-the-badge&logo=laravel" alt="Laravel"></a>
    <a href="https://filamentphp.com"><img src="https://img.shields.io/badge/Filament-3.x-amber?style=for-the-badge&logo=livewire" alt="Filament"></a>
    <a href="https://tailwindcss.com"><img src="https://img.shields.io/badge/Tailwind_CSS-3.0-38B2AC?style=for-the-badge&logo=tailwind-css" alt="Tailwind"></a>
    <a href="https://www.postgresql.org"><img src="https://img.shields.io/badge/PostgreSQL-15-316192?style=for-the-badge&logo=postgresql" alt="Postgres"></a>
</p>

# PROA - Sistema de Gestão para Despachante e Escola Náutica

O **PROA** é uma solução integrada desenvolvida para atender às demandas completas de escritórios de despacho e escolas náuticas (como a *Campeão Náutica*). O sistema automatiza a burocracia exigida pela Marinha do Brasil, gerencia o relacionamento com clientes e alunos, e oferece ferramentas de treinamento para habilitação.

## 🎯 Visão Geral do Sistema

O sistema atua em três frentes principais: **Despacho Documental**, **Gestão Administrativa** e **Educação Náutica**.

### 1. Automação de Documentos (Marinha do Brasil)
Geração automática de anexos e formulários conforme as **NORMAM-211 e 212**, eliminando preenchimento manual e erros.
- **Habilitação (CHA):** Requerimentos de Motonauta/Arrais (Anexos 3A, 5E, 5H), Atestados de Treinamento (3B) e Declarações de Extravio.
- **Embarcações (TIE):** Inscrição e Transferência de Propriedade (Anexos 2D, 2E, 2K, 2M), Termos de Responsabilidade e Construção.
- **Residência:** Declarações automáticas para cliente e embarcação.

### 2. Gestão de Processos e Workflow
Painel de controle para acompanhamento em tempo real dos trâmites junto às Capitanias.
- **Status de Processo:** Triagem, Aguardando Cliente, Em Análise, Concluído, etc.
- **Priorização:** Controle visual de processos Urgentes vs. Normais.
- **Monitoramento de Prazos:** Alertas de vencimento para renovações.

### 3. Módulo Educacional (Simulados)
Ferramenta completa para preparação de alunos para as provas de Arrais e Motonauta.
- **Banco de Questões:** Mais de 2.400 questões cadastradas.
- **Desempenho:** Acompanhamento de notas médias, aprovações e reprovações diretamente no perfil do aluno.
- **Integração:** Vínculo direto entre o cadastro do cliente e seu histórico de treinamento.

## 🗂️ Módulos Principais

* **Painel de Controle:** Visão macro do negócio e atalhos rápidos.
* **Embarcações:** Cadastro detalhado (Lanchas, Jets, Canoas, Iates) com controle de motores e número de inscrição.
* **Clientes:** CRM com dados pessoais, documentos digitalizados e gestão de procurações.
* **Cadastros Auxiliares:** Gestão de Capitanias, Escolas Náuticas credenciadas e Instrutores/Procuradores.
* **Serviços do Site:** Integração para recebimento de propostas e leads via website.

## 🚀 Tecnologias da Stack

O projeto utiliza a **TALL Stack** para oferecer uma interface reativa e moderna:

- **Backend:** Laravel 12
- **Admin Panel:** FilamentPHP v3 (Resources, Widgets, Actions customizadas)
- **Frontend:** Livewire + Blade
- **Banco de Dados:** PostgreSQL
- **Servidor:** Ubuntu + Nginx

