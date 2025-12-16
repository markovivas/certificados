# Gerador de Certificados WP

Versão: 1.0.0

Plugin para gerar certificados em PDF (frente/verso) a partir do painel e também via área pública para usuários com a role "Assinante".

Principais funcionalidades

- Gerenciamento de modelos (admin): upload de imagens A4 para frente/verso e gerenciamento via painel do WordPress.
- Gerenciamento de participantes (admin + assinante via front-end): cadastro, edição e exclusão.
- Emissão de certificados (admin + assinante via front-end): gera PDF usando TCPDF/FPDI e salva em uploads/certificados/emitidos.
- Shortcodes públicos (para assinantes):
  - `[gerador_certificados_participantes]` — interface front-end para gerenciar participantes.
  - `[gerador_certificados_emissao]` — interface front-end para emitir certificados.

Segurança e permissões

- Nonces usados em formulários públicos (`gcwp_public_actions`).
- Verificação de capabilities: apenas usuários com a role `subscriber` podem acessar os shortcodes públicos.
- Cada participante é vinculado ao `user_id` do assinante que o criou — assinantes só veem/edita/excluem os seus próprios participantes.

Arquivos importantes

- `gerador-certificados-wp.php` — arquivo principal do plugin.
- `certificate-generator.php` — classe que gera o PDF (TCPDF + FPDI).
- `class-participants-list-table.php` — tabela administrativa dos participantes (WP_List_Table).
- `admin-menu.php`, `page-*.php` — páginas do admin para gestão de modelos, emissão e configurações.
- `public/class-gcwp-public.php` — shortcodes e handlers AJAX públicos (assinantes).
- `public/public.js` — scripts JS para os shortcodes públicos.
- `public/public.css` — estilos usados pela interface pública.

Como usar (rápido)

1. Ative o plugin.
2. Crie duas páginas no WordPress e cole os shortcodes:

```
[gerador_certificados_participantes]
[gerador_certificados_emissao]
```

3. Faça login com um usuário `Subscriber` e acesse as páginas para gerenciar participantes e emitir certificados sem entrar no painel.

Notas técnicas

- Bibliotecas necessárias: `tecnickcom/tcpdf` e `setasign/fpdi` (via Composer). O plugin checa se `vendor/autoload.php` existe.
- Ao ativar o plugin é criada a tabela `wp_gcwp_participantes` (prefixo pode variar). Uma coluna `user_id` é adicionada automaticamente para conectar participantes aos assinantes.
- Modelos (frente/verso) são armazenados em: `/wp-content/uploads/certificados/modelos/<slug>/frente.*` e `verso.*`.
- Certificados gerados ficam em: `/wp-content/uploads/certificados/emitidos/`.

Próximos passos sugeridos

- Histórico de certificados por assinante (tabela própria) — opcional para exibir emissão anterior.
- Proteção de downloads (proteger arquivos gerados por acesso público) — caso necessário.
- Importação CSV via front-end (removida temporariamente da interface pública; implementação futura).

Suporte

Se precisar que eu ajuste o layout, adicione histórico ou proteja os PDFs por login, posso implementar nas próximas iterações.
# Gerador de Certificados WP

**Versão:** 1.0.0
**Autor:** (Seu Nome)
**Requer PHP:** 8.0+
**Requer WordPress:** 5.0+

Um plugin para WordPress que permite criar, personalizar e gerar certificados em PDF (frente e verso) diretamente do painel administrativo, com total controle sobre o layout e estilo.

---

## Funcionalidades

*   **Gerenciamento de Modelos**:
    *   Crie modelos de certificado ilimitados, cada um com uma imagem de fundo para a frente e, opcionalmente, para o verso.
    *   Interface visual para gerenciar modelos: selecionar como padrão, renomear e apagar.

*   **Gerenciamento de Participantes**:
    *   Cadastre participantes individualmente com informações detalhadas (nome, curso, datas, livro, página, etc.).
    *   Liste, busque, edite e exclua participantes de forma fácil através de uma tabela administrativa.

*   **Emissão de Certificados**:
    *   Gere certificados em PDF no formato A4 paisagem com um clique.
    *   Selecione o participante e o modelo a ser usado no momento da emissão.
    *   Visualize um histórico dos últimos certificados gerados na própria página de emissão.
    *   Reenvie certificados por e-mail diretamente do histórico.

*   **Configuração Visual Avançada**:
    *   **Ajuste de Posição Gráfico**: Arraste e solte os campos de texto sobre uma pré-visualização do modelo para ajustar suas posições com precisão.
    *   **Estilo Individual**: Controle a **fonte**, **tamanho**, **cor** e **negrito** para cada campo de texto do certificado de forma independente.
    *   **Fontes Personalizadas**: Adicione seus próprios arquivos de fonte (`.ttf`) na pasta `fonts` do plugin para usá-los nos certificados.

*   **Administração**:
    *   Função para resetar o plugin, apagando todos os dados (participantes, modelos, certificados emitidos) e configurações.

## Instalação

1.  **Dependências**: Certifique-se de ter o Composer instalado. Navegue até a pasta do plugin (`/wp-content/plugins/certificados/`) via terminal e execute o comando:
    ```bash
    composer install
    ```
    Isso instalará a biblioteca TCPDF, necessária para a geração dos PDFs.

