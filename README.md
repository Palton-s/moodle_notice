# Notícias de login (`local_notice`)

Plugin local para Moodle que permite aos administradores publicar **notícias em popup** exibidas aos usuários logo após o login. Cada notícia é montada num editor completo (texto, imagens, vídeos, links, arquivos) e, quando o usuário a fecha, ela **não aparece mais para ele**.

## Funcionalidades

- **Editor rico (TinyMCE):** texto formatado, imagens e vídeos enviados, vídeos incorporados (YouTube etc.), links e arquivos.
- **Popup na primeira página após o login**, centralizado, com botão de confirmação personalizável (padrão: **"Entendi"**).
- **Exibição única por usuário:** fechar pelo botão, pelo **X** ou pela tecla **Esc** registra que a notícia foi vista.
- **Várias notícias:** se houver mais de uma pendente, elas aparecem em sequência, da mais antiga para a mais nova.
- **Liga/desliga** geral (todas as notícias) e por notícia.
- **Agendamento opcional:** datas de início e de fim de exibição.
- **Contador "Visto por"** em cada notícia.
- **"Exibir novamente para todos":** faz uma notícia reaparecer, inclusive para quem já a fechou.
- **Pré-visualização** do popup sem contar como visualização.
- **Privacidade (LGPD/GDPR):** implementa a Privacy API do Moodle (exportação e exclusão dos registros de visualização).

O popup **não** é exibido:

- na tela de login nem nas etapas de autenticação (MFA / verificação em 2 etapas);
- quando o usuário precisa trocar a senha ou aceitar as políticas do site;
- para visitantes (guest) nem durante o "Acessar como" outro usuário;
- em páginas de layout especial (popups, impressão, conteúdo incorporado, manutenção).

## Requisitos

| Item   | Versão                                                   |
|--------|----------------------------------------------------------|
| Moodle | **4.4 ou mais recente** (testado no 5.0.6)               |
| PHP    | o exigido pela sua versão do Moodle (8.1 ou mais recente) |

O mínimo de 4.4 vem da Hooks API de saída (`core\hook\output\before_footer_html_generation`), usada para inserir o popup nas páginas.

> No Moodle 5.1, que adotou a pasta `public/`, o plugin fica em `public/local/notice`. Essa versão ainda não foi testada.

## Instalação

### Opção 1: pelo painel do Moodle

1. Compacte o conteúdo deste repositório num `.zip` cuja pasta raiz se chame `notice`.
2. Acesse **Administração do site → Plugins → Instalar plugins** e envie o `.zip`.
3. Siga as telas de atualização.

### Opção 2: manualmente

1. Copie os arquivos deste repositório para `<moodle>/local/notice`:
   ```bash
   git clone https://github.com/<seu-usuario>/moodle-local_notice.git /caminho/do/moodle/local/notice
   ```
2. Conclua a instalação pelo navegador (**Administração do site → Notificações**) ou pela linha de comando:
   ```bash
   sudo -u www-data php admin/cli/upgrade.php --non-interactive
   ```

Depois de instalado, o plugin já fica **ativado**, mas não exibe nada até você criar uma notícia.

## Como usar

Acesse **Administração do site → Plugins → Plugins locais → Notícias de login**.

### Configurações

| Opção              | Descrição                                                                               |
|--------------------|-----------------------------------------------------------------------------------------|
| **Exibir notícias** | Interruptor geral. Desmarcado, nenhuma notícia aparece, independentemente do status de cada uma. |

### Gerenciar notícias

A página lista todas as notícias com **Título**, **Status** (Ativa, Desativada, Agendada ou Expirada), **Período**, **Visto por** e **Última modificação**, e oferece estas ações:

| Ícone | Ação                                                              |
|-------|-------------------------------------------------------------------|
| ✏️     | Editar                                                            |
| 🔍     | Pré-visualizar o popup (não registra visualização)                |
| 👁️     | Ativar ou desativar a notícia                                     |
| ↺     | Exibir novamente para todos (zera o "Visto por"; pede confirmação) |
| 🗑️     | Excluir (pede confirmação; remove também as mídias enviadas)      |

### Campos da notícia

| Campo                          | Descrição                                                                                 |
|--------------------------------|-------------------------------------------------------------------------------------------|
| **Título**                     | Aparece no topo do popup. Obrigatório.                                                    |
| **Conteúdo**                   | Corpo da notícia: texto, imagens, vídeos, links e arquivos. Obrigatório.                  |
| **Texto do botão**             | Rótulo do botão que fecha a notícia. Em branco, usa "Entendi".                            |
| **Ativa**                      | Liga ou desliga esta notícia.                                                             |
| **Exibir a partir de**         | Opcional. A notícia só aparece a partir desta data.                                       |
| **Exibir até**                 | Opcional. Depois desta data, a notícia deixa de aparecer.                                 |
| **Exibir novamente para todos** | Só na edição. Ao salvar, quem já fechou a notícia volta a vê-la. Útil após mudanças importantes. |

