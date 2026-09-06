import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

import { Pagination } from '@/components/ui/pagination';

describe('Pagination', () => {
    it('renders nothing when there is only one page', () => {
        const { container } = render(
            <Pagination
                currentPage={1}
                lastPage={1}
                onPageChange={vi.fn()}
                previousLabel="Previous"
                nextLabel="Next"
                pageIndicatorLabel="Page 1 of 1"
            />,
        );

        expect(container).toBeEmptyDOMElement();
    });

    it('disables "previous" on the first page and "next" on the last page', () => {
        const { rerender } = render(
            <Pagination
                currentPage={1}
                lastPage={3}
                onPageChange={vi.fn()}
                previousLabel="Previous"
                nextLabel="Next"
                pageIndicatorLabel="Page 1 of 3"
            />,
        );

        expect(screen.getByRole('button', { name: 'Previous' })).toBeDisabled();
        expect(screen.getByRole('button', { name: 'Next' })).toBeEnabled();

        rerender(
            <Pagination
                currentPage={3}
                lastPage={3}
                onPageChange={vi.fn()}
                previousLabel="Previous"
                nextLabel="Next"
                pageIndicatorLabel="Page 3 of 3"
            />,
        );

        expect(screen.getByRole('button', { name: 'Previous' })).toBeEnabled();
        expect(screen.getByRole('button', { name: 'Next' })).toBeDisabled();
    });

    it('calls onPageChange with the adjacent page number', async () => {
        const user = userEvent.setup();
        const onPageChange = vi.fn();

        render(
            <Pagination
                currentPage={2}
                lastPage={3}
                onPageChange={onPageChange}
                previousLabel="Previous"
                nextLabel="Next"
                pageIndicatorLabel="Page 2 of 3"
            />,
        );

        await user.click(screen.getByRole('button', { name: 'Next' }));
        expect(onPageChange).toHaveBeenCalledWith(3);

        await user.click(screen.getByRole('button', { name: 'Previous' }));
        expect(onPageChange).toHaveBeenCalledWith(1);
    });

    it('shows the given page indicator label', () => {
        render(
            <Pagination
                currentPage={2}
                lastPage={5}
                onPageChange={vi.fn()}
                previousLabel="Previous"
                nextLabel="Next"
                pageIndicatorLabel="Page 2 of 5"
            />,
        );

        expect(screen.getByText('Page 2 of 5')).toBeInTheDocument();
    });
});
