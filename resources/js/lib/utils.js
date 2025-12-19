import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs) {
    return twMerge(clsx(inputs));
}

export function urlIsActive(urlToCheck, currentUrl) {
    return toUrl(urlToCheck) === currentUrl;
}

export function toUrl(href) {
    return typeof href === 'string' ? href : href?.url;
}


