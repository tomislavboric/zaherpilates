const loadVimeoApi = () =>
  new Promise((resolve, reject) => {
    if (window.Vimeo && window.Vimeo.Player) {
      resolve(window.Vimeo);
      return;
    }

    const existingScript = document.querySelector('script[data-vimeo-player]');
    if (existingScript) {
      existingScript.addEventListener('load', () => resolve(window.Vimeo));
      existingScript.addEventListener('error', reject);
      return;
    }

    const script = document.createElement('script');
    script.src = 'https://player.vimeo.com/api/player.js';
    script.async = true;
    script.defer = true;
    script.setAttribute('data-vimeo-player', 'true');
    script.addEventListener('load', () => resolve(window.Vimeo));
    script.addEventListener('error', reject);
    document.head.appendChild(script);
  });

const initVimeoProgressPlayer = (container, Vimeo) => {
  const iframe = container.querySelector('iframe');
  const endpoint = container.getAttribute('data-progress-endpoint');
  const nonce = container.getAttribute('data-progress-nonce');
  const programId = parseInt(container.getAttribute('data-program-id'), 10);

  if (!iframe || !endpoint || !nonce || !programId) {
    return;
  }

  const player = new Vimeo.Player(iframe);
  let hasStarted = false;
  let lastSentAt = 0;
  let lastSentProgress = 0;

  const sendProgress = (progress, completed = false, force = false) => {
    if (!force) {
      const now = Date.now();
      const progressDelta = progress - lastSentProgress;
      if (now - lastSentAt < 15000 && progressDelta < 0.05) {
        return;
      }
    }

    lastSentAt = Date.now();
    lastSentProgress = progress;

    fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        program_id: programId,
        progress,
        completed,
      }),
    }).catch(() => {});
  };

  player.on('play', (data) => {
    hasStarted = true;
    const progress = data && typeof data.percent === 'number' ? data.percent : 0;
    sendProgress(progress, false, true);
  });

  player.on('timeupdate', (data) => {
    if (!hasStarted || !data || typeof data.percent !== 'number') {
      return;
    }
    const progress = data.percent;
    const completed = progress >= 0.98;
    sendProgress(progress, completed, completed);
  });

  player.on('ended', () => {
    sendProgress(1, true, true);
  });
};

const initVimeoProgressTracking = () => {
  const containers = Array.from(document.querySelectorAll('.video__player[data-program-id]'));
  if (!containers.length) {
    return;
  }

  loadVimeoApi()
    .then((Vimeo) => {
      containers.forEach((container) => initVimeoProgressPlayer(container, Vimeo));
    })
    .catch(() => {});
};

document.addEventListener('DOMContentLoaded', initVimeoProgressTracking);
