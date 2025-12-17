# Gerador de Certificados WP

**Versão:** 1.0.0  
**Autor:** (Seu Nome)  
**Requer PHP:** 8.0+  
**Requer WordPress:** 5.0+  

Um plugin para WordPress que permite criar, personalizar e gerar certificados em PDF (frente e verso) diretamente do painel administrativo, com total controle sobre o layout e estilo. Inclui interface pública para usuários com role "Assinante" gerenciarem participantes e emitirem certificados.

---

## Funcionalidades

### Administrativas
*   **Gerenciamento de Modelos**:
    *   Crie modelos de certificado ilimitados, cada um com uma imagem de fundo para a frente e, opcionalmente, para o verso (formato A4 paisagem).
    *   Interface visual para gerenciar modelos: selecionar como padrão, renomear e apagar.
    *   Upload de imagens PNG/JPG com processamento automático para compatibilidade com TCPDF (conversão de PNG com alpha para JPG).

*   **Gerenciamento de Participantes**:
    *   Cadastre participantes individualmente com informações detalhadas (nome, curso, datas, duração, cidade, livro, página, certificado).
    *   Liste, busque, edite e exclua participantes de forma fácil através de uma tabela administrativa (WP_List_Table).
    *   Suporte a paginação e filtros.

*   **Emissão de Certificados**:
    *   Gere certificados em PDF no formato A4 paisagem com um clique.
    *   Selecione o participante e o modelo a ser usado no momento da emissão.
    *   Visualize um histórico dos últimos certificados gerados na própria página de emissão.
    *   Reenvie certificados por e-mail diretamente do histórico (não implementado ainda).
    *   Exclua certificados emitidos.

*   **Configuração Visual Avançada**:
    *   Ajuste de posição (X/Y) para cada campo de texto.
    *   Controle de fonte (padrão ou custom via pasta fonts/), tamanho, cor, negrito e alinhamento para cada campo.
    *   Defaults sensatos para posições e estilos.

*   **Administração**:
    *   Função para resetar o plugin, apagando todos os dados (participantes, modelos, certificados emitidos) e configurações.

### Públicas (para Assinantes)
*   **Shortcodes**:
    *   `[gerador_certificados_participantes]` — Interface front-end para gerenciar participantes (assinantes podem adicionar; apenas administradores podem editar e excluir).
    *   `[gerador_certificados_emissao]` — Interface front-end para emitir certificados com seleção de participante e modelo, design responsivo e feedback visual.

*   **Segurança**:
    *   Apenas usuários logados com role "Subscriber" ou superior podem acessar os shortcodes.
    *   O shortcode `[gerador_certificados_participantes]` fica oculto para usuários não autorizados (sem mensagem).
    *   O shortcode `[gerador_certificados_emissao]` mostra mensagem de acesso negado estilizada com link para login e redirecionamento automático após autenticação.
    *   Nonces utilizados em formulários para proteção CSRF.
    *   Validação de dados e sanitização.

---

## Instalação

1.  **Dependências**: Certifique-se de ter o Composer instalado. Navegue até a pasta do plugin (`/wp-content/plugins/gerador-certificados-wp/`) via terminal e execute:
    ```bash
    composer install
    ```
    Isso instala as bibliotecas TCPDF e FPDI, necessárias para a geração dos PDFs.

2.  **Ativação**:
    *   Faça o upload da pasta `gerador-certificados-wp` para o diretório `/wp-content/plugins/` da sua instalação WordPress.
    *   Vá para o menu "Plugins" no painel do WordPress e ative o "Gerador de Certificados WP".
    *   O plugin cria automaticamente a tabela `wp_gcwp_participantes` no banco de dados.

3.  **Configuração Inicial** (Opcional):
    *   Habilite a extensão GD no php.ini do XAMPP para processamento de imagens.

---

## Como Usar

Após a ativação, um novo menu chamado **"Certificados"** aparecerá no seu painel.

### 1. Adicionar Fontes (Opcional)
*   Coloque arquivos de fonte `.ttf` (ex: `ArialCEMTBlack.ttf`) dentro da pasta `/wp-content/plugins/gerador-certificados-wp/fonts/`. Elas aparecerão automaticamente nas opções de estilo.

### 2. Criar um Modelo
*   Vá para **Certificados > Modelos**.
*   Clique na aba "Adicionar Novo Modelo".
*   Dê um nome ao modelo e envie as imagens de fundo para a frente e (opcionalmente) para o verso (recomendado: 1280x720 pixels ou A4).
*   Na aba "Gerenciar Modelos", clique em "Selecionar" no modelo que você criou para defini-lo como padrão.

### 3. Ajustar o Layout
*   Vá para **Certificados > Configurações**.
*   Ajuste a posição (X/Y), fonte, tamanho, cor, negrito e alinhamento para cada campo.
*   Salve as configurações.

Sugestão de configuração inicial (veja tabela no README original).

### 4. Cadastrar um Participante
*   Vá para **Certificados > Participantes**.
*   Clique em "Adicionar Novo" e preencha os dados.
*   Salve.

### 5. Emitir o Certificado
*   Vá para **Certificados > Emissão**.
*   Selecione o participante e o modelo nos menus suspensos.
*   Clique em "Gerar PDF".
*   Baixe o PDF gerado; ele aparecerá no histórico.

### Uso Público (para Assinantes)
*   Crie páginas com os shortcodes `[gerador_certificados_participantes]` e `[gerador_certificados_emissao]`.
*   Usuários com role "Subscriber" podem gerenciar participantes e emitir certificados via front-end.

#### Exemplo de Implementação HTML
Para uma melhor organização visual, envolva os shortcodes em uma div com a classe do plugin:

```html
<div class="gcwp-public-wrap">
    <h2>Gerenciar Participantes</h2>
    [gerador_certificados_participantes]
    
    <h2>Emitir Certificados</h2>
    [gerador_certificados_emissao]
</div>
```

Isso garante que os estilos do plugin sejam aplicados corretamente e mantém consistência visual.

---

## Estrutura de Arquivos Importante

*   `/wp-content/plugins/gerador-certificados-wp/fonts/` — Adicione fontes `.ttf` aqui.
*   `/wp-content/uploads/certificados/modelos/` — Imagens de modelos organizadas em subpastas.
*   `/wp-content/uploads/certificados/emitidos/` — PDFs gerados.

> **Nota**: Dados em `/uploads/certificados/` são preservados ao desativar, mas apagados no reset.

---

## Requisitos

*   WordPress 5.0+.
*   PHP 8.0+.
*   Composer para instalar dependências.
*   Extensão GD habilitada (recomendado para processamento de imagens).

---

## Changelog

### 1.0.0 - Lançamento Inicial
*   Gerenciamento completo de modelos e participantes via admin.
*   Emissão de certificados com seleção de modelo.
*   Configurações de posição e estilo para textos.
*   Processamento de imagens PNG com alpha.
*   Suporte a fontes custom.
*   Interface pública moderna com design responsivo e organizada para participantes e emissão.
*   Mensagem de acesso negado estilizada com link para login.
*   Histórico de emissões com download e exclusão.
*   Função de reset do plugin.
*   Remoção de restrições de usuário para assinantes (acesso total).

---

## Suporte

Para dúvidas ou ajustes, entre em contato. Próximas melhorias possíveis: proteção de downloads, importação CSV, histórico por usuário.