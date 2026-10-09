# Reopen Ticket (Plugin GLPI 11)

Coloca o botão **Reabrir chamado** na tela do ticket quando ele está **solucionado** ou **fechado**. O usuário informa o motivo, o chamado volta para atendimento e o motivo entra no histórico.

## O que faz

- Botão no topo do chamado, só para status solucionado ou fechado.
- Modal pedindo o motivo.
- Muda o status (padrão: Em atendimento).
- Grava um acompanhamento público com o motivo.
- Recusa a solução pendente e remove a pesquisa de satisfação, pelo fluxo normal do GLPI.
- O solicitante do chamado pode reabrir o próprio ticket. Quem tem o direito do plugin reabre qualquer chamado que consiga ver.

## Estrutura

```
reopenticket/
├── setup.php
├── hook.php
├── inc/
│   ├── ticketreopen.class.php
│   ├── config.class.php
│   └── profile.class.php
├── front/
│   ├── reopen.php
│   └── config.form.php
├── ajax/
│   └── reopen.php
└── templates/
    └── reopen_modal.html.twig
```

## Instalação

1. A pasta `reopenticket` fica em `glpi/plugins/reopenticket`.
2. Em **Configuração > Plugins**, instale e ative o **Reopen Ticket**.
3. Em **Configuração > Plugins > Reopen Ticket**, ajuste prazo, status de destino e se o motivo é obrigatório.
4. O direito `plugin_reopenticket` é criado nos perfis. Perfis da interface padrão já recebem leitura na instalação. O solicitante reabre o próprio chamado mesmo sem esse direito.
5. Quem já estava logado precisa entrar de novo para o direito novo valer.

## Configuração padrão

- Reabrir solucionado: sim
- Reabrir fechado: sim
- Prazo: 0 (sem limite)
- Status de destino: Em atendimento
- Motivo obrigatório: sim

## Tabela

- `glpi_plugin_reopenticket_configs`
