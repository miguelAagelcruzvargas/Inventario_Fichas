# Sistema de Gestión de Leche

Un sistema web moderno y seguro para la gestión de inventarios y clientes de leche Liconsa.

## 🚀 Características

- **Seguridad avanzada**: Protección CSRF, headers de seguridad, validación de entrada
- **Diseño responsive**: Compatible con dispositivos móviles y de escritorio
- **Gestión de clientes**: Agregar, editar, validar y gestionar clientes
- **Control de inventarios**: Crear y gestionar inventarios por mes/año
- **Sistema de retiros**: Registro de retiros de productos
- **Carga masiva**: Importación de clientes via CSV
- **Validaciones inteligentes**: Validación progresiva en formularios

## 📋 Requisitos

- **PHP**: 7.4 o superior (recomendado: 8.0+)
- **MySQL**: 5.7 o superior (recomendado: 8.0+)
- **Servidor web**: Apache, Nginx, o servidor de desarrollo PHP
- **Extensiones PHP requeridas**:
  - PDO
  - PDO_MySQL
  - mbstring
  - fileinfo

## 🛠️ Instalación

### 1. Descargar el proyecto
```bash
git clone [URL_DEL_REPOSITORIO]
# o descomprimir el archivo ZIP en la carpeta del servidor web
```

### 2. Configurar la base de datos

1. **Crear la base de datos**:
   - Abre phpMyAdmin o tu cliente MySQL preferido
   - Ejecuta el contenido del archivo `DB_complete.sql`
   - Esto creará la base de datos `gestion_leche` con todas las tablas necesarias

2. **Configurar la conexión**:
   - Edita el archivo `config/conexion.php`
   - Ajusta los parámetros de conexión según tu entorno:
   ```php
   private $host = 'localhost';        // Tu servidor MySQL
   private $db_name = 'gestion_leche'; // Nombre de la base de datos
   private $username = 'root';         // Usuario MySQL
   private $password = '';             // Contraseña MySQL
   ```

### 3. Verificar instalación

1. **Ejecutar verificación**:
   - Navega a `http://localhost/gestion_leche_web/verificar_bd.php`
   - El script verificará que todo esté correctamente configurado
   - Si hay problemas, seguir las instrucciones mostradas

### 4. Configurar servidor web (opcional)

**Para desarrollo** (servidor integrado PHP):
```bash
cd /ruta/al/proyecto
php -S localhost:8080
```

