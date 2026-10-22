import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import Pagination from './Pagination.vue';

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
}));

const links = [
    { url: null, label: '&laquo; Previous', active: false },
    { url: '/tickets?page=1', label: '1', active: true },
    { url: '/tickets?page=2', label: '2', active: false },
    { url: '/tickets?page=2', label: 'Next &raquo;', active: false },
];

describe('Pagination', () => {
    it('renders nothing for a single page', () => {
        const wrapper = mount(Pagination, { props: { links: links.slice(0, 3) } });
        expect(wrapper.find('nav').exists()).toBe(false);
    });

    it('turns HTML entities into plain arrows without v-html', () => {
        const wrapper = mount(Pagination, { props: { links } });
        expect(wrapper.text()).toContain('‹ Previous');
        expect(wrapper.text()).toContain('Next ›');
    });

    it('marks the current page and disables links without a url', () => {
        const wrapper = mount(Pagination, { props: { links } });
        expect(wrapper.find('[aria-current="page"]').text()).toBe('1');
        expect(wrapper.findAll('a')).toHaveLength(3);
    });
});
