# Resources

`Resource` is the base class for endpoint groups.

Public resource modifiers are available on resource instances. Protected request helpers are for SDK resource classes.

## `query(string $name, mixed $value): static`

Public resource modifier.

Returns a cloned resource with one query option.

```php
return $this
    ->query('active', true)
    ->get('/users')
    ->collection(User::class, key: 'data');
```

Null query values are omitted.

## `queries(array $query): static`

Public resource modifier.

Returns a cloned resource with multiple query options.

```php
$this->queries(['active' => true, 'locale' => 'pt']);
```

## `header(string $name, mixed $value): static`

Public resource modifier.

Returns a cloned resource with one header option.

```php
$this->header('X-Tenant', $tenant);
```

## `headers(array $headers): static`

Public resource modifier.

Returns a cloned resource with multiple header options.

```php
$this->headers(['X-Tenant' => $tenant]);
```

## `json(array $data): static`

Public resource modifier.

Sets a JSON request body and `Content-Type: application/json`.

```php
return $this
    ->json(['name' => 'John'])
    ->post('/users')
    ->entity(User::class);
```

## `form(array $data): static`

Public resource modifier.

Sets a form-encoded request body and `Content-Type: application/x-www-form-urlencoded`.

```php
$this->form(['name' => 'John Doe']);
```

## `body(mixed $body): static`

Public resource modifier.

Sets a raw string, stream, or null request body.

```php
$this->body($stream);
```

Passing an array throws. Use `json()` or `form()` for array data.

## HTTP Helpers

Protected resource helpers execute the request immediately and return `Response`:

```php
$this->get('/users');
$this->post('/users');
$this->put('/users/{id}', ['id' => $id]);
$this->patch('/users/{id}', ['id' => $id]);
$this->delete('/users/{id}', ['id' => $id]);
$this->head('/users');
$this->options('/users');
$this->connect('/users');
$this->trace('/users');
```

All helpers accept:

```php
string $path
array $pathParams = []
array $query = []
```

## `send(string $method, string $path, array $pathParams = [], array $query = []): Response`

Protected escape hatch for methods without a named helper.

```php
use ProgrammatorDev\Api\Http\Method;

return $this
    ->send(Method::TRACE, '/debug')
    ->raw();
```