**Para producción** (Apache con .htaccess):
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Headers de seguridad
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
```

## 🔑 Acceso al Sistema

### Usuario por defecto
- **Usuario**: `admin`
- **Contraseña**: `admin123`

> ⚠️ **Importante**: Cambia la contraseña por defecto después del primer acceso.

## 📱 Uso del Sistema

### Dashboard Principal
- Accede a `index.php` para ver el panel principal
- Navegación responsiva con acceso a todas las funciones

### Gestión de Clientes
- **Agregar cliente**: `clientes/agregar_cliente.php`
  - Formulario moderno con validaciones inteligentes
  - Verificación de número de tarjeta en tiempo real
  - Opción de carga masiva via CSV
- **Listar clientes**: `clientes/listar_clientes.php`
- **Editar/eliminar**: Desde la lista de clientes

### Inventarios
- **Crear inventario**: Desde el dashboard principal
- **Gestionar retiros**: Control de sobres retirados por cliente
- **Reportes**: Resúmenes y estadísticas por inventario

### Carga Masiva de Clientes
1. Descarga la plantilla CSV desde `clientes/generar_plantilla_csv.php`
2. Completa los datos siguiendo el formato
3. Sube el archivo desde `clientes/subir_csv.php`

## 🔒 Seguridad

El sistema incluye múltiples capas de seguridad:

- **Protección CSRF**: Tokens únicos por sesión
- **Headers de seguridad**: X-Frame-Options, CSP, etc.
- **Validación de entrada**: Sanitización de todos los datos
- **Prepared statements**: Protección contra SQL injection
- **Control de acceso**: Sistema de autenticación robusto
- **Logging de seguridad**: Registro de intentos de login

## 🔧 Configuración Avanzada

### Variables de Configuración
El sistema usa la tabla `configuracion` para almacenar configuraciones:
- `sobres_por_caja`: Número de sobres por caja (default: 36)
- `precio_por_sobre`: Precio por sobre (default: 13.00)
- `whatsapp_api_enabled`: Habilitar WhatsApp (default: false)

### Personalización
- **CSS**: Modifica `assets/css/styles.css`
- **JavaScript**: Modifica `assets/js/scripts.js`
- **Headers de seguridad**: Configura en `config/security_config.php`

## 🐛 Solución de Problemas

### Error de conexión a la base de datos
1. Verifica que MySQL esté ejecutándose
2. Comprueba las credenciales en `config/conexion.php`
3. Asegúrate de que la base de datos existe

### Tabla no encontrada
1. Ejecuta `DB_complete.sql` en tu base de datos
2. Ejecuta `verificar_bd.php` para confirmar

### Error de permisos
1. Verifica permisos de escritura en:
   - `logs/` (para logs de seguridad)
   - Directorio de uploads (si aplica)

### Problemas de diseño en móvil
1. Verifica que el viewport esté configurado
2. Comprueba que los estilos CSS responsive estén cargando

## 📊 Estructura del Proyecto

```
gestion_leche_web/
├── assets/
│   ├── css/
│   │   └── styles.css          # Estilos principales
│   └── js/
│       └── scripts.js          # JavaScript principal
├── clientes/
│   ├── agregar_cliente.php     # Formulario de agregar cliente
│   ├── cliente.php             # Clase Cliente
│   ├── listar_clientes.php     # Lista de clientes
│   └── [otros archivos]
├── config/
│   ├── conexion.php            # Configuración de BD
│   ├── security_config.php     # Configuración de seguridad
│   └── security_check.php      # Verificación de seguridad
├── inventarios/
│   ├── Inventario.php          # Clase Inventario
│   └── [otros archivos]
├── login/
│   └── login.php               # Sistema de autenticación
├── logs/
│   ├── failed_logins.log       # Log de intentos fallidos
│   └── successful_logins.log   # Log de accesos exitosos
├── index.php                   # Dashboard principal
├── DB_complete.sql            # Script de base de datos
└── verificar_bd.php           # Script de verificación
```

## 🌐 Configuración de ngrok (Acceso Remoto)

Para acceder al sistema desde internet usando ngrok, ejecuta:

```bash
iniciar_ngrok.bat
```

### Características:

- **Selección automática**: Te pregunta si usas XAMPP (puerto 80) o servidor PHP desarrollo (puerto 8080)
- **Configuración automática**: Se configura automáticamente según tu elección
- **URLs completas**: Muestra todas las URLs importantes del sistema
- **Apertura automática**: Abre las páginas principales en tu navegador

### Opciones:

1. **XAMPP (puerto 80)**:
   - URLs incluyen `/gestion_leche_web/` en el path
   - Ejemplo: `https://abc123.ngrok.io/gestion_leche_web/login/login.php`

2. **Servidor PHP desarrollo (puerto 8080)**:
   - URLs directas sin path adicional
   - Ejemplo: `https://abc123.ngrok.io/login/login.php`

**Nota**: Asegúrate de que ngrok esté instalado y en tu PATH antes de ejecutar el archivo.

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📝 Changelog

### v2.0.0 (Actual)
- ✅ Rediseño completo del sistema
- ✅ Seguridad mejorada con CSRF y headers seguros
- ✅ Diseño responsive moderno
- ✅ Validaciones inteligentes en formularios
- ✅ Base de datos optimizada
- ✅ Sistema de logs de seguridad

### v1.0.0
- ✅ Sistema básico funcional
- ✅ Gestión de clientes e inventarios

## 📄 Licencia

Este proyecto es de uso interno. Consulta con el administrador del sistema para más detalles sobre el uso y distribución.

## 📞 Soporte

Para soporte técnico o reportar problemas:
1. Ejecuta `verificar_bd.php` y proporciona la salida
2. Revisa los logs en la carpeta `logs/`
3. Incluye detalles del error y pasos para reproducirlo

---

**Desarrollado con ❤️ para mejorar la gestión de leche Liconsa**
