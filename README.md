# EchoBuzzer

Application de buzzer multi-joueur en temps réel (Laravel + Reverb + Inertia + Vue 3 + Tailwind). Tout est prêt pour un démarrage via Docker.

## Démarrage rapide

1. Lancer l'ensemble des services

```bash
docker compose up --build
```

2. Accès

- Clients: `http://<MAC_IP>:8086/` (modifiable via `CLIENTS_HOST_PORT`)
- Admin: `http://<MAC_IP>:8087/admin` (modifiable via `ADMIN_HOST_PORT`)
- WebSocket (Reverb): `ws://<MAC_IP>:8085` (modifiable via `REVERB_HOST_PORT`)

Pour un test local:
- Clients: `http://localhost:8086/`
- Admin: `http://localhost:8087/admin`

## Trouver l'IP du Mac (LAN)

Wi‑Fi:

```bash
ipconfig getifaddr en0
```

Alternative:

```bash
ifconfig | grep -A2 "en0" | grep "inet "
```

## Notes importantes

- Si un iPhone ne se connecte pas:
  - Vérifier que macOS Firewall autorise Docker.
- Vérifier que les ports `8085`, `8086`, `8087` sont accessibles sur le LAN.
- Le WebSocket doit être joignable sur `REVERB_HOST_PORT`.
- L'admin a besoin du token `ADMIN_TOKEN` (défaut: `change-me`).

## Fonctionnement

- `/` pour les joueurs, `/admin` pour l'admin.
- Un seul appui par client et par manche.
- Reset admin via `POST /api/buzz/reset` avec en-tête `X-Admin-Token`.
- L'ordre est déterminé côté serveur via timestamp en ms.

## Services Docker

- `app`: PHP-FPM + Laravel
- `nginx`: sert l'app sur `CLIENTS_HOST_PORT` (clients) et `ADMIN_HOST_PORT` (admin)
- `redis`: stockage des rounds
- `reverb`: serveur WebSocket sur `REVERB_HOST_PORT`
- `node`: build des assets Vite

## Variables d'environnement (.env)

Les valeurs par défaut sont dans `.env.example`. Au premier démarrage, Laravel copie le fichier et génère l'`APP_KEY`.

Variables clés:
- `ADMIN_TOKEN`
- `CORS_ALLOWED_ORIGINS`
- `REVERB_ALLOWED_ORIGINS`
- `VITE_REVERB_HOST` (laisser vide pour utiliser `window.location.hostname`)
- `VITE_REVERB_PORT=8085` (doit correspondre au port hôte Reverb)
- `REVERB_HOST_PORT=8085` (port hôte Docker pour éviter un conflit local)
- `CLIENTS_HOST_PORT=8086` (port hôte Docker pour les clients)
- `ADMIN_HOST_PORT=8087` (port hôte Docker pour l'admin)

## Mode "Event" vs Mode "Prod"

### Mode "Event" (IP + HTTP, usage ponctuel)
Recommandé pour une fête sur IP publique/privée sans HTTPS. Le WebSocket est parfois strict sur l'Origin, donc on laisse Reverb en `*` pour éviter les refus.

```
REVERB_ALLOWED_ORIGINS=*
CORS_ALLOWED_ORIGINS=http://<IP>:8086,http://<IP>:8087
```

### Mode "Prod" (domaine + HTTPS, usage public)
Recommandé pour un usage public durable. Active un reverse proxy HTTPS (Caddy/Nginx) et restreins strictement les origins.

```
APP_URL=https://example.com
REVERB_SCHEME=https
VITE_REVERB_HOST=example.com
VITE_REVERB_PORT=443
REVERB_HOST_PORT=443
REVERB_ALLOWED_ORIGINS=https://example.com
CORS_ALLOWED_ORIGINS=https://example.com
```

Notes:
- Le WebSocket (Reverb) n'utilise pas CORS, il se base uniquement sur `REVERB_ALLOWED_ORIGINS`.
- En mode IP + HTTP, il est fréquent que l'Origin envoyé par le navigateur ne corresponde pas exactement à ce qui est attendu.

## Checklist de test

1. Ouvrir 2 onglets ou 2 téléphones sur `/` et saisir des noms différents.
2. Appuyer sur le buzzer:
   - l'admin voit l'ordre en temps réel
   - le premier est mis en évidence
   - chaque client ne peut buzzer qu'une fois
3. Reset depuis l'admin:
   - classement vide
   - tous les clients redeviennent appuyables
4. Rafraîchir un client après avoir buzzé:
   - le serveur refuse un 2e press (même `client_id` en localStorage)

## Structure technique

- Events broadcastés: `BuzzStateUpdated` sur le channel public `buzz` (event `.buzz.state`).
- État stocké dans Redis:
  - `buzz:round_id`
  - `buzz:round:<id>:presses` (ZSET)
  - `buzz:round:<id>:names` (HASH)
  - `buzz:round:<id>:first_ts_ms` (STRING)
