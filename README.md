# WordPress Eloquent Models

The WordPress Eloquent Model component is a comprehensive toolkit providing an ORM and schema builder. It supports MySQL, Postgres, SQL Server and SQLite. It translates WordPress tables to [models compatible with Eloquent](https://laravel.com/docs/7.x/eloquent).

The library is ideal for use with Bedrock / Sage from Roots.

No more need to use the old musty WP_Query class, we enter the world of the future by producing readable and reusable code! Additional features are also available for a personalized WordPress user experience.

The library providing compatibility with Eloquent, you can consult the [ORM documentation](https://laravel.com/docs/7.x/eloquent)if you are a bit lost :)

# Summary

-   [Installation](#installation)
-   [Establishment](#establishment)
-   [Supported models](#supported-model)
    -   [Posts](#posts)
    -   [Comments](#comments)
    -   [Terms](#terms)
    -   [Users](#users)
    -   [Options](#options)
    -   [Menus](#menus)
-   [Images](#images)
-   [Field alias](#field-alias)
-   [Custom scopes](#custom-scopes)
-   [Pagination](#pagination)
-   [Meta](#meta)
-   [Post request from a custom field (Meta)](#post-request-from-a-custom-field-meta)
-   [Advanced Custom Fields](#advanced-custom-fields)
-   [Table creation](#table-creation)
-   [Advanced queries](#advanced-queries)
-   [Custom content type](#custom-content-type)
-   [Custom Modules](#custom-modules)
    -   [Defining the Eloquent model](#defining-the-eloquent-model)
    -   [Custom model queries](#custom-model-queries)
-   [Shortcode](#shortcode)
-   [Request logs](#request-logs)

## Installation

The recommended installation method is [Composer](https://getcomposer.org/).

    composer require amphibee/wordpress-eloquent-models

## Establishment

The connection to the database (via $wpdb) is made at the first call of an Eloquent model.
If you need to retrieve the connection instance, simply run the following code (prefer the use of `use`) :

```php
AmphiBee\Eloquent\Database::instance();
```

## Supported models

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

By default, `Post` returns all articles regardless of their status. This can be changed via a [scope local](https://laravel.com/docs/7.x/eloquent#query-scopes) `published` to return only published articles.

```php
Post::published()->get();
```

It is also possible to define the status in question via the [scope local](https://laravel.com/docs/7.x/eloquent#query-scopes#query-scopes) `status`.

```php
Post::status('draft')->get();
```

**_Post Types_**

By default, `Post` returns the set of content types. This can be overridden via the [scope local](https://laravel.com/docs/7.x/eloquent#query-scopes#query-scopes) `type`.

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

In this release `Term` is accessible as a model but is only accessible through an article. However, it suffices to extend `Term` to apply it to other custom content types.

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

In WordPress, options retrieval is done with the function `get_option`. With Eloquent, to avoid unnecessary loading of the Core WordPress, you can use the function `get` of the model `Option`.

```php
$siteUrl = Option::get('siteurl');
```

You can also add other options:

```php
Option::add('foo', 'bar'); // Stored as a string
Option::add('baz', ['one' => 'two']); // The array will be serialized
```

You can retrieve all the options as an array (pay attention to performance...):

```php
$options = Option::asArray();
echo $options['siteurl'];
```

You can also specify specific options to retrieve:

```php
$options = Option::asArray(['siteurl', 'home', 'blogname']);
echo $options['home'];
```

### Menus

To retrieve a menu from its alias, use the syntax below. Menu items will be returned in a variable `items` (it is a collection of object of type `AmphiBee\Eloquent\Model\MenuItem`).

Currently supported menu types are: Pages, Posts, Custom Links and Categories.

Once you have the model `MenuItem`, if you want to use the original instance (such as Page or Term, for example), just call the method `MenuItem::instance()`. The object `MenuItem` is just a post whose `post_type` is equal to `nav_menu_item`:

```php
$menu = Menu::slug('primary')->first();

foreach ($menu->items as $item) {
    echo $item->instance()->title; // if is a Post
    echo $item->instance()->name; // if is a Term
    echo $item->instance()->link_text; // if is a Custom Link
}
```

The method `instance()` will return the corresponding objects:

-   `Post` instance for a menu item of type `post`;
-   `Page` instance for a menu item of type `page`;
-   `CustomLink` instance for a menu item of type `custom`;
-   `Term `instance for a menu item of type `category`.

#### Multi-levels Menus

To manage multi-level menus, you can iterate to place them at the right level, for example.

You can use the method `MenuItem::parent()` to retrieve the parent instance of the menu item:

```php
$items = Menu::slug('foo')->first()->items;
$parent = $items->first()->parent(); // Post, Page, CustomLink or Term (category)
```

To group menus by parent, you can use the method `->groupBy()` in collection `$menu->items`, which will group the elements according to their parent (`$item->parent()->ID`).

To learn more about the method `groupBy()`, [consult the Eloquent documentation](https://laravel.com/docs/5.4/collections#method-groupby).

## Field aliases

The model `Post` support aliases, so if you inspect an object `Post` you can find aliases in the static table `$aliases` (such as `title` for `post_title` and `content` for `post_content`.

```php
$post = Post::find(1);
$post->title === $post->post_title; // true
```

You can extend the model `Post` to create your own. Just add your aliases in the extended template, it will automatically inherit those defined in the template `Post`:

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

## Custom scopes

To Order Models of Type `Post` Where `User` , you can use scopes `newest()` and `oldest()`:

```php
$newest = Post::newest()->first();
$oldest = Post::oldest()->first();
```

## Pagination

To paginate the results, simply use the method `paginate()` de Eloquent :

```php
// Displays posts with 5 items per page
$posts = Post::published()->paginate(5);
foreach ($posts as $post) {
    // ...
}
```

To display the pagination links, use the method `links()`:

```php
{{ $posts->links() }}
```

## Meta

The Eloquent template set incorporates WordPress metadata management.

Here is an example to retrieve metadata:

```php
// Retrieves a meta (here 'link') from the Post model (we could have used another model like User)
$post = Post::find(31);
echo $post->meta->link; // or
echo $post->fields->link; //or
echo $post->link; // or
```

To create or update a user's meta data, just use the methods `saveMeta()` Where `saveField()`. They return a boolean like the method `save()` de Eloquent.

```php
$post = Post::find(1);
$post->saveMeta('username', 'amphibee');
```

It is possible to save multiple meta data in a single call:

```php
$post = Post::find(1);
$post->saveMeta([
    'username' => 'amphibee',
    'url' => 'https://amphibee.fr',
]);
```

The bookseller also puts the methods `createMeta()` and `createField()`, which works how the methods `saveX()`, but they are only used for creation and return the object of type `PostMeta` created by the instance, instead of a boolean.

```php
$post = Post::find(1);
$postMeta = $post->createMeta('foo', 'bar'); // instance of PostMeta class
$trueOrFalse = $post->saveMeta('foo', 'baz'); // boolean
```

## Post request from a custom field (Meta)

There are different ways to perform a query from a metadata (meta) using scopes on a model `Post` (or any other model using the trait `HasMetaFields`) :

To verify that metadata exists, use the scope `hasMeta()`:

```php
// Retrieves the first article with the meta "featured_article"
$post = Post::published()->hasMeta('featured_article')->first();
```

If you want to target a metadata with a specific value, it is possible to use the scope `hasMeta()` with a value.

```php
// Retrieves the first post with meta "username" and value "amphibee"
$post = Post::published()->hasMeta('username', 'amphibee')->first();
```

It is also possible to perform a query by defining several meta-data and several associated values ​​by passing an array of values ​​to the scope scope `hasMeta()`:

```php
$post = Post::hasMeta(['username' => 'amphibee'])->first();
$post = Post::hasMeta(['username' => 'amphibee', 'url' => 'amphibee.fr'])->first();
// Or just providing metadata keys
$post = Post::hasMeta(['username', 'url'])->first();
```

If you need to match a case-insensitive string or wildcard match, you can use the scope `hasMetaLike() `with a value. This will use the SQL operator `LIKE`, so it is important to use the wildcard operator '%'.

```php
// Will match: 'B Gosselet', 'B BOSSELET', and 'b gosselet'.
$post = Post::published()->hasMetaLike('author', 'B GOSSELET')->first();

// Using the % operator, the following results will be returned: 'N Leroy', 'N LEROY', 'n leroy', 'Nico Leroy' etc.
$post = Post::published()->hasMetaLike('author', 'N%Leroy')->first();
```

## Images

Retrieving an image from a template `Post` or `Page`.

```php
$post = Post::find(1);

// Get an instance of AmphiBee\Eloquent\Model\Meta\ThumbnailMeta.
print_r($post->thumbnail);

// You must display the image instance to retrieve the original image url
echo $post->thumbnail;
```

To retrieve a specific image size, use the method `->size()` on the object and fill in the size alias in the parameter (ex. `thumbnail` or `medium`). If the thumbnail was generated, the method returns an object with the metadata, otherwise the original url is returned (WordPress behavior).

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

The library provides almost all ACF fields (with the exception of Google Map fields). It allows to recover the fields in an optimal way without going through the ACF module.

### Basic use

To retrieve a value from a field, all you have to do is initialize a type model `Post` and invoke the custom field:

```php
$post = Post::find(1);
echo $post->acf->website_url; // returns the url provided in a field with website_url as key
```

### Performance

When using `$post->acf->website_url`, additional queries are executed to retrieve the field according to the ACF approach. It is possible to use a specific method to avoid these additional requests. Just fill in the custom content type used as a function:

```php
// The method performing additional requests
echo $post->acf->author_username; // this is a field relative to User

// Without additional request
echo $post->acf->user('author_username');

// Other examples without queries
echo $post->acf->text('text_field_name');
echo $post->acf->boolean('boolean_field_name');
```

> PS: The method must be called in camel case format. Part example, for the type field `date_picker` you have to write `$post->acf->datePicker('fieldName')`. The bookseller converts camel case to snake case for you.

## Table creation

Docs to come

## Advanced queries

The library being compatible with Eloquent, you can easily perform complex queries without taking into account the WordPress context.

For example, to retrieve customers whose age is greater than 40:

```PHP
$users = Capsule::table('customers')->where('age', '>', 40)->get();
```

## Custom templates

### Defining the Eloquent model

To add your own method to an existing model, you can make "extends" of this model. For example, for the model `User`, you could produce the following code:

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

Another example would be to define a new taxonomy to an article, for example `country`

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

To access the template for a new content type, here is an example of what might be offered:

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

### Custom Model Queries

It is also possible to work with custom content types. You can use the method `type(string)` or create your own classes:

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

Using the method `type()`, the returned object will be of type `AmphiBee\Eloquent\Model\Post`. By using its own model, it allows you to go further in the possibilities by being able to associate custom methods and properties and returning the result as an object `Video` for example.

Custom content type and metadata:

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

Implementation in progress

## Request logs

The Connection Capsule being directly attached to `wpdb`, all queries can be viewed on debug tools such as Query Monitor.
