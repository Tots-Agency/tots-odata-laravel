# Tots OData Laravel

Tots OData Laravel is a library that facilitates the implementation of filters, sorting, and expansions in Eloquent queries using the OData protocol.

## Installation

To install the library, use Composer:

```bash
composer require tots/odata-laravel
```

## Usage

### Basic Configuration

To start using the library, you need to configure the models and controllers to support OData.

### Models

In your Eloquent models, define the fields that will be accessible through OData:

```php
class User extends Model
{
    public static $resourceFields = ['id', 'name', 'email'];
}
```

### Controllers

In your controllers, use `ODataBuilder` to apply filters, sorting, and expansions:

```php
use Tots\Odata\ODataBuilder;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();
        $odataBuilder = ODataBuilder::for($query, $request)
            ->allowedFilters(['name', 'email'])
            ->allowedSorts(['name', 'email'])
            ->allowedExpands(['posts']);

        return $odataBuilder->run();
    }
}
```

## Limit

To limit the number of results, use the `$top` parameter in the URL:

```url
/users?$top=5
```

To get all records, use top=0. 

## Skip

To skip a certain number of results, use the `$skip` parameter in the URL:

```url
/users?$skip=10
```

## Filters

To apply filters, use the `$filter` parameter in the URL:

```url
/users?$filter=name eq 'John'
```

### Filter Operators

- `eq`: Equal to
- `ne`: Not equal to
- `gt`: Greater than
- `ge`: Greater than or equal to
- `lt`: Less than
- `le`: Less than or equal to
- `contains`: Contains
- `startswith`: Starts with
- `endswith`: Ends with
- `in`: In list
- `notin`: Not in list

## Sorting

To apply sorting, use the `$orderby` parameter in the URL:

```url
/users?$orderby=name asc
```

## Expansions

To apply expansions, use the `$expand` parameter in the URL:

```url
/users?$expand=posts
```

### Expansions with Performance

You can define expansions with performance to optimize queries:

```php
$odataBuilder->addExpandPerformance('posts', Post::class, function($builder) {
    // Custom logic for performance
});
```

## Available Methods

### ODataBuilder

- `allowedFilters(array $filters)`: Define the allowed filters.
- `allowedSorts(array $sorts)`: Define the allowed sorts.
- `allowedExpands(array $expands)`: Define the allowed expansions.
- `addExpandPerformance(string $relationKey, Model|string $subject, Closure $callback)`: Add an expansion with performance.
- `run()`: Execute the query and return the paginated results.

### ModelParser

- `addSelectRawByModel($subject)`: Add selected fields from a model.
- `convertToRelationModel($result, $relationKey, $subject)`: Convert the results to a related model.