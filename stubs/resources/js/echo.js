import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const host =
  import.meta.env.VITE_REVERB_HOST ||
  window.__REVERB_HOST__ ||
  window.location.hostname;
const port = Number(
  import.meta.env.VITE_REVERB_PORT ||
    window.__REVERB_PORT__ ||
    8080
);
const isSecure = window.location.protocol === 'https:';
const key =
  import.meta.env.VITE_REVERB_APP_KEY ||
  window.__REVERB_APP_KEY__ ||
  'local';

window.Echo = new Echo({
  broadcaster: 'reverb',
  key,
  wsHost: host,
  wsPort: port,
  wssPort: port,
  forceTLS: isSecure,
  enabledTransports: ['ws', 'wss'],
  disabledTransports: ['xhr_streaming', 'xhr_polling', 'sockjs'],
  disableStats: true,
});
