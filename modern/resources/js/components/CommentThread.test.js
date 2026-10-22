import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import CommentThread from './CommentThread.vue';

const comment = (overrides = {}) => ({
    id: 1,
    body: 'Hello',
    is_internal: false,
    author: 'Sarah Kim',
    created_at: '2026-10-01T10:00:00Z',
    ...overrides,
});

describe('CommentThread', () => {
    it('shows an empty state', () => {
        const wrapper = mount(CommentThread, { props: { comments: [] } });
        expect(wrapper.text()).toContain('No comments yet.');
    });

    it('renders each comment with its author', () => {
        const wrapper = mount(CommentThread, {
            props: { comments: [comment(), comment({ id: 2, author: 'Mike Ortega', body: 'Second' })] },
        });
        expect(wrapper.findAll('li')).toHaveLength(2);
        expect(wrapper.text()).toContain('Mike Ortega');
    });

    it('flags internal notes', () => {
        const wrapper = mount(CommentThread, { props: { comments: [comment({ is_internal: true })] } });
        expect(wrapper.text()).toContain('Internal');
    });

    // Regression test for the stored XSS in legacy ajax/add_comment.php.
    it('escapes HTML in comment bodies instead of executing it', () => {
        const payload = '<script>alert("xss")</script><img src=x onerror=alert(1)>';
        const wrapper = mount(CommentThread, { props: { comments: [comment({ body: payload })] } });

        expect(wrapper.find('script').exists()).toBe(false);
        expect(wrapper.find('img').exists()).toBe(false);
        expect(wrapper.find('p').text()).toBe(payload);
    });
});
