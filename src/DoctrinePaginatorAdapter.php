<?php

declare(strict_types=1);

namespace VictorCodigo\DoctrinePaginatorAdapter;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use VictorCodigo\DoctrinePaginatorAdapter\Exception\PaginatorPageException;

/**
 * @template TKey of array-key
 * @template TResult of mixed
 *
 * @phpstan-implements PaginatorInterface<TKey, TResult>
 */
class DoctrinePaginatorAdapter implements PaginatorInterface
{
    public const MAX_RESULT_DEFAULT = 100;

    /**
     * @var Paginator<TResult>
     */
    private Paginator $paginator;

    /**
     * @param Paginator<TResult>|null $paginator
     */
    public function __construct(?Paginator $paginator = null)
    {
        if (null === $paginator) {
            return;
        }

        $this->paginator = $paginator;
    }

    /**
     * @param Query<TKey, TResult>|QueryBuilder $query
     *
     * @return DoctrinePaginatorAdapter<TKey, TResult>
     */
    #[\Override]
    public function createPaginator(Query|QueryBuilder $query): self
    {
        /** @var Paginator<TResult> $paginator */
        $paginator = new Paginator($query);
        /** @var DoctrinePaginatorAdapter<TKey, TResult> $doctrinePaginatorAdapter */
        $doctrinePaginatorAdapter = new self($paginator);

        return $doctrinePaginatorAdapter;
    }

    /**
     * @return DoctrinePaginatorAdapter<TKey, TResult>
     *
     * @throws \InvalidArgumentException
     */
    private function setPageItems(int $pageItems = self::MAX_RESULT_DEFAULT): self
    {
        if ($pageItems <= 0) {
            throw new \InvalidArgumentException('Page items must be bigger than 0');
        }

        $this->paginator
            ->getQuery()
            ->setMaxResults($pageItems);

        return $this;
    }

    #[\Override]
    public function getPageItems(): ?int
    {
        $this->validateQuery();

        return $this->paginator
            ->getQuery()
            ->getMaxResults();
    }

    /**
     * @return DoctrinePaginatorAdapter<TKey, TResult>
     */
    private function setPage(int $page): self
    {
        if ($page <= 0) {
            throw PaginatorPageException::fromMessage('Wrong page. Page must be bigger than 1');
        }

        $pagesTotal = $this->getPagesTotal();

        if ($page > $pagesTotal) {
            $page = $pagesTotal;
        }

        $this->paginator
            ->getQuery()
            ->setFirstResult($this->getPageOffset($page));

        return $this;
    }

    /**
     * @return DoctrinePaginatorAdapter<TKey, TResult>
     */
    #[\Override]
    public function setPagination(int $page = 1, int $pageItems = self::MAX_RESULT_DEFAULT): self
    {
        $this->validateQuery();

        $this
            ->setPageItems($pageItems)
            ->setPage($page);

        return $this;
    }

    /**
     * @return \Generator<\Traversable<mixed>>
     *
     * @throws \InvalidArgumentException
     */
    #[\Override]
    public function getPagesRange(int $pageIni, int $pageEnd, int $pageItems): \Generator
    {
        if ($pageIni <= 0) {
            throw new \InvalidArgumentException('PageIni, must by bigger than 0');
        }

        $hasNext = true;
        while ($hasNext && $pageIni <= $pageEnd) {
            $this->setPagination($pageIni, $pageItems);

            yield $this->getIterator();

            ++$pageIni;
            $hasNext = $this->hasNext();
        }
    }

    /**
     * @return \Generator<\Traversable<mixed>>
     *
     * @throws \InvalidArgumentException
     */
    #[\Override]
    public function getAllPages(int $pageItems): \Generator
    {
        $this->setPageItems($pageItems);

        return $this->getPagesRange(1, $this->getPagesTotal(), $pageItems);
    }

    #[\Override]
    public function getPageCurrent(): int
    {
        $this->validateQuery();

        $pageItems = $this->getPageItems();
        $firstResult = $this->paginator
            ->getQuery()
            ->getFirstResult();

        return $firstResult < $pageItems
                ? 1
                : (int) floor($firstResult / $pageItems) + 1;
    }

    #[\Override]
    public function getPagesTotal(): int
    {
        $this->validateQuery();

        $itemsTotal = $this->getItemsTotal();

        return 0 !== $itemsTotal
            ? (int) ceil($itemsTotal / $this->getPageItems())
            : 1;
    }

    #[\Override]
    public function hasNext(): bool
    {
        $this->validateQuery();

        return $this->getPageCurrent() < $this->getPagesTotal();
    }

    #[\Override]
    public function hasPrevious(): bool
    {
        $this->validateQuery();

        return $this->getPageCurrent() > 1;
    }

    #[\Override]
    public function getPageNextNumber(): ?int
    {
        $this->validateQuery();

        if (!$this->hasNext()) {
            return null;
        }

        return $this->getPageCurrent() + 1;
    }

    #[\Override]
    public function getPagePreviousNumber(): ?int
    {
        $this->validateQuery();

        if (!$this->hasPrevious()) {
            return null;
        }

        return $this->getPageCurrent() - 1;
    }

    /**
     * @return int<0, max>
     */
    #[\Override]
    public function getItemsTotal(): int
    {
        $this->validateQuery();

        return count($this->paginator);
    }

    /**
     * @return int<0, max>
     */
    #[\Override]
    public function count(): int
    {
        return $this->getItemsTotal();
    }

    /**
     * @return \Traversable<TKey, TResult>
     */
    #[\Override]
    public function getIterator(): \Traversable
    {
        $this->validateQuery();

        return $this->paginator->getIterator();
    }

    private function getPageOffset(int $page): int
    {
        $this->validateQuery();

        return ($page - 1) * $this->getPageItems();
    }

    private function validateQuery(): void
    {
        if (!isset($this->paginator)) {
            throw new \LogicException('Query not set. Use method setQuery, to set the query first');
        }
    }
}
