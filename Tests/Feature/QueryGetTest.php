<?php

declare(strict_types=1);

/**
 * Contains the QueryGetTest class.
 *
 * @copyright   Copyright (c) 2022 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2022-02-11
 *
 */

namespace Vanilo\Links\Tests\Feature;

use Illuminate\Support\Collection;
use Vanilo\Links\Models\LinkGroup;
use Vanilo\Links\Models\LinkGroupItem;
use Vanilo\Links\Models\LinkType;
use Vanilo\Links\Query\Get;
use Vanilo\Links\Tests\Dummies\Property;
use Vanilo\Links\Tests\Dummies\TestLinkableProduct;
use Vanilo\Links\Tests\TestCase;

class QueryGetTest extends TestCase
{
    private TestLinkableProduct $galaxyS22;

    private TestLinkableProduct $galaxyS22Plus;

    private TestLinkableProduct $galaxyS22Ultra;

    private LinkType $series;

    private LinkType $variant;

    private LinkGroup $groupSeries;

    private LinkGroup $groupVariant;

    public function setUp(): void
    {
        parent::setUp();
        $this->createTestData();
    }

    /** @test */
    public function two_linked_products_can_be_queried()
    {
        $attrs = ['link_group_id' => $this->groupSeries->id, 'linkable_type' => TestLinkableProduct::class];
        LinkGroupItem::create(array_merge($attrs, ['linkable_id' => $this->galaxyS22->id]));
        LinkGroupItem::create(array_merge($attrs, ['linkable_id' => $this->galaxyS22Plus->id]));
        LinkGroupItem::create(array_merge($attrs, ['linkable_id' => $this->galaxyS22Ultra->id]));

        $linksOfS22 = Get::the('series')->links()->of($this->galaxyS22);

        $this->assertInstanceOf(Collection::class, $linksOfS22);
        $this->assertCount(2, $linksOfS22);
        $this->assertContains($this->galaxyS22Plus->id, $linksOfS22->map->id);
        $this->assertContains($this->galaxyS22Ultra->id, $linksOfS22->map->id);
    }

    /** @test */
    public function calling_on_models_without_links_returns_an_empty_collection()
    {
        $seriesLinks = Get::the('series')->links()->of($this->galaxyS22);

        $this->assertInstanceOf(Collection::class, $seriesLinks);
        $this->assertCount(0, $seriesLinks);
    }

    /** @test */
    public function calling_on_models_that_arent_in_a_group_returns_an_empty_collection()
    {
        $attrs = ['link_group_id' => $this->groupSeries->id, 'linkable_type' => TestLinkableProduct::class];
        LinkGroupItem::create(array_merge($attrs, ['linkable_id' => $this->galaxyS22Ultra->id]));
        LinkGroupItem::create(array_merge($attrs, ['linkable_id' => $this->galaxyS22Plus->id]));

        $seriesLinks = Get::the('series')->links()->of($this->galaxyS22);

        $this->assertInstanceOf(Collection::class, $seriesLinks);
        $this->assertCount(0, $seriesLinks);
    }

    /** @test */
    public function calling_on_models_that_arent_in_the_given_group_returns_an_empty_collection()
    {
        $attrs = ['link_group_id' => $this->groupSeries->id, 'linkable_type' => TestLinkableProduct::class];
        LinkGroupItem::create(array_merge($attrs, ['linkable_id' => $this->galaxyS22->id]));
        LinkGroupItem::create(array_merge($attrs, ['linkable_id' => $this->galaxyS22Plus->id]));

        $variantLinks = Get::the('variant')->links()->of($this->galaxyS22);

        $this->assertInstanceOf(Collection::class, $variantLinks);
        $this->assertCount(0, $variantLinks);
    }

    /** @test */
    public function groups_of_different_types_arent_mixed_up()
    {
        // Series Group
        $attrs1 = ['link_group_id' => $this->groupSeries->id, 'linkable_type' => TestLinkableProduct::class];
        LinkGroupItem::create(array_merge($attrs1, ['linkable_id' => $this->galaxyS22->id]));
        LinkGroupItem::create(array_merge($attrs1, ['linkable_id' => $this->galaxyS22Plus->id]));

        // Variant Group
        $attrs2 = ['link_group_id' => $this->groupVariant->id, 'linkable_type' => TestLinkableProduct::class];
        LinkGroupItem::create(array_merge($attrs2, ['linkable_id' => $this->galaxyS22->id]));
        LinkGroupItem::create(array_merge($attrs2, ['linkable_id' => $this->galaxyS22Ultra->id]));

        $seriesLinks = Get::the('series')->links()->of($this->galaxyS22);
        $variantLinks = Get::the('variant')->links()->of($this->galaxyS22);

        $this->assertCount(1, $seriesLinks);
        $this->assertContains($this->galaxyS22Plus->id, $seriesLinks->map->id);

        $this->assertCount(1, $variantLinks);
        $this->assertContains($this->galaxyS22Ultra->id, $variantLinks->map->id);
    }

