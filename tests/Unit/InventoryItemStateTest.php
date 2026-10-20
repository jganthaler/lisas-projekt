<?php

namespace Tests\Unit;

use App\Models\InventoryItem;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The MHD logic is pure date math - the framework test case is only needed
 * because the "date" cast asks the connection for its date format. No
 * database is touched, so the assertions stay fast and deterministic.
 * "Today" is frozen to keep them independent of the day the suite runs on.
 */
class InventoryItemStateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-15 09:30:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function item(?string $bestBefore, int $quantity = 1): InventoryItem
    {
        return new InventoryItem([
            'name' => 'Testartikel',
            'quantity' => $quantity,
            'best_before' => $bestBefore,
        ]);
    }

    public function test_items_without_a_date_are_unknown(): void
    {
        $item = $this->item(null);

        $this->assertNull($item->daysUntilBestBefore());
        $this->assertSame(InventoryItem::STATE_UNKNOWN, $item->state());
        $this->assertSame('ohne MHD', $item->stateLabel());
        $this->assertSame('kein MHD', $item->bestBeforeLabel());
        $this->assertSame('badge-neutral', $item->stateBadgeClass());
        $this->assertFalse($item->isExpired());
        $this->assertFalse($item->isExpiringSoon());
        $this->assertNull($item->formattedBestBefore());
    }

    public function test_the_day_itself_counts_as_expiring_soon(): void
    {
        $item = $this->item('2026-06-15');

        $this->assertSame(0, $item->daysUntilBestBefore());
        $this->assertSame(InventoryItem::STATE_SOON, $item->state());
        $this->assertSame('MHD heute', $item->bestBeforeLabel());
        $this->assertSame('badge-warn', $item->stateBadgeClass());
    }

    public function test_yesterday_is_expired(): void
    {
        $item = $this->item('2026-06-14');

        $this->assertSame(-1, $item->daysUntilBestBefore());
        $this->assertSame(InventoryItem::STATE_EXPIRED, $item->state());
        $this->assertSame('MHD seit gestern', $item->bestBeforeLabel());
        $this->assertSame('badge-danger', $item->stateBadgeClass());
        $this->assertTrue($item->isExpired());
    }

    public function test_older_dates_report_the_number_of_days(): void
    {
        $item = $this->item('2026-06-12');

        $this->assertSame(-3, $item->daysUntilBestBefore());
        $this->assertSame('MHD vor 3 Tagen', $item->bestBeforeLabel());
    }

    public function test_the_last_day_of_the_warning_window_is_still_soon(): void
    {
        $item = $this->item(Carbon::today()->addDays(InventoryItem::WARNING_DAYS)->toDateString());

        $this->assertSame(InventoryItem::WARNING_DAYS, $item->daysUntilBestBefore());
        $this->assertSame(InventoryItem::STATE_SOON, $item->state());
        $this->assertSame('MHD in 7 Tagen', $item->bestBeforeLabel());
        $this->assertTrue($item->isExpiringSoon());
    }

    public function test_one_day_beyond_the_warning_window_is_fresh(): void
    {
        $item = $this->item(Carbon::today()->addDays(InventoryItem::WARNING_DAYS + 1)->toDateString());

        $this->assertSame(InventoryItem::STATE_FRESH, $item->state());
        $this->assertSame('frisch', $item->stateLabel());
        $this->assertSame('badge-ok', $item->stateBadgeClass());
        $this->assertFalse($item->isExpiringSoon());
    }

    public function test_tomorrow_is_expiring_soon(): void
    {
        $this->assertSame('MHD morgen', $this->item('2026-06-16')->bestBeforeLabel());
        $this->assertSame('15.06.2026', $this->item('2026-06-15')->formattedBestBefore());
    }

    /**
     * The quick choices of the item form ("3 Monate", "6 Monate", "1 Jahr",
     * "2 Jahre") are today plus their span - a year is twelve months, two years
     * are twenty-four (see InventoryItem::BEST_BEFORE_PRESETS). "Today" is the
     * frozen 15.06.2026 of setUp().
     */
    public function test_a_quick_choice_points_at_today_plus_its_span(): void
    {
        $this->assertSame('2026-09-15', InventoryItem::bestBeforeForPreset('3_months'));
        $this->assertSame('2026-12-15', InventoryItem::bestBeforeForPreset('6_months'));
        $this->assertSame('2027-06-15', InventoryItem::bestBeforeForPreset('1_year'));
        $this->assertSame('2028-06-15', InventoryItem::bestBeforeForPreset('2_years'));
    }

    /**
     * A quick choice stays inside the month it names: from the 31.08. is
     * "3 Monate" the 30.11. and not the 01.12., which would already be a
     * different month. A choice the app does not know yields nothing at all.
     */
    public function test_a_quick_choice_never_overflows_into_the_next_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-31 09:30:00'));

        $this->assertSame('2026-11-30', InventoryItem::bestBeforeForPreset('3_months'));
        $this->assertSame('2027-02-28', InventoryItem::bestBeforeForPreset('6_months'));
        $this->assertSame('2027-08-31', InventoryItem::bestBeforeForPreset('1_year'));
        $this->assertNull(InventoryItem::bestBeforeForPreset('10_years'));
    }

    public function test_names_are_trimmed_and_collapsed(): void
    {
        $this->assertSame('Hack fleisch', InventoryItem::normalizeName("  Hack \t fleisch  "));
        $this->assertSame('Erbsen', InventoryItem::normalizeName('Erbsen'));
    }

    public function test_the_sort_key_transliterates_german_umlauts(): void
    {
        $this->assertSame('aepfel', InventoryItem::sortKey('Äpfel'));
        $this->assertSame('muesli', InventoryItem::sortKey('Müsli'));
        $this->assertSame('griessbrei', InventoryItem::sortKey('Grießbrei'));
        $this->assertSame('zitrone', InventoryItem::sortKey('  Zitrone '));
    }
}
