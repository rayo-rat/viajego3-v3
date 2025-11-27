from flask import Flask, request, Response, redirect
import requests

app = Flask(__name__)

# TUS SERVIDORES PHP
SERVERS = [
    "http://127.0.0.1:8001",
    "http://127.0.0.1:8002",
    "http://127.0.0.1:8003"
]
current_index = 0

@app.route('/', defaults={'path': ''}, methods=['GET', 'POST', 'PUT', 'DELETE'])
@app.route('/<path:path>', methods=['GET', 'POST', 'PUT', 'DELETE'])
def proxy(path):
    global current_index
    
    # 1. REDIRECCIÓN AUTOMÁTICA
    # Si entras a la raíz, te mandamos a la página de inicio real
    if path == "":
        return redirect('/views/index.php')

    # 2. SELECCIÓN DE SERVIDOR (Round Robin)
    server_url = SERVERS[current_index]
    current_index = (current_index + 1) % len(SERVERS)
    
    # 3. URL DESTINO
    url = f"{server_url}/{path}"
    
    # Headers a ignorar
    excluded_headers = ['content-encoding', 'content-length', 'transfer-encoding', 'connection', 'host']

    try:
        # Preparamos headers
        headers = {key: value for (key, value) in request.headers if key.lower() not in excluded_headers}
        
        # 4. INYECTAR HOST REAL (Para que PHP sepa que estás en el puerto 9000)
        headers['Host'] = request.host
        headers['X-Forwarded-For'] = request.remote_addr
        
        # 5. PETICIÓN AL PHP
        resp = requests.request(
            method=request.method,
            url=url,
            headers=headers,
            data=request.get_data(),
            cookies=request.cookies,
            params=request.args,
            allow_redirects=False,
            stream=True
        )

        # 6. RESPUESTA AL CLIENTE
        response_headers = [
            (name, value) for (name, value) in resp.raw.headers.items()
            if name.lower() not in excluded_headers
        ]
        return Response(resp.content, resp.status_code, response_headers)

    except Exception as e:
        return f"Error conectando al servidor {server_url}: {str(e)}", 503

if __name__ == '__main__':
    print(f"🚀 BALANCEADOR LISTO: http://localhost:9000")
    app.run(host='0.0.0.0', port=9000, debug=True)