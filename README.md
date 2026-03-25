# Sistema de Gestión de Suscripciones o Micro-SaaS Educativo

## Upgrade Learning Hub

Basado en un sistema de gestión de cursos o contenidos 
(proyecto ideal para practicar lógica de negocios), 
estos son los módulos que se implementarán para "probar" el ambiente:

1. **Dashboard Reactivo (Livewire 3)**
   
    En lugar de recargar la página, usa componentes de Livewire para:

   * Filtros en tiempo real: Una lista de cursos o módulos que se filtren mientras escribes (sin botones de "buscar").
   * Modales dinámicos: Crear un nuevo recurso sin salir de la vista principal.

2. **Sistema de "Progreso" (Propiedades Entrelazadas)**

   Crea un botón de "Marcar como completado" que actualice una barra de progreso automáticamente usando Wire:model y eventos de Livewire. Esto te servirá para ver lo fácil que es comunicar componentes entre sí ahora.

3. **Panel de Administración Simplificado**

   Aprovecha que Laravel 11 eliminó muchos archivos de configuración por defecto (Http/Kernel.php, por ejemplo). Intenta configurar un Middleware personalizado para separar a los "Estudiantes" de los "Instructores" directamente en el archivo bootstrap/app.php.

4. **Notificaciones en Tiempo Real**

   Usa el sistema de notificaciones de Laravel para mostrar un "Toast" (alerta pequeña) cuando un usuario se registra o completa una tarea, integrándolo con Livewire para que aparezca sin recargar.


### Estructura sugerida para el proyecto:

| **Componente** | Qué se va a implementar/probar|
|----------------|-------------------------------|
| Migrations     |Las nuevas migraciones compactas de Laravel 13|
| Livewire Volt  |Si quieres ir un paso más allá, prueba la sintaxis funcional (todo en un solo archivo .blade.php).|
| Tailwind CSS   |El Starter Kit ya lo trae configurado; ideal para pulir la interfaz.|


### Primeros Pasos

1. Elegimos el Starter Kit: Seleccionamos Pest para pruebas (es mucho más moderno y legible que PHPUnit) y Dark mode support para ver cómo Laravel maneja los assets.

2. Exploramos la estructura: Notaremos que la carpeta app/ está mucho más limpia que en versiones anteriores.


### Configuración de PostgreSQL en Laravel 13

Laravel 13 viene por defecto configurado para SQLite, así que lo primero es conectar nuestra base de datos Postgres.

1. Abrimos el archivo .env.
2. Buscamos las líneas de DB_ y colocamos nuestras credenciales:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=upgrade_hub
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

> Nota de instructor: Como estamos usando Herd, nos aseguramos de que el servicio de Postgres esté corriendo en nuestro computador. Si no se ha creado la base de datos upgrade_hub, créala primero en tu gestor (como pgAdmin o DBeaver).


### La Arquitectura del Proyecto

Para que este proyecto sea profesional, seguiremos esta estructura:

- **Models:** Solo para la definición de datos y relaciones.

- **Livewire Components:** Actúan como la "Capa de Presentación" reactiva. Solo manejan la interacción del usuario.

- **Services:** Aquí vivirá la lógica pesada (ej. CourseService.php). Si mañana decides cambiar Livewire por una API, la lógica no se pierde.

- **Repositories (Opcional pero recomendado):** Para consultas complejas a Postgres.

### Tu Primera Tarea - El Modelo de Datos

Vamos a construir el corazón de Upgrade Hub: la gestión de cursos. Ejecuta este comando en tu terminal para crear el modelo, la migración y el controlador de un solo golpe:

```shell
$ php artisan make:model Course -mc
```

#### La Migración (Usando tipos de datos de Postgres)
En Laravel 11/12/13, las migraciones son más limpias. Abre el archivo en ```database/migrations/xxxx_create_courses_table.php```:

```php
public function up(): void
{
    Schema::create('courses', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('description');
        $table->decimal('price', 8, 2);
        $table->enum('status', ['draft', 'published'])->default('draft');
        $table->timestamps();
    });
}
```

Ahora ejecutamos:

```shell
$ php artisan migrate
```


### Creando el primer Componente Reactivo

Ahora vamos a crear un componente de Livewire para listar estos cursos de forma dinámica.

```shell
$ php artisan make:livewire CourseList --class
```

Esto creará dos archivos:

- app/Livewire/CourseList.php (La lógica)
- resources/views/livewire/course-list.blade.php (La vista)


## Definiendo la lógica del Service Layer

### 1. Estructura de directorios

Laravel no trae la carpeta ***Services*** por defecto. Vamos a crearla para mantener el orden profesional:

- app/Services/CourseService.php

### 2. Implementando el Service Layer (Inyección de Dependencias)

Vamos a crear un servicio robusto. Nota cómo usaremos Typed Properties y el constructor para una posible inyección de un repositorio más adelante.

