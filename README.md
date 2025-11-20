# Rifa API Client

Paquete reusable para proyectos Laravel que consumen la API segura de rifas. Provee un cliente HTTP con firma HMAC, service provider, fachada y archivo de configuración publishable.

## Instalación (Packagist / repo individual)

1. Publica este paquete en un repositorio Git independiente (ej. `https://github.com/vgcsolutions/rifa-api-client`).
2. Registra el paquete en Packagist o agrega un repositorio VCS en tus proyectos:
   ```json
   {
       "repositories": [
           {
               "type": "vcs",
               "url": "https://github.com/vgcsolutions/rifa-api-client"
           }
       ]
   }
   ```
3. Requiere el paquete:
   ```bash
   composer require vgcsolutions/rifa-api-client
   ```
4. Laravel detectará automáticamente el service provider/facade gracias al `extra.laravel` definido en `composer.json`. Si usas el paquete vía `path`, asegúrate de incluir el autoload PSR-4 correspondiente.

## Instalación

1. Publica el archivo de configuración opcionalmente:

```bash
php artisan vendor:publish --tag=rifa-api-client-config
```

2. Configura las variables en `.env`:

```
RIFA_API_BASE_URL=https://backend.example.com/api/rifas
RIFA_API_KEY=PUBLIC_KEY
RIFA_API_SECRET=SECRET_KEY
RIFA_API_SIGNATURE_TTL=60
```

## Uso (una vez instalado)

Inyección por constructor:

```php
use App\Http\Controllers\Controller;
use Rifa\ApiClient\RifaApiClient;

class RifaFrontController extends Controller
{
    public function index(RifaApiClient $client)
    {
        $rifas = $client->list(['per_page' => 12]);

        return view('rifas.index', compact('rifas'));
    }
}
```

O mediante la fachada:

```php
use Rifa\ApiClient\Facades\RifaApi;

$summary = RifaApi::summary(5);
```

El cliente también expone `availability($rifaId, $filters)`, `verifyNumber($rifaId, $number)` y métodos `get/post/request` genéricos para otros endpoints.

## Configuración avanzada

- `rifa-api-client.http.timeout`: tiempo máximo de respuesta.
- `rifa-api-client.http.connect_timeout`: tiempo para conectar.
- `rifa-api-client.http.retry.times/sleep`: reintentos automáticos.
- `rifa-api-client.default_headers`: encabezados adicionales por defecto.

Cualquier cambio en la configuración publishable se realiza editando `config/rifa-api-client.php` después de ejecutado el publish.
