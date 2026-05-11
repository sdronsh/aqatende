import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
            .then(() => navigator.serviceWorker.ready)
            .then(() => window.dispatchEvent(new Event('aqatende:pwa-ready')))
            .catch(() => {});
    });
}

let deferredPwaInstallPrompt = null;
const pwaInstallDismissedKey = 'aqatende.pwaInstall.v2.dismissedUntil';

const isPwaStandalone = () => {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
};

const isMobileDevice = () => {
    return window.matchMedia('(max-width: 768px)').matches || /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
};

const isIosDevice = () => {
    return /iPhone|iPad|iPod/i.test(navigator.userAgent);
};

const pwaInstallDismissed = () => {
    const dismissedUntil = Number(localStorage.getItem(pwaInstallDismissedKey) || 0);

    return dismissedUntil > Date.now();
};

const dismissPwaInstallPrompt = () => {
    const oneDay = 24 * 60 * 60 * 1000;
    localStorage.setItem(pwaInstallDismissedKey, String(Date.now() + oneDay));
    document.querySelector('[data-pwa-install-prompt]')?.remove();
};

const showPwaInstallPrompt = (mode = 'native') => {
    const prompt = document.querySelector('[data-pwa-install-prompt]');
    const button = document.querySelector('[data-pwa-install-button]');
    const text = document.querySelector('[data-pwa-install-text]');

    if (!prompt || !button || isPwaStandalone() || !isMobileDevice() || pwaInstallDismissed()) {
        return;
    }

    if (mode === 'preparing') {
        text.textContent = 'Preparando a instalacao para acesso rapido pela tela inicial do celular.';
        button.textContent = 'Preparando...';
        button.disabled = true;
    }

    if (mode === 'native') {
        text.textContent = 'Acesse mais rapido pela tela inicial do celular.';
        button.textContent = 'Instalar app';
        button.disabled = false;
    }

    if (mode === 'manual') {
        text.textContent = 'Se o Chrome nao mostrar o botao automatico, toque no menu do navegador e escolha Instalar app ou Adicionar a tela inicial.';
        button.textContent = 'Entendi';
        button.disabled = false;
    }

    if (mode === 'ios') {
        text.textContent = 'No iPhone, toque em Compartilhar e depois em Adicionar a Tela de Inicio.';
        button.textContent = 'Entendi';
        button.disabled = false;
    }

    prompt.classList.remove('hidden');
};

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPwaInstallPrompt = event;
    showPwaInstallPrompt('native');
});

window.addEventListener('appinstalled', () => {
    deferredPwaInstallPrompt = null;
    document.querySelector('[data-pwa-install-prompt]')?.remove();
});

window.addEventListener('load', () => {
    document.querySelectorAll('form[action$="/logout"], form[action*="/logout"]').forEach((form) => {
        form.addEventListener('submit', () => {
            if (!isPwaStandalone() || form.querySelector('[name="pwa_standalone"]')) {
                return;
            }

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'pwa_standalone';
            input.value = '1';
            form.appendChild(input);
        });
    });

    document.querySelectorAll('[data-pwa-install-dismiss]').forEach((button) => {
        button.addEventListener('click', dismissPwaInstallPrompt);
    });

    document.querySelector('[data-pwa-install-button]')?.addEventListener('click', async () => {
        if (deferredPwaInstallPrompt) {
            deferredPwaInstallPrompt.prompt();
            await deferredPwaInstallPrompt.userChoice.catch(() => null);
            deferredPwaInstallPrompt = null;
            document.querySelector('[data-pwa-install-prompt]')?.remove();

            return;
        }

        if (isIosDevice() || !deferredPwaInstallPrompt) {
            dismissPwaInstallPrompt();
        }
    });

    if (isIosDevice()) {
        showPwaInstallPrompt('ios');
    } else {
        showPwaInstallPrompt(deferredPwaInstallPrompt ? 'native' : 'preparing');
        window.setTimeout(() => {
            if (!deferredPwaInstallPrompt) {
                showPwaInstallPrompt('manual');
            }
        }, 2500);
    }
});

window.addEventListener('aqatende:pwa-ready', () => {
    if (!isIosDevice() && !deferredPwaInstallPrompt) {
        showPwaInstallPrompt('preparing');
    }
});