```php
namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Exception;

class CourseService
{
    /**
     * Crea un nuevo curso con manejo de transacciones para PostgreSQL.
     */
    public function createCourse(array $data): Course
    {
        return DB::transaction(function () use ($data) {
            try {
                return Course::create([
                    'title'       => $data['title'],
                    'description' => $data['description'],
                    'price'       => $data['price'],
                    'status'      => $data['status'] ?? 'draft',
                ]);
            } catch (Exception $e) {
                // Aquí podrías loguear el error específico de Postgres
                throw new Exception("Error al crear el curso: " . $e->getMessage());
            }
        });
    }

    public function getPublishedCourses()
    {
        return Course::where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
```

> **Tip de Instructor:** Usar ```DB::transaction``` es vital en Postgres cuando escalas a operaciones que afectan varias tablas, asegurando la integridad atómica.

### 3. Inyección en el Componente Livewire 3

Aquí es donde ocurre la magia de Laravel 13. Podemos inyectar nuestro servicio directamente en el método render o en el constructor del componente.

Abrimos el archivo ```app/Livewire/CourseList.php:```

```php
<?php

namespace App\Livewire;

use App\Services\CourseService;
use Livewire\Attributes\Layout;
use Illuminate\View\View;
use Livewire\Component;

class CourseList extends Component
{
    // Esta propiedad será reactiva gracias a wire:model en la vista
    protected string $_search = '';
    
    // Forzamos el uso del layout de invitados que no pide usuario logueado
    #[Layout('layouts.app')]
    public function render(CourseService $courseService): View
    {
        return view('livewire.course-list', [
            'courses' => $courseService->getPublishedCourses($this->_search),
        ]);
    }
}
```

**¿Por qué hacerlo así? (Ventajas de Arquitectura)**

1. **Single Responsibility (SOLID):** El componente de Livewire solo se encarga de la UI. La lógica de "cómo se crea un curso" vive en el CourseService.

2. **Reutilización:** Si mañana decides crear un comando de consola (php artisan courses:import), solo llamas al CourseService y listo.

3. **Facilidad de Testing:** Puedes testear el CourseService sin necesidad de simular una interfaz web o un navegador.

### 4. Configurar la Vista (UI)

Abrimos ```resources/views/livewire/course-list.blade.php``` y colocamos este esqueleto básico para probar que rutea bien:

```html
<div class="p-6">
    <h1 class="text-2xl font-bold mb-4 text-indigo-700">Listado de Cursos (Postgres)</h1>
    
    <div class="mb-4">
        <input 
            wire:model.live="search" 
            type="text" 
            placeholder="Buscar por título..." 
            class="w-full p-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
    </div>

    <div class="space-y-2">
        @forelse($courses as $course)
            <div class="p-3 bg-gray-50 border rounded shadow-sm">
                <span class="font-bold">{{ $course->title }}</span> - ${{ $course->price }}
            </div>
        @empty
            <p class="text-gray-500 italic">No hay cursos disponibles que coincidan.</p>
        @endforelse
    </div>
</div>
```

### Paso 5: Registro de la Ruta y Prueba Final

Para que ```/cursos``` no dé Error 500, asegúrate de que el componente esté bien importado en ```routes/web.php```:

```php
use App\Livewire\CourseList;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    ...
    Route::get('cursos', CourseList::class);
});
```

## Factories y Seeders (Datos de prueba en Postgres)

No vamos a insertar datos a mano. Vamos a usar Factories para generar datos aleatorios realistas.

### 1. Definir el Factory

Abrimos ```database/factories/CourseFactory.php```. Si no existe, créalo con 

```shell
$ php artisan make:factory CourseFactory
```

y realizamos la configuración asi:

```php
public function definition(): array
{
    return [
        'title'       => fake()->sentence(3),
        'description' => fake()->paragraph(),
        'price'       => fake()->randomFloat(2, 10, 100),
        'status'      => 'published', // Los ponemos publicados para que el Service los traiga
    ];
}
```

### 2. Ejecutamos el Seeder

Abrimos ```database/seeders/DatabaseSeeder.php``` y en el método run agregamos:

```php
public function run(): void
{
    \App\Models\Course::factory(10)->create();
}
```

Preparamos nuestro modelo ```app/Models/Course.php``` para poder realizar este poblado de BBDD de esta manera:

```php
<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'description', 'price', 'status'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;
}
```

Luego, ejecutamos en la terminal:

```shell
$ php artisan db:seed
```

Con eso deberemos tener 10 nuevas filas en nuestra tabla en la BBDD

### 3: Probando la Reactividad en el Listado

Ahora que tenemos datos, ve a tu navegador en ```/cursos```. Deberíamos ver la lista.

> **El reto de ingeniería:** Prueba el buscador que pusimos con ```wire:model.live="search"```. Al escribir, Livewire enviará una petición AJAX asíncrona, el ```CourseService``` ejecutará un ```ilike``` en Postgres y la vista se actualizará sin parpadeos.