## Permissões

| Capability            | Padrão         | Descrição                                       |
|-----------------------|----------------|-------------------------------------------------|
| `local/notice:manage` | Gerente (Manager) | Criar, editar, excluir e pré-visualizar notícias. |

Administradores do site têm todas as permissões. O interruptor geral fica nas configurações do site e, por isso, exige `moodle/site:config`.

> ⚠️ **Segurança:** o conteúdo das notícias é exibido **sem filtragem de HTML**, para permitir vídeos incorporados (`<iframe>`) e formatação livre. Por isso a capability está marcada com risco **XSS**: conceda-a apenas a pessoas de confiança.

## Como funciona

- **Exibição:** o callback do hook `before_footer_html_generation` ([classes/hook_callbacks.php](classes/hook_callbacks.php)) busca as notícias pendentes do usuário e as insere na página dentro de elementos `<template>`, que mantêm imagens e vídeos inertes até a exibição. O módulo AMD [`local_notice/notice`](amd/src/notice.js) as mostra com o `core/modal` do Moodle.
- **Registro de visualização:** ao fechar o popup, o JavaScript chama o serviço AJAX `local_notice_acknowledge` ([classes/external/acknowledge.php](classes/external/acknowledge.php)), que grava o par notícia/usuário em `local_notice_ack`.
- **Desempenho:** quando o usuário não tem nada pendente, isso fica guardado na sessão e as páginas seguintes não fazem consultas extras. Esse cache é invalidado automaticamente quando uma notícia é criada, editada, ativada ou reiniciada, ou quando chega a data de início de uma notícia agendada.
- **Mídias:** os arquivos ficam na área `local_notice/content` do contexto de sistema e são servidos por `pluginfile.php` para qualquer usuário logado.

### Estrutura

```
local/notice/
├── amd/
│   ├── src/notice.js           # Código-fonte do popup (ES6)
│   └── build/notice.min.js     # Versão carregada pelo Moodle (AMD)
├── classes/
│   ├── external/acknowledge.php  # Serviço AJAX "marcar como vista"
│   ├── form/notice_form.php      # Formulário de criação/edição
│   ├── local/manager.php         # Regras de negócio e consultas
│   ├── privacy/provider.php      # Privacy API (LGPD/GDPR)
│   └── hook_callbacks.php        # Insere o popup nas páginas
├── db/
│   ├── access.php    # Capabilities
│   ├── hooks.php     # Registro do hook
│   ├── install.xml   # Tabelas
│   └── services.php  # Serviços externos
├── lang/
│   ├── en/local_notice.php
│   └── pt_br/local_notice.php
├── templates/notices.mustache
├── edit.php        # Criar/editar notícia
├── lib.php         # Entrega de arquivos (pluginfile)
├── manage.php      # Listagem e ações
├── settings.php    # Menu e configurações
├── styles.css
└── version.php
```

### Tabelas

| Tabela             | Conteúdo                                                                                             |
|--------------------|------------------------------------------------------------------------------------------------------|
| `local_notice`     | As notícias: título, conteúdo, formato, texto do botão, status, período e auditoria.                 |
| `local_notice_ack` | Quem fechou cada notícia (`noticeid`, `userid`, `timecreated`), com índice único em `noticeid` + `userid`. |

## Desenvolvimento

### JavaScript

O Moodle carrega `amd/build/notice.min.js`. Depois de alterar `amd/src/notice.js`, gere o build com o Grunt a partir da raiz do Moodle, com as dependências instaladas (`npm install`):

```bash
npx grunt amd --root=local/notice
```

Sem o Grunt disponível, o arquivo de build também pode ser atualizado à mão, mantendo o formato AMD (`define("local_notice/notice", [...], function(...) {...})`).

### Atualizar a versão

Ao mudar o banco, as capabilities, os serviços ou os hooks, aumente `$plugin->version` em [version.php](version.php) e rode a atualização do Moodle. Para outras mudanças, basta limpar os caches:

```bash
php admin/cli/purge_caches.php
```

## Desinstalação

Em **Administração do site → Plugins → Visão geral dos plugins**, localize **Notícias de login** e clique em **Desinstalar**. O Moodle remove as tabelas, as configurações e os arquivos enviados.

## Licença

Este plugin é software livre, distribuído sob a [GNU General Public License v3](https://www.gnu.org/licenses/gpl-3.0.html) ou posterior, como o próprio Moodle.
