# ⚽ Mini-Site Mundial 2026

Widget standalone para seguir el **FIFA World Cup 2026** en tiempo real, construido como una sola página HTML + CSS + JS. Ideal para embeberlo en WordPress u otros CMS.

Desarrollado para **[Miravos País](https://miravospais.com)**.

---

## 🚀 Demo & Características

- 📅 Fixture completo de todos los partidos del Mundial 2026
- 🔴 Resultados en vivo (live) y marcadores actualizados
- 🇦🇷 **Ruta de la Albiceleste** — próximos 3 partidos de Argentina
- 🏆 Tabla de goleadores
- 🌤️ Clima del estadio por partido
- 📱 Diseño responsive (mobile-first con Tailwind CSS)
- ⚡ Fallback a datos mock cuando la API no está disponible (perfecto para desarrollo local)

---

## 🔌 API Gratuita — Zafronix World Cup API

Este proyecto consume la **[Zafronix World Cup API v1](https://api.zafronix.com)**, una API REST gratuita con datos del FIFA World Cup 2026.

### Endpoints disponibles

| Endpoint | URL | Descripción |
|---|---|---|
| Partidos | `GET /fifa/worldcup/v1/matches?year=2026` | Lista completa de partidos con resultados |
| Equipos | `GET /fifa/worldcup/v1/teams/{name}` | Información de un equipo |
| Plantel | `GET /fifa/worldcup/v1/teams/{name}/roster?year=2026` | Jugadores convocados |
| Jugador | `GET /fifa/worldcup/v1/players/{name}` | Perfil e historial de un jugador |
| Stream SSE | `GET /fifa/worldcup/v1/matches/stream` | Actualizaciones en tiempo real (Server-Sent Events) |

### Autenticación

Todas las peticiones requieren la API key en los headers:

```http
X-API-Key: TU_API_KEY
Authorization: Bearer TU_API_KEY
```

La **clave gratuita** incluida en este proyecto es de nivel free tier y suficiente para uso personal/demo:
```
zwc_free_363fea4ab6decd2bf80f624d
```

> Para proyectos en producción con alto tráfico, podés registrar tu propia key en [zafronix.com](https://zafronix.com).

---

## 🗂️ Estructura del proyecto

```
mini-site-w2026/
├── index.html          # App completa (HTML + CSS inline + JS)
├── api-proxy.php       # Proxy PHP para el servidor (oculta la API key del frontend)
├── wc2026/
│   ├── index.html      # Copia lista para subir por FTP
│   └── api-proxy.php   # Proxy para producción
├── src/
│   └── input.css       # Tailwind entry point (para build local)
├── tailwind.config.js
└── package.json
```

---

## ⚙️ Cómo funciona el proxy PHP

El archivo `api-proxy.php` actúa como intermediario entre el frontend y la API de Zafronix. Esto permite:

1. **Ocultar la API key** — nunca queda expuesta en el código del navegador.
2. **Caché del lado del servidor** — reduce llamadas a la API:
   - Partidos: caché de 5 minutos
   - Equipos/planteles/jugadores: caché de 24 horas
3. **CORS resuelto** — el proxy agrega los headers necesarios.

```
Navegador → api-proxy.php → api.zafronix.com
                ↕
           cache/*.json
```

El frontend llama simplemente:
```javascript
fetch('api-proxy.php?endpoint=matches')
fetch('api-proxy.php?endpoint=team&name=Argentina')
fetch('api-proxy.php?endpoint=roster&name=Argentina')
fetch('api-proxy.php?endpoint=player&name=Messi')
```

---

## 🛠️ Desarrollo local

```bash
npm install
npm run dev
# → http://localhost:5173
```

> En local, el proxy PHP no está disponible. La app carga automáticamente datos mock (27 partidos hardcodeados) para que puedas trabajar sin servidor.

---

## 🚀 Deploy en WordPress

1. Copiar `wc2026/index.html` y `wc2026/api-proxy.php` al servidor vía FTP.
2. El proxy requiere PHP con extensión `curl` habilitada.
3. La carpeta `cache/` se crea automáticamente con permisos `755`.

---

## 🏗️ Stack tecnológico

- **HTML5 / CSS3 / JavaScript ES2020** — sin frameworks
- **[Tailwind CSS](https://tailwindcss.com)** via CDN (JIT con valores arbitrarios)
- **[Vite](https://vitejs.dev)** — dev server local
- **PHP 7.4+** — proxy del lado del servidor

---

## 📄 Licencia

MIT — libre para usar, modificar y distribuir.
