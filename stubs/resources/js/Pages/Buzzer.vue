<template>
  <div class="min-h-[100dvh] bg-gradient-to-br from-slate-900 via-indigo-950 to-purple-950 text-white">
    <div class="mx-auto flex min-h-[100dvh] max-w-md flex-col px-5 pb-[calc(env(safe-area-inset-bottom)+24px)] pt-[calc(env(safe-area-inset-top)+24px)]">
      <div class="rounded-[38px] border border-white/10 bg-white/5 px-6 py-7 shadow-[0_30px_80px_rgba(10,10,40,0.6)] backdrop-blur">
        <div class="flex items-center justify-between text-[10px] uppercase tracking-[0.35em] text-white/60">
          <span>Player</span>
          <span :class="connected ? 'text-emerald-300' : 'text-amber-300'">
            {{ connected ? 'Live' : 'Offline' }}
          </span>
        </div>
        <div class="mt-6 text-center text-3xl font-bold uppercase tracking-[0.18em]">
          {{ displayName }}
        </div>
        <div class="mt-6">
          <label class="text-xs uppercase tracking-[0.25em] text-white/50">Nom</label>
          <input
            ref="nameInput"
            v-model="name"
            @blur="saveName"
            @keydown.enter.prevent="handlePress"
            type="text"
            placeholder="Ton nom"
            class="mt-3 w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-4 text-lg font-semibold text-white placeholder:text-white/40 focus:outline-none focus:ring-2 focus:ring-rose-400"
            :class="nameError ? 'ring-2 ring-rose-400' : ''"
          />
        </div>
      </div>

      <div class="flex flex-1 items-center justify-center py-8">
        <button
          class="flex h-[80vw] w-[80vw] max-h-[420px] max-w-[420px] items-center justify-center rounded-full border border-white/10 text-3xl font-bold uppercase tracking-wide transition duration-150"
          :class="buttonClass"
          :disabled="isLocked"
          @click="handlePress"
          @touchstart="primeAudio"
        >
          Buzz
        </button>
      </div>

      <div class="text-center text-xs text-white/50">
        Round #{{ state.roundId }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import axios from 'axios';

const nameInput = ref(null);
const name = ref('');
const clientId = ref('');
const pressed = ref(false);
const isWinner = ref(false);
const connected = ref(false);
const nameError = ref(false);
const state = ref({ roundId: 1, presses: [], winnerClientId: null });

const audioCtx = ref(null);
const isAudioPrimed = ref(false);

const isLocked = computed(() => {
  if (pressed.value) return true;
  return state.value.presses.some((p) => p.clientId === clientId.value);
});

const displayName = computed(() => {
  const current = name.value.trim();
  return (current || 'PLAYER ONE').toUpperCase();
});

const buttonClass = computed(() => {
  if (isWinner.value) {
    return [
      'bg-[radial-gradient(circle_at_30%_30%,#fff2a1_0%,#facc15_55%,#b45309_100%)]',
      'shadow-[inset_0_10px_24px_rgba(255,255,255,0.25),0_0_35px_rgba(250,204,21,0.75),0_30px_60px_rgba(0,0,0,0.55)]',
    ].join(' ');
  }
  if (isLocked.value) {
    return [
      'bg-[radial-gradient(circle_at_30%_30%,#fff9b5_0%,#ffd54a_18%,#ff3b3b_50%,#ff1a1a_75%,#7f1d1d_100%)]',
      'shadow-[inset_0_14px_28px_rgba(255,255,255,0.35),inset_0_-12px_24px_rgba(0,0,0,0.35),0_0_40px_rgba(255,59,59,0.75),0_0_18px_rgba(255,213,74,0.65),0_25px_50px_rgba(0,0,0,0.5)]',
      'scale-[0.98]',
      'opacity-95',
    ].join(' ');
  }
  return [
    'bg-[radial-gradient(circle_at_30%_30%,#ff8a8a_0%,#ef4444_55%,#991b1b_100%)]',
    'shadow-[inset_0_10px_24px_rgba(255,255,255,0.15),inset_0_-12px_28px_rgba(0,0,0,0.5),0_30px_60px_rgba(0,0,0,0.55)]',
  ].join(' ');
});

const ensureAudio = async () => {
  const AudioContext = window.AudioContext || window.webkitAudioContext;
  if (!AudioContext) return;
  if (!audioCtx.value) audioCtx.value = new AudioContext();
  if (audioCtx.value.state === 'suspended') {
    try {
      await audioCtx.value.resume();
    } catch {
      // ignore resume errors
    }
  }
  isAudioPrimed.value = true;
};

