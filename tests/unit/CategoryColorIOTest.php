<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Category_Color_IO (pure logic, no WordPress dependency).
 */
final class CategoryColorIOTest extends TestCase {

    // ----------------------------------------------------------------
    // validate_import()
    // ----------------------------------------------------------------

    public function test_validate_import_rejects_non_array() {
        $result = Category_Color_IO::validate_import('not-an-array');
        $this->assertFalse($result['ok']);
        $this->assertNotSame('', $result['error']);
    }

    public function test_validate_import_rejects_wrong_format() {
        $result = Category_Color_IO::validate_import(array(
            'format'     => 'some-other-plugin',
            'version'    => 1,
            'categories' => array(array('slug' => 'a')),
        ));
        $this->assertFalse($result['ok']);
    }

    public function test_validate_import_rejects_wrong_version() {
        $result = Category_Color_IO::validate_import(array(
            'format'     => 'category-color-picker-categories',
            'version'    => 999,
            'categories' => array(array('slug' => 'a')),
        ));
        $this->assertFalse($result['ok']);
    }

    public function test_validate_import_rejects_missing_categories() {
        $result = Category_Color_IO::validate_import(array(
            'format'  => 'category-color-picker-categories',
            'version' => 1,
        ));
        $this->assertFalse($result['ok']);
    }

    public function test_validate_import_rejects_empty_categories() {
        $result = Category_Color_IO::validate_import(array(
            'format'     => 'category-color-picker-categories',
            'version'    => 1,
            'categories' => array(),
        ));
        $this->assertFalse($result['ok']);
    }

    public function test_validate_import_accepts_valid_envelope() {
        $result = Category_Color_IO::validate_import(array(
            'format'     => 'category-color-picker-categories',
            'version'    => 1,
            'categories' => array(array('slug' => 'aicoding')),
        ));
        $this->assertTrue($result['ok']);
        $this->assertSame('', $result['error']);
    }

    // ----------------------------------------------------------------
    // sort_by_hierarchy()
    // ----------------------------------------------------------------

    /** Extract slugs in order for easy assertions. */
    private function slugs(array $rows) {
        return array_map(static function ($r) {
            return $r['slug'];
        }, $rows);
    }

    public function test_sort_places_parent_before_child() {
        $rows = array(
            array('slug' => 'child',  'parent_slug' => 'parent'),
            array('slug' => 'parent', 'parent_slug' => ''),
        );
        $sorted = Category_Color_IO::sort_by_hierarchy($rows);
        $this->assertSame(array('parent', 'child'), $this->slugs($sorted));
    }

    public function test_sort_handles_three_level_chain() {
        $rows = array(
            array('slug' => 'child',  'parent_slug' => 'parent'),
            array('slug' => 'parent', 'parent_slug' => 'grand'),
            array('slug' => 'grand',  'parent_slug' => ''),
        );
        $sorted = Category_Color_IO::sort_by_hierarchy($rows);
        $this->assertSame(array('grand', 'parent', 'child'), $this->slugs($sorted));
    }

    public function test_sort_keeps_orphan_with_unknown_parent() {
        $rows = array(
            array('slug' => 'orphan', 'parent_slug' => 'missing'),
            array('slug' => 'root',   'parent_slug' => ''),
        );
        $sorted = Category_Color_IO::sort_by_hierarchy($rows);
        // No row is dropped.
        $this->assertCount(2, $sorted);
        $this->assertContains('orphan', $this->slugs($sorted));
        $this->assertContains('root', $this->slugs($sorted));
    }

    public function test_sort_does_not_hang_on_cycle() {
        $rows = array(
            array('slug' => 'a', 'parent_slug' => 'b'),
            array('slug' => 'b', 'parent_slug' => 'a'),
        );
        $sorted = Category_Color_IO::sort_by_hierarchy($rows);
        // Both rows survive even though the relationship is cyclic.
        $this->assertCount(2, $sorted);
    }

    public function test_sort_preserves_flat_top_level_order() {
        $rows = array(
            array('slug' => 'a', 'parent_slug' => ''),
            array('slug' => 'b', 'parent_slug' => ''),
            array('slug' => 'c', 'parent_slug' => ''),
        );
        $sorted = Category_Color_IO::sort_by_hierarchy($rows);
        $this->assertSame(array('a', 'b', 'c'), $this->slugs($sorted));
    }

    // ----------------------------------------------------------------
    // is_valid_hex()
    // ----------------------------------------------------------------

    public function test_is_valid_hex_accepts_six_digit() {
        $this->assertTrue(Category_Color_IO::is_valid_hex('#ffffff'));
        $this->assertTrue(Category_Color_IO::is_valid_hex('#002A7B'));
    }

    public function test_is_valid_hex_accepts_three_digit() {
        $this->assertTrue(Category_Color_IO::is_valid_hex('#fff'));
    }

    public function test_is_valid_hex_rejects_missing_hash() {
        $this->assertFalse(Category_Color_IO::is_valid_hex('ffffff'));
    }

    public function test_is_valid_hex_rejects_bad_length() {
        $this->assertFalse(Category_Color_IO::is_valid_hex('#ffff'));
        $this->assertFalse(Category_Color_IO::is_valid_hex('#fffff'));
    }

    public function test_is_valid_hex_rejects_non_hex_chars() {
        $this->assertFalse(Category_Color_IO::is_valid_hex('#gggggg'));
        $this->assertFalse(Category_Color_IO::is_valid_hex('#12345g'));
    }

    public function test_is_valid_hex_rejects_empty() {
        $this->assertFalse(Category_Color_IO::is_valid_hex(''));
    }

    // ----------------------------------------------------------------
    // normalize_row()
    // ----------------------------------------------------------------

    public function test_normalize_fills_defaults_for_missing_fields() {
        $row = Category_Color_IO::normalize_row(array('slug' => 'x'));
        $this->assertSame('x', $row['slug']);
        $this->assertSame('', $row['name']);
        $this->assertSame('', $row['parent_slug']);
        $this->assertSame('', $row['description']);
        $this->assertSame('', $row['color']);
        $this->assertFalse($row['noindex']);
    }

    public function test_normalize_coerces_noindex_truthy_values() {
        $this->assertTrue(Category_Color_IO::normalize_row(array('noindex' => true))['noindex']);
        $this->assertTrue(Category_Color_IO::normalize_row(array('noindex' => 1))['noindex']);
        $this->assertTrue(Category_Color_IO::normalize_row(array('noindex' => '1'))['noindex']);
    }

    public function test_normalize_coerces_noindex_falsy_values() {
        $this->assertFalse(Category_Color_IO::normalize_row(array('noindex' => false))['noindex']);
        $this->assertFalse(Category_Color_IO::normalize_row(array('noindex' => 0))['noindex']);
        $this->assertFalse(Category_Color_IO::normalize_row(array('noindex' => ''))['noindex']);
        $this->assertFalse(Category_Color_IO::normalize_row(array('noindex' => '0'))['noindex']);
    }

    public function test_normalize_preserves_known_values() {
        $row = Category_Color_IO::normalize_row(array(
            'name'        => 'AIコーディング',
            'slug'        => 'aicoding',
            'parent_slug' => 'tech',
            'description' => 'desc',
            'color'       => '#d46851',
            'noindex'     => true,
        ));
        $this->assertSame('AIコーディング', $row['name']);
        $this->assertSame('aicoding', $row['slug']);
        $this->assertSame('tech', $row['parent_slug']);
        $this->assertSame('desc', $row['description']);
        $this->assertSame('#d46851', $row['color']);
        $this->assertTrue($row['noindex']);
    }
}
