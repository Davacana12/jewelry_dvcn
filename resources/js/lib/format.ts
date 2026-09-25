const money = new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
});

const date = new Intl.DateTimeFormat('es-MX', { dateStyle: 'medium' });

const dateTime = new Intl.DateTimeFormat('es-MX', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

export function formatMoney(amount: number | string): string {
    return money.format(Number(amount));
}

/**
 * Format a calendar date (e.g. a deadline) without shifting it across
 * time zones, since it has no meaningful time of day.
 */
export function formatDate(value: string): string {
    const [year, month, day] = value.slice(0, 10).split('-').map(Number);

    return date.format(new Date(year, month - 1, day));
}

export function formatDateTime(value: string): string {
    return dateTime.format(new Date(value));
}
