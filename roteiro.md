# Roteiro: Invocação do Service a partir do Controller

Este documento explica o fluxo de execução desde a definição da rota até a chamada do método no `ProductService` dentro do `ProductController`.

## 1. Definição da Rota

Tudo começa no arquivo de rotas `routes/api.php`. Ao contrário de um `Route::apiResource` que cria várias rotas com uma só linha, este projeto define cada rota de forma explícita.

Cada linha mapeia um verbo HTTP (GET, POST, etc.) e um URI para um método específico dentro do `ProductController`.

**Exemplo (`routes/api.php`):**
```php
// Quando uma requisição GET chega em '/products'...
// ...chame o método 'index' da classe ProductController.
Route::get('/products', [ProductController::class, 'index']);

// Quando uma requisição POST chega em '/products'...
// ...chame o método 'store' da classe ProductController.
Route::post('/products', [ProductController::class, 'store']);
```

## 2. O `ProductController` e a Injeção de Dependência

O "pulo do gato" acontece dentro do `ProductController` e não é visível apenas olhando as rotas. O controller utiliza um dos recursos mais importantes do Laravel: **Injeção de Dependência via Service Container**.

### O Construtor (`__construct`)

O método construtor da classe `ProductController` "pede" por uma instância de `ProductServiceInterface`.

**`app/Http/Controllers/ProductController.php`:**
```php
class ProductController extends Controller
{
    protected ProductServiceInterface $productService;

    // 1. O construtor declara que precisa de um objeto que
    //    implemente a interface 'ProductServiceInterface'.
    public function __construct(ProductServiceInterface $productService)
    {
        // 2. O Laravel automaticamente cria uma instância de 'ProductService'
        //    (a classe que implementa a interface) e a "injeta" aqui.

        // 3. A instância recebida é armazenada em uma variável da classe.
        $this->productService = $productService;
    }

    // ... resto do código
}
```

O **Service Container** do Laravel é como uma "fábrica" inteligente que sabe como criar e fornecer objetos quando eles são solicitados, como no caso do construtor acima.

## 3. A Chamada do Método do Serviço

Com a instância do `ProductService` já armazenada na variável `$this->productService`, os métodos do controller (`index`, `store`, etc.) ficam muito simples. A única responsabilidade deles é:
1. Receber a requisição HTTP.
2. Chamar o método correspondente no serviço para executar a lógica de negócio.
3. Retornar uma resposta HTTP (geralmente em JSON).

**`app/Http/Controllers/ProductController.php`:**
```php
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Acessa a instância do serviço e chama o método 'getAll'.
        $products = $this->productService->getAll($request->get('per_page', 15));
        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request)
    {
        $productDto = ProductDto::fromRequest($request);
        // Acessa a instância do serviço e chama o método 'create'.
        $product = $this->productService->create($productDto->toArray());
        return response()->json($product, 201);
    }
```

## Resumo do Fluxo

**Requisição GET `/products`** -> **`routes/api.php`** -> **`ProductController@index()`** -> (Usa a instância injetada de `ProductService`) -> **`$this->productService->getAll()`** -> Retorna o JSON para o cliente.

Este padrão de projeto (injeção de dependência) é fundamental em Laravel, pois promove o **baixo acoplamento** (o controller não precisa saber *como* criar o serviço) e a **separação de responsabilidades** (o controller cuida do HTTP, o serviço cuida da regra de negócio).
