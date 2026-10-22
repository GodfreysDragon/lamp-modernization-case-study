// Display metadata for the TicketStatus / TicketPriority enums.
// The values must match app/Enums/*.php.

export const statuses = {
    open: { label: 'Open', classes: 'bg-rose-100 text-rose-800 ring-rose-200' },
    pending: { label: 'Pending', classes: 'bg-amber-100 text-amber-800 ring-amber-200' },
    closed: { label: 'Closed', classes: 'bg-emerald-100 text-emerald-800 ring-emerald-200' },
};

export const priorities = {
    low: { label: 'Low', classes: 'text-slate-500' },
    normal: { label: 'Normal', classes: 'text-slate-700' },
    high: { label: 'High', classes: 'text-orange-600 font-medium' },
    urgent: { label: 'Urgent', classes: 'text-rose-600 font-semibold' },
};

export function formatDateTime(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}
