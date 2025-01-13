<?php

declare(strict_types=1);

namespace VictorCodigo\DoctrinePaginatorAdapter\Tests\Unit;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use VictorCodigo\DoctrinePaginatorAdapter\DoctrinePaginatorAdapter;
use VictorCodigo\DoctrinePaginatorAdapter\Exception\PaginatorPageException;
use VictorCodigo\DoctrinePaginatorAdapter\Tests\Unit\Fixtures\QueryResult;

/**
 * @template TKey of array-key
 * @template TResult of mixed
 */
class DoctrinePaginatorAdapterTest extends TestCase
{
    private MockObject&Connection $connection;
    private MockObject&EntityManager $entityManager;
    private MockObject&AbstractPlatform $abstractPlatform;

    /**
     * @var QueryResult[]
     */
    private array $queryResult;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(Connection::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->abstractPlatform = $this->createMock(AbstractPlatform::class);
        $this->queryResult = $this->getQueryResult();

        $this->entityManager
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->connection
            ->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn($this->abstractPlatform);
    }

    /**
     * @return QueryResult[]
     */
    private function getQueryResult(): array
    {
        return [
            new QueryResult('1', 'name 1', 15),
            new QueryResult('2', 'name 2', 16),
            new QueryResult('3', 'name 3', 35),
            new QueryResult('4', 'name 4', 31),
            new QueryResult('5', 'name 5', 12),
            new QueryResult('6', 'name 6', 46),
            new QueryResult('7', 'name 7', 23),
            new QueryResult('8', 'name 8', 27),
            new QueryResult('9', 'name 9', 18),
            new QueryResult('10', 'name 10', 43),
            new QueryResult('11', 'name 11', 19),
            new QueryResult('12', 'name 12', 20),
            new QueryResult('13', 'name 13', 56),
            new QueryResult('14', 'name 14', 65),
            new QueryResult('15', 'name 15', 44),
            new QueryResult('16', 'name 16', 78),
            new QueryResult('17', 'name 17', 79),
            new QueryResult('18', 'name 18', 80),
            new QueryResult('19', 'name 19', 19),
            new QueryResult('20', 'name 20', 21),
        ];
    }

    /**
     * @param Query<TKey, TResult> $query
     * @param QueryResult[]        $queryResult
     *
     * @return MockObject&Paginator<TResult>
     */
    private function mockPaginator(Query $query, array $queryResult): MockObject&Paginator
    {
        /** @var MockObject&Paginator<TResult> $paginator */
        $paginator = $this->createMock(Paginator::class);

        $paginator
            ->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $paginator
            ->expects($this->any())
            ->method('count')
            ->willReturn(count($queryResult));

        return $paginator;
    }

    /**
     * @param Paginator<TResult> $paginator
     *
     * @return DoctrinePaginatorAdapter<TKey, TResult>
     */
    private function createObjectTest(Paginator $paginator): DoctrinePaginatorAdapter
    {
        $object = new DoctrinePaginatorAdapter($paginator);

        $objectReflection = new \ReflectionClass($object);
        $paginatorProperty = $objectReflection->getProperty('paginator');
        $paginatorProperty->setAccessible(true);
        $paginatorProperty->setValue($object, $paginator);

        return $object;
    }

    /**
     * @param QueryResult[] $queryResult
     *
     * @return MockObject&Query<TKey, TResult>
     */
    private function mockQuery(array $queryResult, EntityManager $entityManager, int $pageItems): MockObject&Query
    {
        /** @var MockObject&Query<TKey, TResult> */
        $query = $this->createMock(Query::class);

        $query
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn(new ArrayCollection());

        $query
            ->expects($this->any())
            ->method('getHints')
            ->willReturn([]);

        $query
            ->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $query
            ->expects($this->any())
            ->method('getScalarResult')
            ->willReturn($queryResult);

        $query
            ->expects($this->any())
            ->method('getMaxResults')
            ->willReturn($pageItems);

        $query
            ->expects($this->any())
            ->method('setMaxResults')
            ->with($pageItems);

        return $query;
    }

    /**
     * @return DoctrinePaginatorAdapter<TKey, TResult>
     */
    private function mockObjects(int $pageItems): DoctrinePaginatorAdapter
    {
        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);

