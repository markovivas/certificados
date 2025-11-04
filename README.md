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
    *   Use a área de **"Ajuste Gráfico de Posição"** para arrastar os campos de texto para os locais desejados sobre a imagem do seu modelo.
    *   Na tabela abaixo, ajuste a **fonte**, **tamanho**, **cor** e **negrito** para cada campo individualmente.
    *   Clique em "Salvar Configurações".

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