import { createApp, h } from 'vue';
import Toast from '../components/Toast.vue';

let toastContainer = null;
const toasts = [];

function createToastContainer() {
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        toastContainer.style.cssText = 'position: fixed; top: 16px; left: 50%; transform: translateX(-50%); z-index: 9999; pointer-events: none; display: flex; flex-direction: column; gap: 8px; align-items: center;';
        document.body.appendChild(toastContainer);
    }
    return toastContainer;
}

function showToast(type, title, message, duration = 3000) {
    const container = createToastContainer();

    const toastId = `toast-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    const toastDiv = document.createElement('div');
    toastDiv.id = toastId;
    toastDiv.style.cssText = 'pointer-events: auto;';
    container.appendChild(toastDiv);

    let app = null;

    const closeToast = () => {
        if (app) {
            app.unmount();
            app = null;
        }
        if (toastDiv.parentNode) {
            toastDiv.remove();
        }
        const index = toasts.indexOf(toastId);
        if (index > -1) {
            toasts.splice(index, 1);
        }
        updateToastPositions();
    };

    app = createApp(Toast, {
        type,
        title,
        message,
        duration,
        onClose: closeToast,
    });

    app.mount(toastDiv);
    toasts.push(toastId);
    updateToastPositions();

    return {
        close: () => {
            if (app) {
                app.unmount();
                app = null;
            }
            if (toastDiv.parentNode) {
                toastDiv.remove();
            }
            const index = toasts.indexOf(toastId);
            if (index > -1) {
                toasts.splice(index, 1);
            }
            updateToastPositions();
        },
    };
}

function updateToastPositions() {

}

export const toast = {
    success: (title, message, duration) => showToast('success', title, message, duration),
    error: (title, message, duration) => showToast('error', title, message, duration),
};

if (typeof window !== 'undefined') {
    window.toast = toast;
}

