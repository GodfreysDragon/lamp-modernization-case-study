import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import DueDate from './DueDate.vue';
import StatusBadge from './StatusBadge.vue';

describe('StatusBadge', () => {
    it.each([
        ['open', 'Open', 'bg-rose-100'],
        ['pending', 'Pending', 'bg-amber-100'],
        ['closed', 'Closed', 'bg-emerald-100'],
    ])('renders %s', (status, label, colour) => {
        const wrapper = mount(StatusBadge, { props: { status } });
        expect(wrapper.text()).toBe(label);
        expect(wrapper.classes()).toContain(colour);
    });

    it('falls back gracefully for an unknown status', () => {
        const wrapper = mount(StatusBadge, { props: { status: 'archived' } });
        expect(wrapper.text()).toBe('archived');
    });
});

describe('DueDate', () => {
    it('shows a dash when there is no due date', () => {
        const wrapper = mount(DueDate, { props: { dueAt: null } });
        expect(wrapper.text()).toBe('—');
    });

    it('shows the overdue flag only when the server says so', () => {
        const due = '2020-01-01T00:00:00Z';
        expect(mount(DueDate, { props: { dueAt: due, isOverdue: true } }).text()).toContain('Overdue');
        expect(mount(DueDate, { props: { dueAt: due, isOverdue: false } }).text()).not.toContain('Overdue');
    });
});
