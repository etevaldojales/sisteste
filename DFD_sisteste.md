# Diagrama de Fluxo de Dados (DFD) - Sistema `sisteste`

Este documento descreve o Diagrama de Fluxo de Dados (DFD) para o projeto `sisteste`, uma API de gerenciamento de produtos com integração com Elasticsearch.

## Nível 0: Diagrama de Contexto

Este diagrama apresenta a visão mais macro do sistema, mostrando suas interações com entidades externas.

*   **Entidades Externas:**
    *   `Usuário/Cliente API`: Qualquer aplicação ou pessoa que consome a API.
    *   `Administrador do Sistema`: Pessoa que opera o sistema via linha de comando (CLI).

*   **Sistema:**
    *   `Sistema de Gerenciamento de Produtos (sisteste)`

*   **Fluxos de Dados:**
    1.  **Requisições da API** (De `Usuário/Cliente API` para o `Sistema`):
        *   Dados para criar/atualizar produtos.
        *   Solicitação de busca/listagem de produtos.
        *   Arquivo de imagem para upload.
        *   Pedidos de exclusão.
    2.  **Respostas da API** (Do `Sistema` para o `Usuário/Cliente API`):
        *   Dados dos produtos (criados, atualizados ou listados).
        *   Resultados da busca.
        *   Confirmação de sucesso ou mensagens de erro.
    3.  **Comandos CLI** (Do `Administrador do Sistema` para o `Sistema`):
        *   Comando para reindexar produtos.
    4.  **Saída do Terminal** (Do `Sistema` para o `Administrador do Sistema`):
        *   Status e logs da execução dos comandos.

---

## DFD Nível 1: Visão Detalhada

Este diagrama expande o Nível 0, detalhando os processos internos, fluxos e armazenamentos de dados do sistema.

*   **Entidades Externas:**
    *   `Usuário/Cliente API`
    *   `Administrador do Sistema`

*   **Processos Principais:**
    *   `P1: Gerenciar Requisições de Produto (Controller)`: Recebe e valida as requisições HTTP para o CRUD de produtos.
    *   `P2: Gerenciar Lógica de Negócio (Service)`: Orquestra as operações de criação, atualização e exclusão.
    *   `P3: Processar Busca (Service/Controller)`: Manipula as requisições de busca, utilizando o Elasticsearch.
    *   `P4: Sincronizar Produto (Job)`: Processo em background que adiciona, atualiza ou remove um produto do índice do Elasticsearch.
    *   `P5: Reindexar Produtos (Comando CLI)`: Processo manual para sincronizar todos os produtos do banco de dados com o Elasticsearch.
    *   `P6: Gerenciar Upload de Imagem`: Processa o upload do arquivo de imagem e o associa a um produto.

*   **Armazenamentos de Dados (Data Stores):**
    *   `D1: Banco de Dados SQL`: Armazena os dados principais dos produtos (tabela `products`).
    *   `D2: Índice Elasticsearch`: Armazena os dados dos produtos de forma otimizada para busca.
    *   `D3: Sistema de Arquivos (Storage)`: Guarda os arquivos de imagem dos produtos.

*   **Fluxos de Dados Detalhados:**
    1.  `Usuário/Cliente API` envia uma **Requisição de CRUD** (criar/atualizar/deletar) para `P1`.
    2.  `P1` envia os **Dados do Produto Validados** para `P2`.
    3.  `P2` envia os **Dados para Persistência** para o `D1: Banco de Dados SQL`.
    4.  Após a escrita no `D1`, um gatilho (Observer) dispara o job `P4: Sincronizar Produto` com os **Dados do Produto Modificado**.
    5.  `P4` processa e envia os **Dados para Indexação/Remoção** para o `D2: Índice Elasticsearch`.
    6.  `P2` retorna a **Resposta da Operação** para `P1`, que a formata como **Resposta HTTP** para o `Usuário/Cliente API`.
    7.  `Usuário/Cliente API` envia uma **Requisição de Upload** com um **Arquivo de Imagem** para `P6`.
    8.  `P6` salva o **Arquivo de Imagem** no `D3: Sistema de Arquivos` e retorna a **URL da Imagem**.
    9.  A **URL da Imagem** é associada ao produto através do fluxo de atualização (`P1` -> `P2` -> `D1`).
    10. `Usuário/Cliente API` envia uma **Requisição de Busca** para `P3`.
    11. `P3` consulta o `D2: Índice Elasticsearch` com os **Termos da Busca**.
    12. `D2` retorna os **Resultados da Busca** para `P3`.
    13. `P3` formata e envia a **Resposta da Busca** para o `Usuário/Cliente API`.
    14. `Administrador do Sistema` executa o **Comando de Reindexação** em `P5`.
    15. `P5` lê **Todos os Produtos** do `D1: Banco de Dados SQL`.
    16. `P5` envia os **Dados em Massa para Indexação** para o `D2: Índice Elasticsearch`.
    17. `P5` envia o **Status da Execução** como **Saída do Terminal** para o `Administrador do Sistema`.