    /** @test */
    public function an_optional_property_can_be_specified_to_filter_groups_by()
    {
        $variantGroup22 = LinkGroup::create(['link_type_id' => $this->variant->id, 'property_id' => 22])->fresh();
        $variantGroup33 = LinkGroup::create(['link_type_id' => $this->variant->id, 'property_id' => 33])->fresh();

        // Variant 22 Group
        $attrs22 = ['link_group_id' => $variantGroup22->id, 'linkable_type' => TestLinkableProduct::class];
        LinkGroupItem::create(array_merge($attrs22, ['linkable_id' => $this->galaxyS22->id]));
        LinkGroupItem::create(array_merge($attrs22, ['linkable_id' => $this->galaxyS22Plus->id]));

        // Variant 33 Group
        $attrs33 = ['link_group_id' => $variantGroup33->id, 'linkable_type' => TestLinkableProduct::class];
        LinkGroupItem::create(array_merge($attrs33, ['linkable_id' => $this->galaxyS22->id]));
        LinkGroupItem::create(array_merge($attrs33, ['linkable_id' => $this->galaxyS22Ultra->id]));

        $allVariants = Get::the('variant')->links()->of($this->galaxyS22);
        $this->assertCount(2, $allVariants);
        $this->assertContains($this->galaxyS22Ultra->id, $allVariants->map->id);
        $this->assertContains($this->galaxyS22Plus->id, $allVariants->map->id);

        $variants22 = Get::the('variant')->links()->basedOn(22)->of($this->galaxyS22);
        $this->assertCount(1, $variants22);
        $this->assertContains($this->galaxyS22Plus->id, $variants22->map->id);

        $variants33 = Get::the('variant')->links()->basedOn(33)->of($this->galaxyS22);
        $this->assertCount(1, $variants33);
        $this->assertContains($this->galaxyS22Ultra->id, $variants33->map->id);
    }

    /** @test */
    public function the_property_filter_can_be_passed_by_slug()
    {
        Get::usePropertiesModel(Property::class);

        $seriesProperty = Property::create(['name' => 'Series', 'slug' => 'series', 'type' => 'string'])->fresh();
        $screenProperty = Property::create(['name' => 'Screen', 'slug' => 'screen', 'type' => 'string'])->fresh();
        $groupSeries = LinkGroup::create(['link_type_id' => $this->variant->id, 'property_id' => $seriesProperty->id])->fresh();
        $groupScreen = LinkGroup::create(['link_type_id' => $this->variant->id, 'property_id' => $screenProperty->id])->fresh();

        // Variant "Series" Group
        $attrsSeries = ['link_group_id' => $groupSeries->id, 'linkable_type' => TestLinkableProduct::class];
        LinkGroupItem::create(array_merge($attrsSeries, ['linkable_id' => $this->galaxyS22->id]));
        LinkGroupItem::create(array_merge($attrsSeries, ['linkable_id' => $this->galaxyS22Plus->id]));
        LinkGroupItem::create(array_merge($attrsSeries, ['linkable_id' => $this->galaxyS22Ultra->id]));

        // Variant "Screen" Group
        $attrsScreen = ['link_group_id' => $groupScreen->id, 'linkable_type' => TestLinkableProduct::class];
        LinkGroupItem::create(array_merge($attrsScreen, ['linkable_id' => $this->galaxyS22->id]));
        LinkGroupItem::create(array_merge($attrsScreen, ['linkable_id' => $this->galaxyS22Plus->id]));

        $variantsBySeries = Get::the('variant')->links()->basedOn('series')->of($this->galaxyS22);
        $this->assertCount(2, $variantsBySeries);
        $this->assertContains($this->galaxyS22Plus->id, $variantsBySeries->map->id);
        $this->assertContains($this->galaxyS22Ultra->id, $variantsBySeries->map->id);

        $variantsByScreen = Get::the('variant')->links()->basedOn('screen')->of($this->galaxyS22);
        $this->assertCount(1, $variantsByScreen);
        $this->assertContains($this->galaxyS22Plus->id, $variantsByScreen->map->id);
    }

    /** @test */
    public function it_can_retrieve_the_link_groups_of_a_model()
    {
        // Series Group
        $attrs1 = ['link_group_id' => $this->groupSeries->id, 'linkable_type' => TestLinkableProduct::class];
        LinkGroupItem::create(array_merge($attrs1, ['linkable_id' => $this->galaxyS22->id]));
        // Variant Group
        $attrs2 = ['link_group_id' => $this->groupVariant->id, 'linkable_type' => TestLinkableProduct::class];
        LinkGroupItem::create(array_merge($attrs2, ['linkable_id' => $this->galaxyS22->id]));

        $seriesGroups = Get::the('series')->groups()->of($this->galaxyS22);
        $this->assertCount(1, $seriesGroups);
        $this->assertInstanceOf(LinkGroup::class, $seriesGroups->first());
    }

    /** @ test */
    public function it_resolves_the_types_via_a_magic_method_call()
    {
        // @todo implement this
        // via magic __call:
        Get::variant()->links()->of($product1);
        Get::upsell()->links()->of($product1);

        // in blade templates;
        links('upsell')->of($product1);
        links('variant')->basedOn('shoe-size')->of($product1);
        variants('shoe-size')->of($product1);
    }

    protected function setUpDatabase($app)
    {
        $this->loadMigrationsFrom(dirname(__DIR__) . '/migrations_of_property_module');
        parent::setUpDatabase($app);
    }

    private function createTestData(): void
    {
        $this->galaxyS22 = TestLinkableProduct::create(['name' => 'Galaxy S22']);
        $this->galaxyS22Plus = TestLinkableProduct::create(['name' => 'Galaxy S22 Plus']);
        $this->galaxyS22Ultra = TestLinkableProduct::create(['name' => 'Galaxy S22 Ultra']);

        $this->series = LinkType::create(['name' => 'Series']);
        $this->variant = LinkType::create(['name' => 'Variant']);

        $this->groupSeries = LinkGroup::create(['link_type_id' => $this->series->id])->fresh();
        $this->groupVariant = LinkGroup::create(['link_type_id' => $this->variant->id])->fresh();
    }
}
