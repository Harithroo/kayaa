<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\AdminUserResource\Pages\CreateAdminUser;
use App\Filament\Resources\AdminUserResource\Pages\EditAdminUser;
use App\Filament\Resources\AdminUserResource\Pages\ListAdminUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Concerns\BuildsShopData;
use Tests\TestCase;

class AdminTeamTest extends TestCase
{
    use BuildsShopData, RefreshDatabase;

    public function test_the_list_shows_admins_only(): void
    {
        $me = $this->actingAsAdmin();
        $colleague = $this->makeAdmin();
        $shopper = $this->customer();

        Livewire::test(ListAdminUsers::class)
            ->assertCanSeeTableRecords([$me, $colleague])
            ->assertCanNotSeeTableRecords([$shopper]);
    }

    public function test_creating_an_admin_sets_the_password_and_the_admin_flag(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateAdminUser::class)
            ->fillForm(['name' => 'Second Admin', 'email' => 'second@example.test', 'password' => 'a-good-password'])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::where('email', 'second@example.test')->firstOrFail();
        $this->assertTrue($created->is_admin);
        $this->assertTrue(Hash::check('a-good-password', $created->password));
    }

    public function test_a_password_is_required_when_creating(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateAdminUser::class)
            ->fillForm(['name' => 'No Password', 'email' => 'np@example.test', 'password' => ''])
            ->call('create')
            ->assertHasFormErrors(['password' => 'required']);
    }

    public function test_editing_without_a_password_keeps_the_old_one_and_with_one_changes_it(): void
    {
        $this->actingAsAdmin();
        $colleague = $this->makeAdmin();
        $oldHash = $colleague->password;

        Livewire::test(EditAdminUser::class, ['record' => $colleague->getKey()])
            ->fillForm(['name' => 'Renamed', 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Renamed', $colleague->fresh()->name);
        $this->assertSame($oldHash, $colleague->fresh()->password);

        Livewire::test(EditAdminUser::class, ['record' => $colleague->getKey()])
            ->fillForm(['password' => 'brand-new-password'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('brand-new-password', $colleague->fresh()->password));
        $this->assertTrue($colleague->fresh()->is_admin);
    }

    public function test_an_admin_cannot_delete_themselves(): void
    {
        $me = $this->actingAsAdmin();
        $this->makeAdmin();

        $this->assertFalse($me->canBeDeleted());
        $this->assertFalse($me->delete(), 'the model refuses, whatever screen asks');
        $this->assertNotNull($me->fresh());

        Livewire::test(EditAdminUser::class, ['record' => $me->getKey()])->assertActionHidden('delete');
    }

    public function test_an_admin_can_delete_a_colleague(): void
    {
        $this->actingAsAdmin();
        $colleague = $this->makeAdmin();

        Livewire::test(EditAdminUser::class, ['record' => $colleague->getKey()])
            ->assertActionVisible('delete')
            ->callAction('delete');

        $this->assertNull($colleague->fresh());
    }

    public function test_the_last_admin_cannot_be_deleted_even_by_code_with_nobody_signed_in(): void
    {
        $only = User::create(['name' => 'Only', 'email' => 'only@example.test', 'password' => 'secret-pass-123', 'is_admin' => true]);

        $this->assertFalse($only->canBeDeleted());
        $this->assertFalse($only->delete());
        $this->assertNotNull($only->fresh());
    }

    public function test_the_last_admin_cannot_be_demoted(): void
    {
        $only = User::create(['name' => 'Only', 'email' => 'only@example.test', 'password' => 'secret-pass-123', 'is_admin' => true]);

        $only->is_admin = false;
        $this->assertFalse($only->save());
        $this->assertTrue($only->fresh()->is_admin);

        // With a second admin around, demotion is allowed again.
        $this->makeAdmin();
        $only->is_admin = false;
        $this->assertTrue($only->save());
    }

    public function test_deleting_a_customer_is_not_affected(): void
    {
        $this->actingAsAdmin();
        $shopper = $this->customer();

        $this->assertTrue($shopper->canBeDeleted());
        $this->assertTrue($shopper->delete());
    }

    public function test_the_admin_team_screens_render(): void
    {
        $this->actingAsAdmin();

        $this->get('/admin/admin-users')->assertOk()->assertSee('Admin team');
        $this->get('/admin/admin-users/create')->assertOk();
    }

    private function makeAdmin(): User
    {
        return User::create([
            'name' => 'Colleague',
            'email' => 'colleague'.++$this->seq.'@example.test',
            'password' => 'secret-pass-123',
            'is_admin' => true,
        ]);
    }
}
