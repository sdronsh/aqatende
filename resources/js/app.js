import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

let pwaServiceWorkerRegistration = null;
let pwaRefreshingForUpdate = false;

const notifyPwaUpdateAvailable = () => {
    window.dispatchEvent(new Event('aqatende:pwa-update-available'));
};

const watchPwaServiceWorkerUpdate = (registration) => {
    if (!registration) {
        return;
    }

    pwaServiceWorkerRegistration = registration;

    if (registration.waiting && navigator.serviceWorker.controller) {
        notifyPwaUpdateAvailable();
    }

    registration.addEventListener('updatefound', () => {
        const installingWorker = registration.installing;
        if (!installingWorker) {
            return;
        }

        installingWorker.addEventListener('statechange', () => {
            if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                notifyPwaUpdateAvailable();
            }
        });
    });
};

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        const appVersion = document.querySelector('meta[name="app-version"]')?.content || '1.0.0';
        const serviceWorkerUrl = `/sw.js?v=${encodeURIComponent(appVersion)}`;

        navigator.serviceWorker.register(serviceWorkerUrl)
            .then((registration) => {
                watchPwaServiceWorkerUpdate(registration);
                return navigator.serviceWorker.ready;
            })
            .then(() => window.dispatchEvent(new Event('aqatende:pwa-ready')))
            .catch(() => {});
    });

    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (pwaRefreshingForUpdate) {
            return;
        }

        pwaRefreshingForUpdate = true;
        window.location.reload();
    });
}

let deferredPwaInstallPrompt = null;
const pwaInstallDismissedKey = 'aqatende.pwaInstall.v3.dismissed';

const isPwaStandalone = () => {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
};

const isMobileDevice = () => {
    return window.matchMedia('(max-width: 768px)').matches || /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
};

const isIosDevice = () => {
    return /iPhone|iPad|iPod/i.test(navigator.userAgent);
};

const hasPwaInstallPrompt = () => {
    return Boolean(document.querySelector('[data-pwa-install-prompt]'));
};

const pwaInstallDismissed = () => {
    return sessionStorage.getItem(pwaInstallDismissedKey) === '1';
};

const dismissPwaInstallPrompt = () => {
    sessionStorage.setItem(pwaInstallDismissedKey, '1');
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
    if (!hasPwaInstallPrompt() || isPwaStandalone() || !isMobileDevice() || pwaInstallDismissed()) {
        return;
    }

    event.preventDefault();
    deferredPwaInstallPrompt = event;
    showPwaInstallPrompt('native');
});

window.addEventListener('appinstalled', () => {
    deferredPwaInstallPrompt = null;
    document.querySelector('[data-pwa-install-prompt]')?.remove();
});

window.addEventListener('load', () => {
    const offlineGuard = document.querySelector('[data-pwa-offline-guard]');
    const offlineBanner = document.querySelector('[data-pwa-offline-banner]');
    const onlineBanner = document.querySelector('[data-pwa-online-banner]');
    const blockedBanner = document.querySelector('[data-pwa-action-blocked]');
    const updateBanner = document.querySelector('[data-pwa-update-banner]');
    const offlineDismissButton = document.querySelector('[data-pwa-offline-dismiss]');
    const updateReloadButton = document.querySelector('[data-pwa-update-reload]');
    let offlineDismissed = false;
    let onlineBannerTimeout = null;
    let blockedBannerTimeout = null;

    const showElement = (element) => element?.classList.remove('hidden');
    const hideElement = (element) => element?.classList.add('hidden');

    const showBlockedOfflineAction = () => {
        if (!blockedBanner) {
            return;
        }

        showElement(blockedBanner);
        window.clearTimeout(blockedBannerTimeout);
        blockedBannerTimeout = window.setTimeout(() => hideElement(blockedBanner), 3500);
    };

    const updateConnectionStatus = (showOnlineToast = false) => {
        if (!offlineGuard) {
            return;
        }

        if (navigator.onLine) {
            hideElement(offlineBanner);
            hideElement(blockedBanner);

            if (showOnlineToast) {
                showElement(onlineBanner);
                window.clearTimeout(onlineBannerTimeout);
                onlineBannerTimeout = window.setTimeout(() => hideElement(onlineBanner), 3500);
            }

            return;
        }

        hideElement(onlineBanner);
        if (!offlineDismissed) {
            showElement(offlineBanner);
        }
    };

    const formNeedsConnection = (form) => {
        const method = (form.getAttribute('method') || 'GET').toUpperCase();
        const spoofedMethod = (form.querySelector('input[name="_method"]')?.value || '').toUpperCase();
        const effectiveMethod = spoofedMethod || method;

        if (form.dataset.allowOffline === '1') {
            return false;
        }

        if (form.matches('form[action$="/logout"], form[action*="/logout"]')) {
            return false;
        }

        return !['GET', 'HEAD'].includes(effectiveMethod);
    };

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

    document.addEventListener('submit', (event) => {
        const form = event.target instanceof HTMLFormElement ? event.target : null;
        if (!form || !formNeedsConnection(form) || navigator.onLine) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        showBlockedOfflineAction();
        updateConnectionStatus();
    }, true);

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

    offlineDismissButton?.addEventListener('click', () => {
        offlineDismissed = true;
        hideElement(offlineBanner);
    });

    updateReloadButton?.addEventListener('click', () => {
        const waitingWorker = pwaServiceWorkerRegistration?.waiting;
        if (waitingWorker) {
            waitingWorker.postMessage({type: 'SKIP_WAITING'});
            return;
        }

        window.location.reload();
    });

    window.addEventListener('online', () => {
        offlineDismissed = false;
        updateConnectionStatus(true);
    });

    window.addEventListener('offline', () => {
        offlineDismissed = false;
        updateConnectionStatus();
    });

    window.addEventListener('aqatende:pwa-update-available', () => {
        showElement(updateBanner);
    });

    updateConnectionStatus();
});
