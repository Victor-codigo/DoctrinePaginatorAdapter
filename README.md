# DoctrinePaginatorAdapter

## Description

PHP classes for doctrine pagination

## Requeriments

### Usage
1. [PHP >= 8.1](https://www.php.net/)
2. [Doctrine >= 2.20](https://www.doctrine-project.org/)

### Development
1. [PHP >= 8.1](https://www.php.net/)
2. [Doctrine >= 2.20](https://www.doctrine-project.org/)
3. [PHPUnit 11.5](https://phpunit.de/index.html)
4. [PHPStan](https://phpstan.org/)
5. [Php-cs-fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer)


## Installation

Install with Composer:
```
    composer require victor-codigo/doctrine-paginator-adapter
```
## Classes
  **DoctrinePaginatorAdapter**: Class to create the pagination
  <br>**PaginatorInterface**: Interface for paginator adapter
  <br>**PaginatorException**: Page exceptions

## Usage

### DoctrinePaginatorAdapter methods:
| Method | Description | Params | Return |
|:-------------|:-------------|:-------------|:-----|
| **__construct** | Creates a class instance | 1. **Doctrine\ORM\Tools\Pagination\Paginator or null**: doctrine paginator | |
| **createPaginator** | Creates a new instance of DoctrinePaginatorAdapter | 1. **Doctrine\ORM\Query or Doctrine\ORM\QueryBuilder**: Query to create pagination | DoctrinePaginatorAdapter<TKey, TResult> |
| **getPageItems** | Gets the number of items by page |  | int or null |
| **setPagination** | Sets the page to return, and the number of items per page | 1. **int**: Page number. <br>2. **int**: Number of items by page | DoctrinePaginatorAdapter<TKey, TResult> |
| **getPagesRange** | Get a range of pages | 1. **int**: First page. <br>2. **int**: Last page. <br>3. **int**: Number of items per page | \Generator |
| **getAllPages** | Gets all pages | **int**: Number of items per page | \Generator |
| **getPageCurrent** | Gets current page | | int |
| **getPagesTotal** | Gets the total number of pages | | int |
| **hasNext** | Gets if there is another page after the current page | | int |
| **hasPrevious** | Gets if there is a page before the current page | | int |
| **getPageNextNumber** | Gets the number of the page after | | int or null |
| **getPagePreviousNumber** | Gets the number of the page before | | int or null |
| **getItemsTotal** <br>and <br>**count** | Gets the total number of items | | int |
| **getIterator** | Gets an iterator of the current page | | \Tarversable |
