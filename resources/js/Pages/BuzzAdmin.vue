<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-950 via-indigo-950 to-purple-950 text-white">
    <div class="mx-auto flex min-h-screen max-w-sm flex-col px-4 py-6">
      <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
        <div class="flex items-center justify-between">
          <div class="text-lg font-semibold">BuzzAdmin</div>
          <div class="text-xs" :class="connected ? 'text-emerald-300' : 'text-amber-300'">
            {{ connected ? 'Live' : 'Offline' }}
          </div>
        </div>

        <div class="mt-4">
          <label class="text-xs uppercase tracking-[0.2em] text-white/50">Admin Token</label>
          <div class="mt-2 flex gap-2">
            <input
              v-model="adminToken"
              type="password"
              placeholder="Token"
              class="w-full rounded-2xl border border-white/10 bg-white/10 px-3 py-2 text-sm text-white placeholder:text-white/40 focus:outline-none focus:ring-2 focus:ring-emerald-400"
            />
            <button
              class="rounded-2xl bg-emerald-500/80 px-3 py-2 text-xs font-semibold text-white"
              @click="saveToken"
            >
              Save
            </button>
          </div>
          <div class="mt-2 text-[10px] text-white/50">
            Token par défaut: <span class="font-semibold text-white/70">20140217</span>
          </div>
        </div>
      </div>

      <div class="mt-4 flex-1 overflow-hidden rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
        <div class="text-xs uppercase tracking-[0.25em] text-white/60">Classement</div>

        <div v-if="state.presses.length === 0" class="mt-6 text-center text-sm text-white/50">
          En attente...
        </div>

        <div v-else class="mt-4 space-y-2">
          <div
            v-for="press in state.presses"
            :key="press.clientId"
            class="flex items-center justify-between rounded-2xl border border-white/10 px-4 py-3"
            :class="press.rank === 1 ? 'bg-amber-400/20 ring-2 ring-amber-300/50' : 'bg-white/5'"
          >
            <div class="flex items-center gap-3">
              <div class="text-lg font-semibold">#{{ press.rank }}</div>
              <div>
                <div class="text-sm font-semibold">{{ press.name }}</div>
                <div v-if="press.rank === 1" class="text-[10px] uppercase tracking-[0.3em] text-amber-200">Winner</div>
              </div>
            </div>
            <div class="text-sm text-white/70">{{ formatDelta(press.deltaMs) }}</div>
          </div>
        </div>
      </div>

      <div class="mt-4 grid grid-cols-1 gap-3">
        <button
          class="w-full rounded-2xl bg-rose-500 py-4 text-lg font-semibold shadow-lg shadow-rose-500/30"
          @click="resetRound"
        >
          Next Round
        </button>
        <button
          class="w-full rounded-2xl border border-white/15 bg-white/10 py-3 text-sm font-semibold text-white/90"
          @click="hardResetRound"
        >
          Restart (Round 1)
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import axios from 'axios';

const adminToken = ref('');
const connected = ref(false);
const state = ref({ roundId: 1, presses: [], winnerClientId: null });
const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

const saveToken = () => {
  if (adminToken.value.trim()) {
    localStorage.setItem('buzz:admin_token', adminToken.value.trim());
  }
};

const formatDelta = (deltaMs) => {
  const seconds = deltaMs / 1000;
  const sign = deltaMs === 0 ? '' : '+';
  return `${sign}${seconds.toFixed(3)}s`;
};

const fetchState = async () => {
  const { data } = await axios.get('/api/buzz/state');
  state.value = data;
  if (isIOS) connected.value = true;
};

const resetRound = async () => {
  try {
    await axios.post('/api/buzz/reset', {}, {
      headers: {
        'X-Admin-Token': adminToken.value.trim(),
      },
    });
  } catch (err) {
    alert('Token admin invalide ou absent.');
  }
};

const hardResetRound = async () => {
  try {
    await axios.post('/api/buzz/hard-reset', {}, {
      headers: {
        'X-Admin-Token': adminToken.value.trim(),
      },
    });
  } catch (err) {
    alert('Token admin invalide ou absent.');
  }
};

onMounted(async () => {
  adminToken.value = localStorage.getItem('buzz:admin_token') || '';
  await fetchState();

  if (!isIOS) {
    const channel = window.Echo?.channel('buzz');
    channel?.listen('.buzz.state', (payload) => {
      connected.value = true;
      state.value = payload;
    });
    channel?.subscribed(() => {
      connected.value = true;
      fetchState();
    });

    const connection = window.Echo?.connector?.pusher?.connection;
    if (connection) {
      connected.value = connection.state === 'connected';
      connection.bind('state_change', (states) => {
        connected.value = states.current === 'connected';
        if (connected.value) {
          fetchState();
        }
      });
      connection.bind('connected', () => {
        connected.value = true;
        fetchState();
      });
      connection.bind('disconnected', () => (connected.value = false));
      connection.bind('error', () => (connected.value = false));
    }
  }

  setInterval(() => {
    if (!connected.value) {
      fetchState();
    }
  }, 3000);

  if (!isIOS) {
    setInterval(() => {
      const conn = window.Echo?.connector?.pusher?.connection;
      if (conn) {
        connected.value = conn.state === 'connected';
      }
    }, 1000);
  } else {
    setInterval(() => {
      fetchState();
    }, 1000);
  }
});
</script>
