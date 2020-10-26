<?php

declare(strict_types=1);

namespace Flow\ETL\Tests\Unit\Row\Entry;

use Flow\ETL\Row\Entries;
use Flow\ETL\Row\Entry\ArrayEntry;
use Flow\ETL\Row\Entry\CollectionEntry;
use Flow\ETL\Row\Entry\IntegerEntry;
use Flow\ETL\Row\Entry\JsonEntry;
use Flow\ETL\Row\Entry\StringEntry;
use PHPUnit\Framework\TestCase;

final class CollectionEntryTest extends TestCase
{
    public function test_appends_entries() : void
    {
        $one   = new Entries(new IntegerEntry('id', 1), new StringEntry('name', 'one'));
        $two   = new Entries(new IntegerEntry('id', 2), new StringEntry('name', 'two'));
        $three = new Entries(new IntegerEntry('id', 3), new StringEntry('name', 'three'));
        $entry = new CollectionEntry('collection-entry', $one);

        $entry = $entry
            ->append($two)
            ->append($three);

        $this->assertEquals(new CollectionEntry('collection-entry', $one, $two, $three), $entry);
    }

    public function test_gets_entry_from_all_entries_when_value_is_the_same() : void
    {
        $orderIdEntry = new IntegerEntry('order-id', 1);
        $entry = new CollectionEntry(
            'items',
            new Entries($orderIdEntry, new StringEntry('item-name', 'one')),
            new Entries($orderIdEntry, new StringEntry('item-name', 'two')),
            new Entries(new IntegerEntry('order-id', 1), new StringEntry('item-name', 'three')),
        );

        $this->assertEquals($orderIdEntry, $entry->entryFromAll('order-id'));
    }

    public function test_prevents_from_getting_entry_when_value_is_different() : void
    {
        $entry = new CollectionEntry(
            'items',
            new Entries(new IntegerEntry('order-id', 1), new StringEntry('item-name', 'one')),
            new Entries(new IntegerEntry('order-id', 2), new StringEntry('item-name', 'two')),
        );

        $this->expectExceptionMessage('Entry "order-id" has different values in "items" collection entry');

        $entry->entryFromAll('order-id');
    }

    public function test_removes_entry_from_all_entries() : void
    {
        $entry = new CollectionEntry(
            'items',
            new Entries(new IntegerEntry('order-id', 1), new StringEntry('item-name', 'one')),
            new Entries(new IntegerEntry('order-id', 2), new StringEntry('item-name', 'two')),
            new Entries(new IntegerEntry('order-id', 3), new StringEntry('item-name', 'three'))
        );

        $this->assertEquals(
            new CollectionEntry(
                'items',
                new Entries(new StringEntry('item-name', 'one')),
                new Entries(new StringEntry('item-name', 'two')),
                new Entries(new StringEntry('item-name', 'three'))
            ),
            $entry->removeFromAll('order-id'),
        );
    }

    public function test_returns_array_as_value() : void
    {
        $entry = new CollectionEntry(
            'items',
            new Entries(new IntegerEntry('item-id', 1), new StringEntry('name', 'one')),
            new Entries(new IntegerEntry('item-id', 2), new StringEntry('name', 'two')),
            new Entries(new IntegerEntry('item-id', 3), new StringEntry('name', 'three'))
        );

        $this->assertEquals(
            [
                ['item-id' => 1, 'name' => 'one'],
                ['item-id' => 2, 'name' => 'two'],
                ['item-id' => 3, 'name' => 'three'],
            ],
            $entry->value()
        );
    }

    public function test_renames_entry() : void
    {
        $entry = new CollectionEntry('entry-name', new Entries(new IntegerEntry('id', 1234)));
        $newEntry = $entry->rename('new-entry-name');

        $this->assertEquals('new-entry-name', $newEntry->name());
        $this->assertEquals($entry->value(), $newEntry->value());
    }

    public function test_map() : void
    {
        $entry = new CollectionEntry('entry-name', new Entries(new IntegerEntry('id', 1234)));

        $this->assertEquals(
            $entry,
            $entry->map(function (array $entries) {
                return $entries;
            })
        );
    }

    /**
     * @dataProvider is_equal_data_provider
     */
    public function test_is_equal(bool $equals, CollectionEntry $entry, CollectionEntry $nextEntry) : void
    {
        $this->assertSame($equals, $entry->isEqual($nextEntry));
    }

    public function is_equal_data_provider() : \Generator
    {
        yield 'equal names and equal simple same integer entries' => [
            true,
            new CollectionEntry('name', new Entries(new IntegerEntry('1', 1), new IntegerEntry('2', 2), new IntegerEntry('3', 3))),
            new CollectionEntry('name', new Entries(new IntegerEntry('1', 1), new IntegerEntry('2', 2), new IntegerEntry('3', 3))),
        ];
        yield 'equal names and equal simple same integer entries with different number of entries' => [
            false,
            new CollectionEntry('name', new Entries(new IntegerEntry('1', 1), new IntegerEntry('2', 2), new IntegerEntry('3', 3))),
            new CollectionEntry('name', new Entries(new IntegerEntry('1', 1), new IntegerEntry('2', 2))),
        ];
        yield 'equal names and equal simple same integer entries with different number of entries reversed' => [
            false,
            new CollectionEntry('name', new Entries(new IntegerEntry('1', 1), new IntegerEntry('2', 2))),
            new CollectionEntry('name', new Entries(new IntegerEntry('1', 1), new IntegerEntry('2', 2), new IntegerEntry('3', 3))),
        ];
        yield 'equal names and equal simple same json entries' => [
            true,
            new CollectionEntry('name', new Entries(new JsonEntry('json', ['foo' => ['bar' => 'baz']]))),
            new CollectionEntry('name', new Entries(new JsonEntry('json', ['foo' => ['bar' => 'baz']]))),
        ];
        yield 'equal names and equal simple different json entries' => [
            false,
            new CollectionEntry('name', new Entries(new JsonEntry('json', ['foo' => ['bar' => 'baz']]))),
            new CollectionEntry('name', new Entries(new JsonEntry('json', ['bar' => ['bar' => 'baz']]))),
        ];
        yield 'equal names and equal simple same array entries' => [
            true,
            new CollectionEntry('name', new Entries(new ArrayEntry('json', ['foo' => ['bar' => 'baz']]))),
            new CollectionEntry('name', new Entries(new ArrayEntry('json', ['foo' => ['bar' => 'baz']]))),
        ];
        yield 'equal names and equal simple same collection entries' => [
            true,
            new CollectionEntry('name', new Entries(new CollectionEntry('json', new Entries(new IntegerEntry('1', 1), new IntegerEntry('2', 2), new IntegerEntry('3', 3))))),
            new CollectionEntry('name', new Entries(new CollectionEntry('json', new Entries(new IntegerEntry('1', 1), new IntegerEntry('2', 2), new IntegerEntry('3', 3))))),
        ];
        yield 'equal names and equal simple different collection entries' => [
            false,
            new CollectionEntry('name', new Entries(new CollectionEntry('json', new Entries(new IntegerEntry('5', 5), new IntegerEntry('2', 2), new IntegerEntry('3', 3))))),
            new CollectionEntry('name', new Entries(new CollectionEntry('json', new Entries(new IntegerEntry('1', 1), new IntegerEntry('2', 2), new IntegerEntry('3', 3))))),
        ];
    }
}
