/**
 * Relay transparente para la API de Zafronix (Mundial 2026).
 *
 * Motivo: el VPS de producción (DonWeb, IP 168.181.185.203) recibe "connection
 * refused" al conectar a api.zafronix.com (74.208.142.121). El bloqueo es del
 * lado remoto / upstream. Cloudflare SÍ alcanza a Zafronix, así que este Worker
 * actúa de intermediario: el proxy PHP le pega al Worker, el Worker reenvía a
 * Zafronix con las cabeceras de auth y devuelve el JSON.
 *
 * Seguridad: solo reenvía a api.zafronix.com (host fijo). La API key viaja en las
 * cabeceras que manda el proxy PHP; este Worker NO la almacena. Sin key válida,
 * Zafronix responde 401, así que el Worker no es un proxy abierto explotable.
 *
 * Deploy: pegar este código en un Worker nuevo en dash.cloudflare.com y publicar.
 * La URL queda tipo: https://wc-relay.TU-SUBDOMINIO.workers.dev
 */
export default {
  async fetch(request) {
    const url = new URL(request.url);

    // CORS preflight (por si se llama desde el navegador)
    if (request.method === 'OPTIONS') {
      return new Response(null, {
        status: 204,
        headers: {
          'access-control-allow-origin': '*',
          'access-control-allow-headers': 'Content-Type, Authorization, X-API-Key',
          'access-control-allow-methods': 'GET, OPTIONS',
        },
      });
    }

    // Reenviar el path + query tal cual a Zafronix (host fijo, no es proxy abierto).
    const target = 'https://api.zafronix.com' + url.pathname + url.search;

    let upstream;
    try {
      upstream = await fetch(target, {
        method: 'GET',
        headers: {
          'X-API-Key': request.headers.get('X-API-Key') || '',
          'Authorization': request.headers.get('Authorization') || '',
          'Accept': 'application/json',
        },
      });
    } catch (err) {
      return new Response(
        JSON.stringify({ error: 'relay_upstream_failed', message: String(err) }),
        { status: 502, headers: { 'content-type': 'application/json; charset=utf-8' } }
      );
    }

    const body = await upstream.text();
    return new Response(body, {
      status: upstream.status,
      headers: {
        'content-type': upstream.headers.get('content-type') || 'application/json; charset=utf-8',
        'access-control-allow-origin': '*',
        'x-relay': 'zafronix-wc',
      },
    });
  },
};