2.  **Ativação**:
    *   Faça o upload da pasta `certificados` para o diretório `/wp-content/plugins/` da sua instalação WordPress.
    *   Vá para o menu "Plugins" no painel do WordPress e ative o "Gerador de Certificados WP".

## Como Usar

Após a ativação, um novo menu chamado **"Certificados"** aparecerá no seu painel.

1.  **(Opcional) Adicionar Fontes**:
    *   Coloque arquivos de fonte `.ttf` (ex: `Roboto-Regular.ttf`) dentro da pasta `/wp-content/plugins/certificados/fonts/`. Elas aparecerão automaticamente nas opções de estilo.

2.  **Criar um Modelo**:
    *   Vá para **Certificados > Modelos**.
    *   Clique na aba "Adicionar Novo Modelo".
    *   Dê um nome ao modelo e envie as imagens de fundo para a frente e (opcionalmente) para o verso.
    *   Na aba "Gerenciar Modelos", clique em "Selecionar" no modelo que você acabou de criar para defini-lo como padrão.

3.  **Ajustar o Layout**:
    *   Vá para **Certificados > Configurações**.
    *   Nesta página, você pode ajustar a posição e o estilo de cada campo de texto. Use a área de **"Ajuste Gráfico de Posição"** para arrastar os campos para os locais desejados ou insira as coordenadas (X e Y) manualmente.
    *   Ajuste também a **fonte**, **tamanho**, **cor**, **negrito** e **alinhamento** para cada campo.
    *   Clique em "Salvar Configurações".

    Abaixo, uma sugestão de configuração inicial que pode ser usada como ponto de partida:

    #### **Posição dos Textos (Frente)**

    | Campo                    | X   | Y   | Fonte          | Tamanho | Cor                   | Negrito | Alinhar  |
    | ------------------------ | --- | --- | -------------- | ------- | --------------------- | ------- | -------- |
    | **Nome do Participante** | 0   | 57  | ArialCEMTBlack | 35      | Azul escuro (#002e67) | Sim     | Centro   |
    | **Curso**                | 0   | 90  | ArialCEMTBlack | 35      | Azul escuro (#002e67) | Sim     | Centro   |
    | **Data de Início**       | 100 | 131 | ArialCEMTBlack | 20      | Azul escuro (#002e67) | Sim     | Esquerda |
    | **Data de Término**      | 155 | 131 | ArialCEMTBlack | 20      | Azul escuro (#002e67) | Sim     | Esquerda |
    | **Duração (horas)**      | 122 | 143 | ArialCEMTBlack | 25      | Azul escuro (#002e67) | Sim     | Esquerda |
    | **Local e Data**         | 0   | 160 | Times          | 16      | Preto                 | Não     | Centro   |

    #### **Posição dos Textos (Verso)**

    | Campo                              | X   | Y   | Fonte          | Tamanho | Cor   | Negrito |
    | ---------------------------------- | --- | --- | -------------- | ------- | ----- | ------- |
    | **Número do Livro**                | 93  | 188 | ArialCEMTBlack | 12      | Preto | Sim     |
    | **Número da Página**               | 188 | 188 | ArialCEMTBlack | 12      | Preto | Sim     |
    | **Número do Registro/Certificado** | 260 | 188 | ArialCEMTBlack | 12      | Preto | Sim     |

4.  **Cadastrar um Participante**:
    *   Vá para **Certificados > Participantes**.
    *   Clique em "Adicionar Novo" e preencha as informações do participante.
    *   Salve o registro.

5.  **Emitir o Certificado**:
    *   Vá para **Certificados > Emissão**.
    *   Selecione o participante e o modelo desejado nos menus suspensos.
    *   Clique em "Gerar PDF".
    *   Um link para download aparecerá, e o certificado será listado no histórico à direita.

## Estrutura de Arquivos Importante

*   `/wp-content/plugins/certificados/fonts/`
    *   Local para adicionar seus arquivos de fonte `.ttf`.

*   `/wp-content/uploads/certificados/`
    *   **`modelos/`**: Contém as imagens de fundo dos seus modelos, organizadas em subpastas.
    *   **`emitidos/`**: Onde todos os arquivos PDF dos certificados gerados são armazenados.

> **Nota**: O conteúdo da pasta `/uploads/certificados/` não é removido ao desativar o plugin, mas é apagado se a função "Resetar Plugin" for utilizada.

## Requisitos

*   WordPress 5.0 ou superior.
*   PHP 8.0 ou superior.
*   Acesso ao terminal para executar `composer install`.

## Changelog

### 1.0.0 - Lançamento Inicial
*   Criação da estrutura base do plugin.
*   Implementação das seções: Modelos, Participantes, Emissão e Configurações.
*   Geração de PDF com TCPDF usando imagens de fundo.
*   Sistema de cadastro e listagem de participantes.
*   Configuração de posição e estilo (tamanho, cor, negrito) para os campos de texto.
*   Implementação de interface gráfica para ajuste de posição (arrastar e soltar).
*   Suporte a fontes personalizadas (`.ttf`).
*   Funcionalidades de gerenciamento de modelos (renomear, apagar, selecionar padrão).
*   Função de reset completo do plugin.