const playTone = async (freq, duration = 0.35, type = 'sine') => {
  const AudioContext = window.AudioContext || window.webkitAudioContext;
  if (!AudioContext) return;
  await ensureAudio();
  const ctx = audioCtx.value;

  const osc = ctx.createOscillator();
  const gain = ctx.createGain();
  const now = ctx.currentTime;
  osc.type = type;
  osc.frequency.setValueAtTime(freq, now);
  gain.gain.setValueAtTime(0.0001, now);
  gain.gain.exponentialRampToValueAtTime(0.2, now + 0.02);
  gain.gain.exponentialRampToValueAtTime(0.0001, now + duration);

  osc.connect(gain);
  gain.connect(ctx.destination);
  osc.start(now);
  osc.stop(now + duration + 0.02);
};

const playBuzz = async () => {
  const AudioContext = window.AudioContext || window.webkitAudioContext;
  if (!AudioContext) return;
  await ensureAudio();
  const ctx = audioCtx.value;
  const now = ctx.currentTime;

  const osc1 = ctx.createOscillator();
  const osc2 = ctx.createOscillator();
  const gain = ctx.createGain();
  const filter = ctx.createBiquadFilter();

  osc1.type = 'sawtooth';
  osc2.type = 'square';
  osc1.frequency.setValueAtTime(140, now);
  osc2.frequency.setValueAtTime(95, now);

  filter.type = 'lowpass';
  filter.frequency.setValueAtTime(1200, now);

  gain.gain.setValueAtTime(0.0001, now);
  gain.gain.exponentialRampToValueAtTime(0.4, now + 0.03);
  gain.gain.exponentialRampToValueAtTime(0.0001, now + 1.1);

  osc1.connect(filter);
  osc2.connect(filter);
  filter.connect(gain);
  gain.connect(ctx.destination);

  osc1.start(now);
  osc2.start(now);
  osc1.stop(now + 1.2);
  osc2.stop(now + 1.2);
};

const playWinner = () => {
  playTone(520, 0.12, 'sawtooth');
  setTimeout(() => playTone(760, 0.18, 'sawtooth'), 120);
};

const saveName = () => {
  if (name.value.trim()) {
    localStorage.setItem('buzz:name', name.value.trim());
  }
};

const loadClientId = () => {
  let id = localStorage.getItem('buzz:client_id');
  if (!id) {
    id = (crypto?.randomUUID && crypto.randomUUID()) || `${Date.now()}-${Math.random()}`;
    localStorage.setItem('buzz:client_id', id);
  }
  clientId.value = id;
};

const handleState = (payload) => {
  const previousRound = state.value.roundId;
  const wasWinner = isWinner.value;
  state.value = payload;

  if (payload.roundId !== previousRound && payload.presses.length === 0) {
    pressed.value = false;
    isWinner.value = false;
  }

  isWinner.value = payload.winnerClientId === clientId.value;

  if (isWinner.value && !wasWinner) {
    playWinner();
  }
};

const fetchState = async () => {
  const { data } = await axios.get('/api/buzz/state');
  handleState(data);
};

const handlePress = async () => {
  if (isLocked.value) return;
  await ensureAudio();

  if (!name.value.trim()) {
    nameError.value = true;
    nameInput.value?.focus();
    setTimeout(() => (nameError.value = false), 600);
    return;
  }

  pressed.value = true;
  playBuzz();

  try {
    const { data } = await axios.post('/api/buzz/press', {
      client_id: clientId.value,
      name: name.value.trim(),
    });
    handleState(data);
  } catch (err) {
    setTimeout(() => {
      pressed.value = false;
    }, 800);
  }
};

onMounted(async () => {
  loadClientId();
  name.value = localStorage.getItem('buzz:name') || '';

  await fetchState();

  const channel = window.Echo?.channel('buzz');
  channel?.listen('.buzz.state', (payload) => {
    connected.value = true;
    handleState(payload);
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

  window.addEventListener(
    'touchstart',
    () => {
      ensureAudio();
    },
    { once: true }
  );

  setInterval(() => {
    if (!connected.value) {
      fetchState();
    }
  }, 3000);

  setInterval(() => {
    const conn = window.Echo?.connector?.pusher?.connection;
    if (conn) {
      connected.value = conn.state === 'connected';
    }
  }, 1000);
});

const primeAudio = async () => {
  if (!isAudioPrimed.value) {
    await ensureAudio();
    // iOS: play a very short silent tone to fully unlock audio
    playTone(1, 0.02, 'sine');
  }
};
</script>