        return $this->createObjectTest($paginator);
    }

    /**
     * @param \Traversable<mixed>       $page
     * @param \Traversable<QueryResult> $queryPageItemsExpected
     */
    private function assertPageIsOk(\Traversable $page, \Traversable $queryPageItemsExpected, int $pageItems): void
    {
        foreach ($page as $item) {
            $this->assertInstanceOf(\Traversable::class, $item);
            $this->assertEquals($item, $queryPageItemsExpected);
            $this->assertCount($pageItems, $item);
        }
    }

    #[Test]
    public function itShouldCreateAPaginator(): void
    {
        $pageItems = 5;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $object = new DoctrinePaginatorAdapter();

        $return = $object->createPaginator($query);

        $objectReflection = new \ReflectionClass($return);
        $paginatorProperty = $objectReflection->getProperty('paginator');
        $paginatorProperty->setAccessible(true);
        $paginator = $paginatorProperty->getValue($return);

        // @phpstan-ignore method.alreadyNarrowedType
        $this->assertInstanceOf(DoctrinePaginatorAdapter::class, $return);
        $this->assertNotSame($object, $return);
        $this->assertInstanceOf(Paginator::class, $paginator);
        $this->assertSame($query, $paginator->getQuery());
    }

    #[Test]
    public function itShouldGetPageItems(): void
    {
        $pageItems = 5;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $query
            ->expects($this->any())
            ->method('getMaxResults')
            ->willReturn($pageItems);

        $return = $object->getPageItems();

        $this->assertEquals($pageItems, $return);
    }

    #[Test]
    public function itShouldFailGetPageItemsQueryNotSet(): void
    {
        $pageItems = 5;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $object = new DoctrinePaginatorAdapter();

        $query
            ->expects($this->any())
            ->method('getMaxResults')
            ->willReturn($pageItems);

        $this->expectException(\LogicException::class);
        $object->getPageItems();
    }

    #[Test]
    public function itShouldSetPaginateResultPageOne(): void
    {
        $pageItems = 5;
        $page = 1;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $query
            ->expects($this->any())
            ->method('setMaxResults')
            ->with($pageItems);

        $query
            ->expects($this->any())
            ->method('setFirstResult')
            ->with(0);

        $return = $object->setPagination($page, $pageItems);

        $this->assertEquals($object, $return);
    }

    #[Test]
    public function itShouldSetPaginateResultPageTwo(): void
    {
        $pageItems = 5;
        $page = 2;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $query
            ->expects($this->any())
            ->method('setMaxResults')
            ->with($pageItems);

        $query
            ->expects($this->any())
            ->method('setFirstResult')
            ->with(5);

        $return = $object->setPagination($page, $pageItems);

        $this->assertEquals($object, $return);
    }

    #[Test]
    public function itShouldSetPaginateResultPageThree(): void
    {
        $pageItems = 5;
        $page = 3;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $query
            ->expects($this->any())
            ->method('setMaxResults')
            ->with($pageItems);

        $query
            ->expects($this->any())
            ->method('setFirstResult')
            ->with(10);

        $return = $object->setPagination($page, $pageItems);

        $this->assertEquals($object, $return);
    }

    #[Test]
    public function itShouldSetPaginateResultPageFour(): void
    {
        $pageItems = 5;
        $page = 4;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $query
            ->expects($this->any())
            ->method('setMaxResults')
            ->with($pageItems);

        $query
            ->expects($this->any())
            ->method('setFirstResult')
            ->with(15);

        $return = $object->setPagination($page, $pageItems);

        $this->assertEquals($object, $return);
    }

    #[Test]
    public function itShouldFailSettingPaginationPageItemsLowerThanOne(): void
    {
        $pageItems = 0;
        $page = 4;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $query
            ->expects($this->any())
            ->method('setMaxResults')
            ->with($pageItems);

        $this->expectException(\InvalidArgumentException::class);
        $object->setPagination($page, $pageItems);
    }

    #[Test]
    public function itShouldFailSettingPaginationPageLowerThanOne(): void
    {
        $pageItems = 5;
        $page = 0;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $query
            ->expects($this->any())
            ->method('setMaxResults')
            ->with($pageItems);

        $this->expectException(PaginatorPageException::class);
        $object->setPagination($page, $pageItems);
    }

    #[Test]
    public function itShouldSetPaginationPageGreaterThanTotalPagesToLastPage(): void
    {
        $pageItems = 5;
        $page = 5;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $query
            ->expects($this->any())
            ->method('setMaxResults')
            ->with($pageItems);

        $query
           ->expects($this->any())
           ->method('setFirstResult')
           ->with($pageItems * (4 - 1));

        $return = $object->setPagination($page, $pageItems);

        // @phpstan-ignore method.alreadyNarrowedType
        $this->assertInstanceOf(DoctrinePaginatorAdapter::class, $return);
    }

    #[Test]
    public function itShouldGetAPageRange(): void
    {
        $pageIni = 1;
        $pageEnd = 3;
        $pageItems = 5;
        $queryResult = $this->getQueryResult();
        $queryPageItemsExpected = [
            array_slice($queryResult, 0, 5),
            array_slice($queryResult, 5, 5),
            array_slice($queryResult, 10, 5),
        ];

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $paginator
            ->expects($this->exactly($pageEnd - $pageIni + 1))
            ->method('getIterator')
            ->willReturnOnConsecutiveCalls(
                new \ArrayIterator([new \ArrayIterator($queryPageItemsExpected[0])]),
                new \ArrayIterator([new \ArrayIterator($queryPageItemsExpected[1])]),
                new \ArrayIterator([new \ArrayIterator($queryPageItemsExpected[2])]),
            );

        $query
            ->expects($this->exactly($pageEnd - $pageIni + 1))
            ->method('getFirstResult')
            ->willReturnOnConsecutiveCalls(
                0,
                5,
                10
            );

        $return = $object->getPagesRange($pageIni, $pageEnd, $pageItems);
        $pages = iterator_to_array($return);

        $this->assertCount(3, $pages);

        $pageCount = 0;
        foreach ($pages as $page) {
            $this->assertPageIsOk($page, new \ArrayIterator($queryPageItemsExpected[$pageCount]), $pageItems);

            ++$pageCount;
        }
    }

    #[Test]
    public function itShouldFailPageIniIsSmallerThanZero(): void
    {
        $pageIni = 0;
        $pageEnd = 3;
        $pageItems = 1;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $paginator
            ->expects($this->never())
            ->method('getIterator');

        $this->expectException(\InvalidArgumentException::class);
        $return = $object->getPagesRange($pageIni, $pageEnd, $pageItems);

        foreach ($return as $item) {
        }
    }

    #[Test]
    public function itShouldFailPageIniIsBiggerThanPageEnd(): void
    {
        $pageIni = 4;
        $pageEnd = 3;
        $pageItems = 5;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $paginator
            ->expects($this->never())
            ->method('getIterator');

        $return = $object->getPagesRange($pageIni, $pageEnd, $pageItems);

        foreach ($return as $item) {
        }
    }

    #[Test]
    public function itShouldGetLessOfThePageRangeLimitPagesExceded(): void
    {
        $pageIni = 1;
        $pageEnd = 3;
        $pageItems = 10;
        $queryResult = $this->getQueryResult();
        $queryPageItemsExpected = [
            array_slice($queryResult, 0, 10),
            array_slice($queryResult, 10, 10),
        ];

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $paginator
            ->expects($this->exactly(2))
            ->method('getIterator')
            ->willReturnOnConsecutiveCalls(
                new \ArrayIterator([new \ArrayIterator($queryPageItemsExpected[0])]),
                new \ArrayIterator([new \ArrayIterator($queryPageItemsExpected[1])]),
            );

        $query
            ->expects($this->exactly(2))
            ->method('getFirstResult')
            ->willReturnOnConsecutiveCalls(
                0,
                10,
            );

        $return = $object->getPagesRange($pageIni, $pageEnd, $pageItems);
        $pages = iterator_to_array($return);

        $this->assertCount(2, $pages);

        $pageCount = 0;
        foreach ($pages as $page) {
            $pageItemsExpected = new \ArrayIterator($queryPageItemsExpected[$pageCount]);
            $this->assertPageIsOk($page, $pageItemsExpected, $pageItems);

            ++$pageCount;
        }
    }

    #[Test]
    public function itShouldGetOnlyOnePage(): void
    {
        $pageIni = 1;
        $pageEnd = 1;
        $pageItems = 20;
        $queryResult = $this->getQueryResult();
        $queryPageItemsExpected = $queryResult;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $paginator
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([new \ArrayIterator($queryPageItemsExpected)]));

        $query
            ->expects($this->once())
            ->method('getFirstResult')
            ->willReturn(0);

        $return = $object->getPagesRange($pageIni, $pageEnd, $pageItems);
        $pages = iterator_to_array($return);

        $this->assertCount(1, $pages);

        foreach ($pages as $item) {
            $this->assertPageIsOk($item, new \ArrayIterator($queryPageItemsExpected), $pageItems);
        }
    }

    #[Test]
    public function itShouldGetAllPagesRange(): void
    {
        $pageItems = 5;
        $queryResult = $this->getQueryResult();
        $queryPageItemsExpected = $queryResult;
        $pagesTotal = (int) ceil(count($queryResult) / $pageItems);

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $paginator
            ->expects($this->exactly($pagesTotal))
            ->method('getIterator')
            ->willReturnOnConsecutiveCalls(
                new \ArrayIterator([new \ArrayIterator(array_slice($queryPageItemsExpected, 0, 5))]),
                new \ArrayIterator([new \ArrayIterator(array_slice($queryPageItemsExpected, 5, 5))]),
                new \ArrayIterator([new \ArrayIterator(array_slice($queryPageItemsExpected, 10, 5))]),
                new \ArrayIterator([new \ArrayIterator(array_slice($queryPageItemsExpected, 15, 5))]),
            );

        $return = $object->getAllPages($pageItems);
        $pages = iterator_to_array($return);

        $this->assertCount(4, $pages);

        $pageCount = 1;
        foreach ($pages as $page) {
            $pageItemsExpected = new \ArrayIterator(array_slice($queryPageItemsExpected, ($pageCount - 1) * $pageItems, $pageItems));
            $this->assertPageIsOk($page, $pageItemsExpected, $pageItems);

            ++$pageCount;
        }
    }

    #[Test]
    public function itShouldGetAllPagesRangePageItemsGreaterThanTotalItems(): void
    {
        $pageItems = 30;
        $queryResult = $this->getQueryResult();
        $queryPageItemsExpected = $queryResult;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $paginator
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([new \ArrayIterator($queryPageItemsExpected)]));

        $return = $object->getAllPages($pageItems);
        $pages = iterator_to_array($return);

        $this->assertCount(1, $pages);

        foreach ($pages as $page) {
            $this->assertPageIsOk($page, new \ArrayIterator($queryPageItemsExpected), count($queryPageItemsExpected));
        }
    }

    #[Test]
    public function itShouldGetAllPagesRangeNoItems(): void
    {
        $pageItems = 5;
        $queryResult = [];

        $query = $this->mockQuery($queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $queryResult);
        $object = $this->createObjectTest($paginator);

        $paginator
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($queryResult));

        $return = $object->getAllPages($pageItems);
        $pages = iterator_to_array($return);

        $this->assertCount(1, $pages);

        foreach ($pages as $page) {
            $this->assertEquals(0, iterator_count($page));
        }
    }

    #[Test]
    public function itShouldFailGettingPageCurrentPageQueryNotSet(): void
    {
        $object = new DoctrinePaginatorAdapter();

        $this->expectException(\LogicException::class);
        $object->getPageCurrent();
    }

    public function itShouldGetPageCurrentPageOne(): void
    {
        $pageItems = 5;
        $page = 1;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $query
            ->expects($this->any())
            ->method('getFirstResult')
            ->willReturn(0);

        $return = $object->getPageCurrent();

        $this->assertEquals($page, $return);
    }

    #[Test]
    public function itShouldGetPageCurrentPageTwo(): void
    {
        $pageItems = 5;
        $page = 2;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $query
            ->expects($this->any())
            ->method('getFirstResult')
            ->willReturn(5);

        $return = $object->getPageCurrent();

        $this->assertEquals($page, $return);
    }

    #[Test]
    public function itShouldGetPageCurrentPageThree(): void
    {
        $pageItems = 5;
        $page = 3;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $query
            ->expects($this->any())
            ->method('getFirstResult')
            ->willReturn(10);

        $return = $object->getPageCurrent();

        $this->assertEquals($page, $return);
    }

    #[Test]
    public function itShouldGetPageCurrentPageFour(): void
    {
        $pageItems = 5;
        $page = 4;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $query
            ->expects($this->any())
            ->method('getFirstResult')
            ->willReturn(15);

        $return = $object->getPageCurrent();

        $this->assertEquals($page, $return);
    }

    #[Test]
    public function itShouldGetPagesTotal(): void
    {
        $pageItems = 5;

        $object = $this->mockObjects($pageItems);

        $return = $object->getPagesTotal();

        $this->assertEquals(4, $return);
    }

    #[Test]
    public function itShouldGetPagesTotalResultEmpty(): void
    {
        $pageItems = 5;
        $this->queryResult = [];

        $object = $this->mockObjects($pageItems);

        $return = $object->getPagesTotal();

        $this->assertEquals(1, $return);
    }

    #[Test]
    public function itShouldFailGettingPagesTotalPageQueryNotSet(): void
    {
        $object = new DoctrinePaginatorAdapter();

        $this->expectException(\LogicException::class);
        $object->getPagesTotal();
    }

    #[Test]
    public function itShouldHasNextPage(): void
    {
        $pageItems = 5;

        $object = $this->mockObjects($pageItems);

        $object->setPagination(3, $pageItems);
        $return = $object->hasNext();

        $this->assertTrue($return);
    }

    #[Test]
    public function itShouldHasNotNextPage(): void
    {
        $pageItems = 5;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);
        $query
            ->expects($this->any())
            ->method('getFirstResult')
            ->willReturn(15);

        $object->setPagination(4, $pageItems);
        $return = $object->hasNext();

        $this->assertFalse($return);
    }

    #[Test]
    public function itShouldFailHasNextPageQueryNotSet(): void
    {
        $object = new DoctrinePaginatorAdapter();

        $this->expectException(\LogicException::class);
        $object->hasNext();
    }

    #[Test]
    public function itShouldHasPrevious(): void
    {
        $pageItems = 5;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $query
            ->expects($this->any())
            ->method('getFirstResult')
            ->willReturn(5);

        $object->setPagination(2, $pageItems);
        $return = $object->hasPrevious();

        $this->assertTrue($return);
    }

    #[Test]
    public function itShouldHasNotPrevious(): void
    {
        $pageItems = 5;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $query
            ->expects($this->any())
            ->method('getFirstResult')
            ->willReturn(0);

        $object->setPagination(1, $pageItems);
        $return = $object->hasPrevious();

        $this->assertFalse($return);
    }

    #[Test]
    public function itShouldFailHasPreviousQueryNotSet(): void
    {
        $object = new DoctrinePaginatorAdapter();

        $this->expectException(\LogicException::class);
        $object->hasPrevious();
    }

    #[Test]
    public function itShouldGetPageNextNumber(): void
    {
        $pageItems = 5;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $query
            ->expects($this->any())
            ->method('getFirstResult')
            ->willReturn(0);

        $return = $object->getPageNextNumber();

        $this->assertEquals(2, $return);
    }

    #[Test]
    public function itShouldNotGetPageNextNumber(): void
    {
        $pageItems = 5;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $query
            ->expects($this->any())
            ->method('getFirstResult')
            ->willReturn(15);

        $return = $object->getPageNextNumber();

        $this->assertNull($return);
    }

    #[Test]
    public function itShouldFailGettingPageNextNumberQueryNotSet(): void
    {
        $object = new DoctrinePaginatorAdapter();

        $this->expectException(\LogicException::class);
        $object->getPageNextNumber();
    }

    #[Test]
    public function itShouldGetPagePreviousNumber(): void
    {
        $pageItems = 5;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $query
            ->expects($this->any())
            ->method('getFirstResult')
            ->willReturn(15);

        $return = $object->getPagePreviousNumber();

        $this->assertEquals(3, $return);
    }

    #[Test]
    public function itShouldNotGetPagePreviousNumber(): void
    {
        $pageItems = 5;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $query
            ->expects($this->any())
            ->method('getFirstResult')
            ->willReturn(0);

        $return = $object->getPagePreviousNumber();

        $this->assertNull($return);
    }

    #[Test]
    public function itShouldFailGettingPagePreviousNumberQueryNotSet(): void
    {
        $object = new DoctrinePaginatorAdapter();

        $this->expectException(\LogicException::class);
        $object->getPagePreviousNumber();
    }

    #[Test]
    public function itShouldGetItemsTotal(): void
    {
        $pageItems = 5;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $return = $object->getItemsTotal();

        $this->assertEquals(count($paginator), $return);
    }

    #[Test]
    public function itShouldFailGettingItemsTotalQueryNotValid(): void
    {
        $object = new DoctrinePaginatorAdapter();

        $this->expectException(\LogicException::class);
        $object->getItemsTotal();
    }

    #[Test]
    public function itShouldGetNumItems(): void
    {
        $pageItems = 5;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $return = $object->count();

        $this->assertEquals(count($paginator), $return);
    }

    #[Test]
    public function itShouldFailGettingNumItemsQueryNotValid(): void
    {
        $object = new DoctrinePaginatorAdapter();

        $this->expectException(\LogicException::class);
        $object->count();
    }

    #[Test]
    public function itShouldGetTheIterator(): void
    {
        $pageItems = 5;

        $query = $this->mockQuery($this->queryResult, $this->entityManager, $pageItems);
        $paginator = $this->mockPaginator($query, $this->queryResult);
        $object = $this->createObjectTest($paginator);

        $paginator
            ->expects($this->exactly(2))
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([]));

        $return = $object->getIterator();

        $this->assertEquals($paginator->getIterator(), $return);
    }

    #[Test]
    public function itShouldFailGettingTheIteratorQueryNotValid(): void
    {
        $object = new DoctrinePaginatorAdapter();

        $this->expectException(\LogicException::class);
        $object->getIterator();
    }
}
