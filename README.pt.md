# Modelos eloquentes do WordPress

O componente WordPress Eloquent Model é um kit de ferramentas abrangente que fornece um construtor de esquema e ORM. Ele suporta MySQL, Postgres, SQL Server e SQLite. Ele traduz tabelas do WordPress para [modelos compatíveis com Eloquent](https://laravel.com/docs/7.x/eloquent).

A biblioteca é ideal para uso com Bedrock / Sage from Roots.

Não há mais necessidade de usar a velha classe WP_Query, entramos no mundo do futuro produzindo código legível e reutilizável! Recursos adicionais também estão disponíveis para uma experiência de usuário personalizada do WordPress.

A biblioteca que oferece compatibilidade com o Eloquent, você pode consultar o [Documentação do ORM](https://laravel.com/docs/7.x/eloquent)se você está um pouco perdido :)

# Resumo

-   [Instalação](#instalação)
-   [Estabelecimento](#estabelecimento)
-   [Modelos suportados](#modelos-suportados)
    -   [Posts](#posts)
    -   [Comments](#comments)
    -   [Terms](#terms)
    -   [Users](#users)
    -   [Options](#options)
    -   [Menus](#menus)
-   [Imagens](#imagens)
-   [Alias de campos](#alias-de-campos)
-   [Scopes personalizados](#scopes-personalizados)
-   [Paginação](#paginação)
-   [Meta](#meta)
-   [Request de um campo personalizado de um Post (Meta)](#request-de-um-campo-personalizado-de-um-post-meta)
-   [Advanced Custom Fields](#advanced-custom-fields)
-   [Criação de tabela](#criação-de-tabela)
-   [Consultas avançadas](#consultas-avançadas)
-   [Modelos personalizados](#modelos-personalizados)
    -   [Definição do modelo Eloquent](#definição-do-modelo-eloquent)
    -   [Consultas de modelo personalizado](#consultas-de-modelo-personalizado)
-   [Shortcode](#shortcode)
-   [Request logs](#request-logs)

## Instalação

O método de instalação recomendado é [Composer](https://getcomposer.org/).

    composer require amphibee/wordpress-eloquent-models

## Estabelecimento

A conexão com a base de dados (via $wpdb) é feita na primeira chamada de um modelo Eloquent.
Se você precisar da instância de conexão, basta executar o seguinte código (prefira o uso de `use`) :

```php
AmphiBee\Eloquent\Database::instance();
```

## Modelos suportados

### Posts

```php

use \AmphiBee\Eloquent\Model\Post;

// Get Post with ID 1
$post = Post::find(1);

// Related data available
$post->author;
$post->comments;
$post->terms;
$post->tags;
$post->categories;
$post->meta;

```

**_Status_**

Por padrão, `Post` retorna todos os artigos independentemente de seu status. Isso pode ser alterado através de um [local scope](https://laravel.com/docs/7.x/eloquent#query-scopes) `published` para retornar apenas artigos publicados.

```php
Post::published()->get();
```

Também é possível definir o status em questão através do [local scope](https://laravel.com/docs/7.x/eloquent#query-scopes#query-scopes) `status`.

```php
Post::status('draft')->get();
```

**_PostType_**

Por padrão, `Post` retorna o conjunto de tipos de conteúdo. Isso pode ser alterado através do [local scope](https://laravel.com/docs/7.x/eloquent#query-scopes#query-scopes)`type`.

```php
Post::type('page')->get();
```

### Comments

```php

use \AmphiBee\Eloquent\Model\Comment;

// Get Comment with ID 12345
$comment = Comment::find(12345);

// Related data available
$comment->post;
$comment->author;
$comment->meta

```

### Terms

Nesta release `Term` é acessível como um modelo, mas só é acessível através de um Post. No entanto, basta estender `Term` para aplicá-lo a outros tipos de conteúdo personalizados.

```php
$post->terms()->where('taxonomy', 'country');
```

### Users

```php

use \AmphiBee\Eloquent\Model\User;

// All Users
$users = User::get();

// Find User with ID 123
$user = User::find(123);

```

### Options

No WordPress, para obter dados das opções é feita com a função `get_option`. Com o Eloquent, para evitar o carregamento desnecessário do Core WordPress, você pode usar a função `get` do modelo `Option`.

```php
$siteUrl = Option::get('siteurl');
```

Você também pode adicionar outras opções:

```php
Option::add('foo', 'bar'); // Stored as a string
Option::add('baz', ['one' => 'two']); // The array will be serialized
```

Você pode obter todas as opções como um array (preste atenção ao desempenho...):

```php
$options = Option::asArray();
echo $options['siteurl'];
```

Você também pode especificar opções específicas para obter:

```php
$options = Option::asArray(['siteurl', 'home', 'blogname']);
echo $options['home'];
```

### Menus

Para obter um menu de seu alias, use a sintaxe abaixo. Os itens do menu serão retornados numa variável `items` (é uma coleção de objetos do tipo `AmphiBee\Eloquent\Model\MenuItem`).

Os tipos de menu atualmente suportados são: Pages, Posts, Custom Links e Categories.

Assim que tiver o modelo `MenuItem`, caso queira usar a instância original (como Page ou Term, por exemplo), basta chamar o método `MenuItem::instance()`. O objeto `MenuItem` é apenas um Post cujo `post_type` é igual a `nav_menu_item`:

```php
$menu = Menu::slug('primary')->first();

foreach ($menu->items as $item) {
    echo $item->instance()->title; // if is a Post
    echo $item->instance()->name; // if is a Term
    echo $item->instance()->link_text; // if is a Custom Link
}
```

O método `instance()` retornará os objetos correspondentes:

-   `Post` instância para um item de menu do tipo `post`;
-   `Page` instância para um item de menu do tipo `page`;
-   `CustomLink` instância para um item de menu do tipo `custom`;
-   `Term` instância para um item de menu do tipo `category`;

#### Menus de vários níveis

Para gerir menus de vários níveis, você pode iterar para colocá-los no nível certo, por exemplo.

Você pode usar o método `MenuItem::parent()` para recuperar a instância pai do item de menu:

```php
$items = Menu::slug('foo')->first()->items;
$parent = $items->first()->parent(); // Post, Page, CustomLink or Term (category)
```

Para agrupar menus por pai, você pode usar o método `->groupBy()` na coleção `$menu->items`, que agrupará os elementos de acordo com seu pai (`$item->parent()->ID`).

Para saber mais sobre o método `groupBy()`, [consulte a documentação do Eloquent](https://laravel.com/docs/5.4/collections#method-groupby).

## Alias de campos

O modelo `Post` suporta aliases, portanto, se você inspecionar um objeto `Post` você pode encontrar aliases na tabela estática `$aliases` (tal como `title` por `post_title` e `content` por `post_content`.

```php
$post = Post::find(1);
$post->title === $post->post_title; // true
```

Você pode estender o modelo `Post` para criar o seu próprio. Basta adicionar seus aliases no modelo estendido, ele herdará automaticamente aqueles definidos no modelo `Post`:

```php
class A extends \AmphiBee\Eloquent\Model\Post
{
    protected static $aliases = [
        'foo' => 'post_foo',
    ];
}

$a = A::find(1);
echo $a->foo;
echo $a->title; // retrieved from Post model
```

## Scopes personalizados

Para encomendar Modelos do tipo `Post` ou `User`, você pode usar scopes `newest()` e `oldest()`:

```php
$newest = Post::newest()->first();
$oldest = Post::oldest()->first();
```

## Paginação

Para paginar os resultados, basta usar o método `paginate()` de Eloquent :

```php
// Displays posts with 5 items per page
$posts = Post::published()->paginate(5);
foreach ($posts as $post) {
    // ...
}
```

Para exibir os links de paginação, use o método `links()`:

```php
{{ $posts->links() }}
```

## Meta

O conjunto de modelos Eloquent incorpora o gerenciamento de metadados do WordPress.

Aqui está um exemplo para obter metadados:

```php
// Retrieves a meta (here 'link') from the Post model (we could have used another model like User)
$post = Post::find(31);
echo $post->meta->link; // or
echo $post->fields->link; //or
echo $post->link; // or
```

Para criar ou atualizar os metadados de um utilizador, basta usar os métodos `saveMeta()` ou `saveField()`. Eles retornam um booleano como o método `save()` de Eloquent.

```php
$post = Post::find(1);
$post->saveMeta('username', 'amphibee');
```

É possível guardar vários metadados numa única chamada:

```php
$post = Post::find(1);
$post->saveMeta([
    'username' => 'amphibee',
    'url' => 'https://amphibee.fr',
]);
```

O biblioteca também coloca os métodos `createMeta()` e `createField()`, que funciona como os métodos `saveX()`, mas são usados ​​apenas para criação e retornam o objeto do tipo `PostMeta` criado pela instância, em vez de um booleano.

```php
$post = Post::find(1);
$postMeta = $post->createMeta('foo', 'bar'); // instance of PostMeta class
$trueOrFalse = $post->saveMeta('foo', 'baz'); // boolean
```

## Request de um campo personalizado de um Post (Meta)

Existem diferentes maneiras de realizar uma consulta de um metadados (meta) usando scopes num modelo `Post` (ou qualquer outro modelo usando o trait `HasMetaFields`) :

Para verificar se os metadados existem, use o scope `hasMeta()`:

```php
// Retrieves the first article with the meta "featured_article"
$post = Post::published()->hasMeta('featured_article')->first();
```

Se você deseja direcionar um metadados com um valor específico, é possível usar o scope `hasMeta()` com um valor.

```php
// Retrieves the first post with meta "username" and value "amphibee"
$post = Post::published()->hasMeta('username', 'amphibee')->first();
```

Também é possível realizar uma consulta definindo vários metadados e vários valores associados passando uma matriz de valores para o scope do scope `hasMeta()`:

```php
$post = Post::hasMeta(['username' => 'amphibee'])->first();
$post = Post::hasMeta(['username' => 'amphibee', 'url' => 'amphibee.fr'])->first();
// Or just providing metadata keys
$post = Post::hasMeta(['username', 'url'])->first();
```

Se você precisar corresponder a uma string que não diferencia maiúsculas de minúsculas ou uma correspondência curinga, poderá usar o escopo`hasMetaLike()`com um valor. Isso usará o operador SQL `LIKE`, por isso é importante usar o operador wildcard '%'.

```php
// Will match: 'B Gosselet', 'B BOSSELET', and 'b gosselet'.
$post = Post::published()->hasMetaLike('author', 'B GOSSELET')->first();

// Using the % operator, the following results will be returned: 'N Leroy', 'N LEROY', 'n leroy', 'Nico Leroy' etc.
$post = Post::published()->hasMetaLike('author', 'N%Leroy')->first();
```

## Imagens

Obter uma imagem de um modelo `Post` ou `Page`.

```php
$post = Post::find(1);

// Get an instance of AmphiBee\Eloquent\Model\Meta\ThumbnailMeta.
print_r($post->thumbnail);

// You must display the image instance to retrieve the original image url
echo $post->thumbnail;
```

Para recuperar um tamanho de imagem específico, use o método `->size()` no objeto e preencha o alias de tamanho no parâmetro (ex. `thumbnail` ou `medium`). Se a miniatura foi gerada, o método retorna um objeto com os metadados, caso contrário, a url original é retornada (comportamento do WordPress).

```php
if ($post->thumbnail !== null) {
    /**
     * [
     *     'file' => 'filename-300x300.jpg',
     *     'width' => 300,
     *     'height' => 300,
     *     'mime-type' => 'image/jpeg',
     *     'url' => 'http://localhost/wp-content/uploads/filename-300x300.jpg',
     * ]
     */
    print_r($post->thumbnail->size(AmphiBee\Eloquent\Model\Meta\ThumbnailMeta::SIZE_THUMBNAIL));

    // http://localhost/wp-content/uploads/filename.jpg
    print_r($post->thumbnail->size('invalid_size'));
}
```

## Advanced Custom Fields

A biblioteca fornece quase todos os campos ACF (com exceção dos campos do Google Map). Permite obter os campos de forma otimizada sem passar pelo módulo ACF.

### Uso básico

Para recuperar um valor de um campo, tudo o que você precisa fazer é inicializar um modelo de tipo `Post` e invoque o campo personalizado:

```php
$post = Post::find(1);
echo $post->acf->website_url; // returns the url provided in a field with website_url as key
```

### Desempenho

Ao usar `$post->acf->website_url`, consultas adicionais são executadas para recuperar o campo de acordo com a abordagem ACF. É possível usar um método específico para evitar essas solicitações adicionais. Basta preencher o tipo de conteúdo personalizado usado como função:

```php
// The method performing additional requests
echo $post->acf->author_username; // this is a field relative to User

// Without additional request
echo $post->acf->user('author_username');

// Other examples without queries
echo $post->acf->text('text_field_name');
echo $post->acf->boolean('boolean_field_name');
```

> PS: O método deve ser chamado no formato camel case. Por exemplo, para o campo de tipo `date_picker` você tem que escrever `$post->acf->datePicker('fieldName')`. A biblioteca converte camel case para snake case para você.

## Criação de tabela

Docs to come

## Consultas avançadas

Sendo a biblioteca compatível com o Eloquent, você pode facilmente realizar consultas complexas sem levar em conta o contexto do WordPress.

Por exemplo, para recuperar clientes com mais de 40 anos:

```PHP
$users = Capsule::table('customers')->where('age', '>', 40)->get();
```

## Modelos personalizados

### Definição do modelo Eloquent

Para adicionar seu próprio método a um modelo existente, você pode fazer "extends" desse modelo. Por exemplo, para o modelo `User`, você poderia fazer o seguinte código:

```php
namespace App\Model;

use \AmphiBee\Eloquent\Model\User as BaseUser;

class User extends BaseUser {

    public function orders() {
        return $this->hasMany('\App\Model\User\Orders');
    }

    public function current() {
        // functionality specific to the current user
    }

    public function favorites() {
        return $this->hasMany('Favorites');
    }

}
```

Outro exemplo seria definir uma nova Taxomony para um Post, por exemplo `country`

```php
namespace App\Model;

user \AmphiBee\Eloquent\Model\Post as BasePost;

class Post extends BasePost {

    public function countries() {
        return $this->terms()->where('taxonomy', 'country');
    }

}

Post::with(['categories', 'countries'])->find(1);
```

Para aceder o modelo de um novo tipo de conteúdo, aqui está um exemplo do que pode ser oferecido:

```php
namespace App\Model;

class CustomPostType extends \AmphiBee\Eloquent\Model\Post {
    protected $post_type  = 'custom_post_type';

    public static function getBySlug(string $slug): self
    {
        return self::where('post_name', $slug)->firstOrfail();
    }
}

CustomPostType::with(['categories', 'countries'])->find(1);

```

### Consultas de modelo personalizado

Também é possível trabalhar com tipos de conteúdo personalizados. Você pode usar o método `type(string)` ou crie suas próprias classes:

```php
// using the type() method
$videos = Post::type('video')->status('publish')->get();

// by defining its own class
class Video extends AmphiBee\Eloquent\Model\Post
{
    protected $postType = 'video';
}
$videos = Video::status('publish')->get();
```

Usando o método `type()`, o objeto retornado será do tipo `AmphiBee\Eloquent\Model\Post`. Ao utilizar um modelo próprio, permite ir mais longe nas possibilidades ao poder associar métodos e propriedades personalizadas e devolver o resultado como um objeto `Video` por exemplo.

Tipo de conteúdo e metadados personalizados:

```php
// Retrieve 3 elements of a custom content type and by retrieving metadata (address)
$stores = Post::type('store')->status('publish')->take(3)->get();
foreach ($stores as $store) {
    $storeAddress = $store->address; // option 1
    $storeAddress = $store->meta->address; // option 2
    $storeAddress = $store->fields->address; // option 3
}
```

## Shortcode

Implementação em andamento

## Request logs

A Cápsula de Conexão sendo conectada diretamente ao `wpdb`, todas as consultas podem ser visualizadas em ferramentas de depuração, como o Query Monitor.
