<?php

declare(strict_types=1);

/**
 * Contains the LinkTypeTest class.
 *
 * @copyright   Copyright (c) 2022 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2022-02-11
 *
 */

namespace Vanilo\Links\Tests\Unit;

use Illuminate\Support\Str;
use Vanilo\Links\Models\LinkType;
use Vanilo\Links\Tests\TestCase;

class LinkTypeTest extends TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $name = Str::random();
        $type = LinkType::create(['name' => $name]);

        $this->assertInstanceOf(LinkType::class, $type);
        $this->assertEquals($name, $type->name);
    }

    /** @test */
    public function the_slug_gets_autogenerated()
    {
        $variant = LinkType::create(['name' => 'Product Variant']);

        $this->assertEquals('product-variant', $variant->slug);
    }

    /** @test */
    public function the_name_must_be_unique()
    {
        LinkType::create(['name' => 'Upsell']);
        $this->expectExceptionMessageMatches('/UNIQUE constraint failed/');
        LinkType::create(['name' => 'Upsell']);
    }

    /** @test */
    public function the_autogenerated_slug_is_unique()
    {
        $xsell1 = LinkType::create(['name' => 'Cross Sell']);
        $xsell2 = LinkType::create(['name' => 'Cross sell']);

        $this->assertEquals('cross-sell', $xsell1->slug);
        $this->assertEquals('cross-sell-2', $xsell2->slug);
    }

    /** @test */
    public function is_active_attribute_is_a_boolean()
    {
        $inactive = LinkType::create(['name' => Str::random(), 'is_active' => 0]);
        $active = LinkType::create(['name' => Str::random(), 'is_active' => 1]);

        $this->assertFalse($inactive->is_active);
        $this->assertTrue($active->is_active);
    }

    /** @test */
    public function it_is_active_by_default()
    {
        $this->assertTrue((LinkType::create(['name' => 'Oh I am active']))->fresh()->is_active);
    }

    /** @test */
    public function it_can_be_queried_by_slug()
    {
        LinkType::create(['name' => 'X X X', 'slug' => 'xxx']);
        LinkType::create(['name' => 'X O X', 'slug' => 'xox']);

        $result = LinkType::bySlug('xox')->get();

        $this->assertCount(1, $result);
        $this->assertEquals('X O X', $result->first()->name);
        $this->assertEquals('xox', $result->first()->slug);
    }

    /** @test */
    public function it_can_be_found_by_slug_using_the_static_method()
    {
        LinkType::create(['name' => 'Kai', 'slug' => 'kai']);
        LinkType::create(['name' => 'Jay', 'slug' => 'jay']);
        LinkType::create(['name' => 'Cole', 'slug' => 'cole']);

        $cole = LinkType::findBySlug('cole');

        $this->assertInstanceOf(LinkType::class, $cole);
        $this->assertEquals('Cole', $cole->name);
        $this->assertEquals('cole', $cole->slug);
    }

    /** @test */
    public function the_choices_method_returns_the_list_of_active_entries()
    {
        LinkType::create(['name' => 'Variant']);
        LinkType::create(['name' => 'Similar']);
        LinkType::create(['name' => 'X-Sell']);

        $this->assertEquals([
            1 => 'Variant',
            2 => 'Similar',
            3 => 'X-Sell',
        ], LinkType::choices());
    }

    /** @test */
    public function the_choices_method_returns_inactive_entries_as_well_if_requested_explicitly()
    {
        LinkType::create(['name' => 'Variant']);
        LinkType::create(['name' => 'Similar']);
        LinkType::create(['name' => 'X-Sell']);
        LinkType::create(['name' => 'Black Friday 2013', 'is_active' => false]);

        $this->assertEquals([
            1 => 'Variant',
            2 => 'Similar',
            3 => 'X-Sell',
            4 => 'Black Friday 2013',
        ], LinkType::choices(true));
    }

    /** @test */
    public function the_choices_method_returns_specific_inactive_entries_as_well_if_an_array_of_integer_ids_is_passed()
    {
        LinkType::create(['name' => 'Variant']);
        LinkType::create(['name' => 'Similar']);
        LinkType::create(['name' => 'X-Sell']);
        LinkType::create(['name' => 'Black Friday 2012', 'is_active' => false]);
        LinkType::create(['name' => 'Black Friday 2013', 'is_active' => false]);
        LinkType::create(['name' => 'Black Friday 2014', 'is_active' => false]);
        LinkType::create(['name' => 'Black Friday 2015', 'is_active' => false]);

        $this->assertEquals([
            1 => 'Variant',
            2 => 'Similar',
            3 => 'X-Sell',
            5 => 'Black Friday 2013',
            7 => 'Black Friday 2015',
        ], LinkType::choices([5, 7]));
    }

    /** @test */
    public function the_choices_method_returns_specific_inactive_entries_as_well_if_an_array_of_strings_slugs_is_passed()
    {
        LinkType::create(['name' => 'Matching Style']);
        LinkType::create(['name' => 'Upsell']);
        LinkType::create(['name' => '2013', 'is_active' => false]);
        LinkType::create(['name' => '2015', 'is_active' => false]);
        LinkType::create(['name' => 'X-Sell', 'is_active' => false]);

        $this->assertEquals([
            1 => 'Matching Style',
            2 => 'Upsell',
            4 => '2015',
            5 => 'X-Sell',
        ], LinkType::choices(['2015', 'x-sell']));
    }

    /** @test */
    public function the_choices_method_can_return_slugs_as_keys()
    {
        LinkType::create(['name' => 'Designer']);
        LinkType::create(['name' => 'Cross Sell']);
        LinkType::create(['name' => 'Color Variant']);
        LinkType::create(['name' => '2019']);

        $this->assertEquals([
            'designer' => 'Designer',
            'cross-sell' => 'Cross Sell',
            'color-variant' => 'Color Variant',
            '2019' => '2019',
        ], LinkType::choices(false, true));
    }
}
