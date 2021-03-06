<?php

namespace Tests\Browser;

use App\Dock;
use App\Ship;
use App\User;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Tests\Browser\Components\IndexComponent;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class DateTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * @test
     */
    public function resource_can_be_created()
    {
        $this->seed();

        $dock = factory(Dock::class)->create();

        $date = now()->subHours(1);
        $uiDate = $date->setTimezone(env('DUSK_TIMEZONE'))->format('Y-m-d g:i:s A');
        $formattedDate = $date->setTimezone(env('DUSK_TIMEZONE'))->format('Y-m-d H:i:s');

        $this->browse(function (Browser $browser) use ($dock, $uiDate, $formattedDate) {
            $browser->loginAs(User::find(1))
                    ->visit(new Pages\Detail('docks', $dock->id))
                    ->within(new IndexComponent('ships'), function ($browser) {
                        $browser->click('@create-button');
                    })
                    ->on(new Pages\Create('ships'))
                    ->type('@name', 'Titanic')
                    ->type('@departed_at', $formattedDate)
                    ->create();

            $ship = Ship::orderBy('id', 'desc')->first();

            // Asset the date is UTC in the database...
            $this->assertEquals(
                    $formattedDate,
                    $ship->departed_at->setTimezone(env('DUSK_TIMEZONE'))->format('Y-m-d H:i:s')
                );

            // Assert the date is localized on the detail page...
            $browser->on(new Pages\Detail('ships', $ship->id))
                        ->assertSee($uiDate);

            $browser->assertPathIs('/nova/resources/ships/'.$ship->id);

            // Assert the date is localized on the index page...
            $browser->visit(new Pages\Index('ships'))
                        ->within(new IndexComponent('ships'), function ($browser) use ($uiDate) {
                            $browser->assertSee($uiDate);
                        });
        });
    }
}
