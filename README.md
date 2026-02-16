# Desafio Técnico - API de Catálogo com Busca, Cache e Observabilidade

Esta é a implementação do desafio técnico para a construção de uma API REST em Laravel para gerenciar um catálogo de produtos.

A aplicação utiliza as seguintes tecnologias:
- Laravel 10
- PHP 8.2
- MySQL
- Elasticsearch 8
- Redis
- Docker

## Funcionalidades Implementadas

A API atende aos seguintes requisitos do desafio:

### 1. CRUD de Produtos
- [x] Endpoints para `POST`, `GET`, `PUT`, `DELETE` e listagem paginada.
- [x] Validação de dados (SKU único, nome obrigatório, etc.).
- [x] Utilização de `SoftDeletes`.

### 2. Busca com Elasticsearch
- [x] Endpoint `GET /api/search/products` para buscas.
- [x] Suporte aos filtros: `q`, `category`, `min_price`, `max_price`, `status`, `sort`, `order`.
- [x] Sincronização automática com o MySQL utilizando `Observers` e `Jobs` (assíncrono).

### 3. Cache com Redis
- [x] Cache no endpoint `GET /api/products/{id}`.
- [x] Cache no endpoint de busca `GET /api/search/products`.
- [x] Invalidação automática do cache ao criar, atualizar ou deletar um produto.
- [x] Cache evitado para paginações muito altas (`page > 50`).

### 4. Testes
- [x] Testes de feature para os endpoints do CRUD.
- [x] Testes para validação de dados.
- [x] Teste de cache para o endpoint de detalhe do produto.
- [x] Teste de soft delete.

### 5. Docker
- [x] Arquivo `compose.yaml` para orquestração dos containers (`app`, `mysql`, `redis`, `elasticsearch`, `minio`).

### 6. Diferenciais
- [x] **AWS S3:** Endpoint `POST /api/products/{id}/image` para upload de imagem para um storage S3-compatível (MinIO).
- [x] **CI/CD:** Pipeline básica no GitHub Actions para rodar os testes automaticamente.
- [x] **Arquitetura e Código Limpo:**
    - Utilização do padrão `Services -> Repositories`.
    - `Request Objects` para validação.
    - `DTOs` para transferência de dados.

## Como Rodar com Docker

**1. Pré-requisitos:**
- Docker
- Docker Compose

**2. Clone o repositório:**
```bash
git clone <URL_DO_REPOSITORIO>
cd <NOME_DO_DIRETORIO>
```

**3. Copie o arquivo de ambiente e suba os containers:**
```bash
cp .env.example .env
./vendor/bin/sail up -d
```
> **Nota:** O comando `sail` é um alias para `docker compose`.

**4. Instale as dependências do Composer e gere a chave da aplicação:**
```bash
./vendor/bin/sail composer install
./vendor/bin/sail artisan key:generate
```

**5. Rode as migrations e os seeders:**
```bash
./vendor/bin/sail artisan migrate --seed
```

**6. Crie o índice no Elasticsearch:**
A aplicação está configurada para criar o índice automaticamente na primeira vez que um produto é indexado. Alternativamente, você pode criá-lo manualmente com o seguinte comando:
```bash
./vendor/bin/sail artisan postman:create-index
```
Ou via API:
```http
POST /api/search/products/index
```

A aplicação estará disponível em `http://localhost`.

## Como Rodar os Testes

Para rodar a suíte de testes, execute o seguinte comando:
```bash
./vendor/bin/sail artisan test
```
Os testes utilizam um banco de dados SQLite em memória para agilizar a execução.

## Decisões Técnicas

- **Laravel Sail:** Utilizado para simplificar a interação com o ambiente Docker.
- **MinIO:** Utilizado como um storage de objetos S3-compatível para o upload de imagens, facilitando o desenvolvimento e teste local sem a necessidade de uma conta na AWS.
- **Observers + Jobs:** A sincronização entre MySQL e Elasticsearch é feita de forma assíncrona para não impactar a performance das requisições do usuário.
- **SQLite em memória para testes:** Para garantir que os testes rodem de forma rápida e isolada, sem depender de um banco de dados externo.

## Coleção Postman

Uma coleção do Postman está disponível no arquivo `postman_collection.json` para facilitar os testes da API.